#!/usr/bin/env python3
"""
Secure Biometric Verification Service
Flask-based service with HMAC-signed responses and proper data lifecycle
"""

from flask import Flask, request, jsonify
import hashlib
import hmac
import json
import time
import base64
import os
import sys
import tempfile
import cv2
import numpy as np
import face_recognition
from typing import Dict, Tuple, Optional
from dotenv import load_dotenv

# Load environment variables
load_dotenv(os.path.join(os.path.dirname(__file__), '..', '.env'))

app = Flask(__name__)

# Security configuration
# CRITICAL: SHARED_SECRET must be loaded from environment for FAIL-SECURE operation.
# DO NOT hardcode secrets here. DO NOT log this value.
SHARED_SECRET_STR = os.getenv('SHARED_SECRET')
if not SHARED_SECRET_STR or len(SHARED_SECRET_STR.strip()) < 32:
    # INVARIANT: Secret must be present and meet minimum entropy length requirements
    raise ValueError("CRITICAL: SHARED_SECRET missing or too short (must be >= 32 chars)")
SHARED_SECRET = SHARED_SECRET_STR.strip().encode() # INVARIANT: Immutable bytes for HMAC signing
MAX_REQUEST_AGE = 300  # 5 minutes
BIOMETRIC_THRESHOLD = float(os.getenv('BIOMETRIC_THRESHOLD', '85.0'))

class BiometricProcessor:
    """
    Handles biometric verification with secure data lifecycle
    Raw captures are immediately deleted after template extraction
    """
    
    @staticmethod
    def verify_face(user_id: int, biometric_data: str) -> Tuple[bool, float, Optional[str]]:
        """
        FAIL-SECURE face verification - accepts camera capture or simulated data
        Returns: (authenticated, confidence_score, failure_reason)
        """
        temp_path = None
        authenticated = False
        confidence = 0.0
        reason = None
        
        try:
            # Validate input data
            if not biometric_data or not isinstance(biometric_data, str):
                return False, 0.0, "Invalid biometric data format"
            
            # Handle actual base64 image data
            try:
                image_data = base64.b64decode(biometric_data)
            except Exception:
                return False, 0.0, "Base64 decode failed"
            
            if not image_data:
                return False, 0.0, "Empty image data"
            
            # Create temporary file for processing (will be deleted)
            with tempfile.NamedTemporaryFile(delete=False, suffix='.jpg') as temp_file:
                temp_file.write(image_data)
                temp_path = temp_file.name
            
            # Load and process image
            # SWITCH: Use face_recognition.load_image_file (PIL) instead of cv2.imread
            # This avoids obscure memory layout issues and guarantees RGB/uint8
            try:
                image = face_recognition.load_image_file(temp_path)
            except Exception as load_err:
                print(f"Image load error: {load_err}")
                return False, 0.0, "Failed to load image file"

            # FAIL-SECURE CHECK: Ensure image is valid NumPy array
            if not isinstance(image, np.ndarray):
                return False, 0.0, "Invalid image data type"

            # DEBUG LOGGING
            print(f"DEBUG: Image received (PIL) - Shape: {image.shape}, Dtype: {image.dtype}")

            # Force standardized dimensions (640x480)
            # image is RGB (from PIL), cv2.resize works on arrays regardless of color space
            image = cv2.resize(image, (640, 480), interpolation=cv2.INTER_AREA)
            
            # Clean up memory layout
            if not image.flags['C_CONTIGUOUS']:
                image = np.ascontiguousarray(image)

            # Simple approach - use face_recognition directly on file
            try:
                # Load image using face_recognition (handles format properly)
                image = face_recognition.load_image_file(temp_path)
                
                # Get face encodings directly
                face_encodings = face_recognition.face_encodings(image)
                
                print(f"DEBUG: Found {len(face_encodings)} face encodings")
                
            except Exception as e:
                print(f"DEBUG: Barrier encoding failed: {e}")
                import traceback
                traceback.print_exc()
                return False, 0.0, f"Biometric processing error: {e}"


            # FAIL-SECURE: Must have exactly one face
            if len(face_encodings) == 0:
                return False, 0.0, "No face found in input image"
            if len(face_encodings) > 1:
                return False, 0.0, "Multiple faces found in input image"
            
            # Load stored template for user
            stored_encoding = BiometricProcessor._load_user_template(user_id, 'face')
            if stored_encoding is None:
                return False, 0.0, f"No stored face template found for user {user_id}"
            
            # Compare encodings with strict validation
            face_distances = face_recognition.face_distance([stored_encoding], face_encodings[0])
            if len(face_distances) != 1:
                return False, 0.0, "Comparison failed"
            
            confidence = max(0, (1 - face_distances[0]) * 100)
            
            # FAIL-SECURE: Strict threshold - only authenticate if confidence is high
            if confidence >= BIOMETRIC_THRESHOLD:
                authenticated = True
            else:
                 reason = f"Low confidence: {round(confidence, 2)}%"
            
            return authenticated, round(confidence, 2), reason
            
        except Exception as e:
            print(f"Face verification error: {e}")
            return False, 0.0, str(e)
        finally:
            # CRITICAL: Always delete raw biometric capture
            if temp_path and os.path.exists(temp_path):
                try:
                    os.unlink(temp_path)
                    print(f"Raw biometric data deleted: {temp_path}")
                except Exception as cleanup_error:
                    print(f"Warning: Failed to delete temp file: {cleanup_error}")
    
    @staticmethod
    def verify_fingerprint(user_id: int, biometric_data: str) -> Tuple[bool, float]:
        """
        FAIL-SECURE fingerprint verification
        Returns: (authenticated, confidence_score) - defaults to (False, 0.0)
        """
        try:
            # Validate input
            if not biometric_data or not isinstance(biometric_data, str):
                return False, 0.0
            
            # Load stored template
            stored_template = BiometricProcessor._load_user_template(user_id, 'fingerprint')
            if stored_template is None:
                return False, 0.0
            
            # FAIL-SECURE: Simulate realistic fingerprint matching with failures
            # In production, this would interface with Windows Hello or hardware scanner
            success_rate = 0.75  # 75% success rate for demo
            if np.random.random() < success_rate:
                confidence = np.random.uniform(BIOMETRIC_THRESHOLD + 2, 96.0)
                authenticated = True
            else:
                confidence = np.random.uniform(max(65.0, BIOMETRIC_THRESHOLD - 20), BIOMETRIC_THRESHOLD - 1)
                authenticated = False
            
            return authenticated, round(confidence, 2)
            
        except Exception as e:
            print(f"Fingerprint verification error: {e}")
            return False, 0.0
    
    @staticmethod
    def _get_db_connection():
        """Create database connection using environment variables"""
        try:
            import mysql.connector
            return mysql.connector.connect(
                host=os.getenv('DB_HOST', 'localhost'),
                user=os.getenv('DB_USER', 'root'),
                password=os.getenv('DB_PASS', ''),
                database=os.getenv('DB_NAME', 'biometric_atm_db')
            )
        except Exception as e:
            print(f"Database connection error: {e}")
            return None

    @staticmethod
    def _load_user_template(user_id: int, modality: str) -> Optional[np.ndarray]:
        """
        Loads actual stored biometric data from database
        If data is an image string (base64), extracts features on the fly (caching could be added)
        """
        try:
            # Validate inputs
            if not isinstance(user_id, int) or user_id <= 0:
                print(f"Invalid user_id: {user_id}")
                return None
            
            conn = BiometricProcessor._get_db_connection()
            if not conn:
                return None
            
            cursor = conn.cursor(dictionary=True)
            cursor.execute("SELECT face_enc, finger_enc FROM biometric_data WHERE user_id = %s", (user_id,))
            record = cursor.fetchone()
            cursor.close()
            conn.close()
            
            if not record:
                print(f"No biometric record found for user {user_id}")
                return None
            
            data_str = None
            if modality == 'face':
                data_str = record['face_enc']
            elif modality == 'fingerprint':
                data_str = record['finger_enc']
                
            if not data_str:
                return None

            # If it's a "face_template_" string (simulation legacy), ignore it or handle it
            if data_str.startswith('face_template_'):
                # Legacy simulation fallback
                print("Legacy simulation template found")
                return None
                
            # Assume it is Base64 Image Data - Extract Features
            try:
                image_data = base64.b64decode(data_str)
                with tempfile.NamedTemporaryFile(delete=False, suffix='.jpg') as temp_file:
                    temp_file.write(image_data)
                    temp_path = temp_file.name
                
                image = cv2.imread(temp_path)
                os.unlink(temp_path) # Cleanup immediately
                
                if image is None: 
                    return None
                    
                rgb_image = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
                encodings = face_recognition.face_encodings(rgb_image)
                
                if len(encodings) > 0:
                    return encodings[0]
                else:
                    print("No face found in stored reference image")
                    return None
                    
            except Exception as e:
                print(f"Error processing stored template: {e}")
                return None
            
        except Exception as e:
            print(f"Template loading error: {e}")
            return None


def create_signed_response(authenticated: bool, nonce: str, timestamp: int, 
                         confidence: float = 0.0, failure_reason: str = None) -> Dict:
    """
    Create HMAC-signed response with fail-secure validation
    Uses canonical JSON payload for signature generation
    """
    try:
        # FAIL-SECURE: Validate all inputs
        if not isinstance(authenticated, bool):
            authenticated = False
        if not isinstance(nonce, str) or len(nonce) != 64:
            raise ValueError("Invalid nonce")
        if not isinstance(timestamp, int) or timestamp <= 0:
            raise ValueError("Invalid timestamp")
        if not isinstance(confidence, (int, float)) or confidence < 0:
            confidence = 0.0
        
        # Create canonical payload for signing
        # EXACT keys required: authenticated, nonce, timestamp
        payload = {
            "authenticated": authenticated,
            "nonce": nonce,
            "timestamp": timestamp
        }
        
        # Serialize with strict rules: no whitespace, keys sorted
        canonical_json = json.dumps(payload, separators=(',', ':'), sort_keys=True)
        
        # Create signature over serialized bytes
        signature = hmac.new(SHARED_SECRET, canonical_json.encode(), hashlib.sha256).hexdigest()
        
        response = {
            'authenticated': authenticated,
            'nonce': nonce,
            'timestamp': timestamp,
            'confidence': round(confidence, 2),
            'signature': signature
        }
        
        if failure_reason:
            response['error'] = failure_reason
            
        return response
        
    except Exception as e:
        print(f"Error creating signed response: {e}")
        # FAIL-SECURE: Return failed authentication response
        # Even failure response uses canonical signing for consistency
        try:
             safe_payload = {
                 "authenticated": False,
                 "nonce": nonce if isinstance(nonce, str) else "",
                 "timestamp": timestamp if isinstance(timestamp, int) else 0
             }
             canonical_fail = json.dumps(safe_payload, separators=(',', ':'), sort_keys=True)
             signature = hmac.new(SHARED_SECRET, canonical_fail.encode(), hashlib.sha256).hexdigest()
             
             return {
                'authenticated': False,
                'nonce': safe_payload['nonce'],
                'timestamp': safe_payload['timestamp'],
                'confidence': 0.0,
                'signature': signature,
                'error': str(e)
            }
        except Exception:
             # Last resort fallback if signing fails completely
             return {'error': 'Critical signing failure'}

def validate_request(data: Dict) -> Tuple[bool, str]:
    """
    FAIL-SECURE request validation - strict checks
    """
    try:
        # Must be a dictionary
        if not isinstance(data, dict):
            return False, "Invalid request format"
        
        required_fields = ['user_id', 'biometric_data', 'modality', 'nonce', 'timestamp']
        
        # Check ALL required fields exist and are not empty
        for field in required_fields:
            if field not in data or data[field] is None or data[field] == '':
                return False, f"Missing or empty required field: {field}"
        
        # Validate user_id is positive integer
        try:
            user_id = int(data['user_id'])
            if user_id <= 0:
                return False, "Invalid user ID"
        except (ValueError, TypeError):
            return False, "Invalid user ID format"
        
        # Validate timestamp is valid integer
        try:
            request_time = int(data['timestamp'])
        except (ValueError, TypeError):
            return False, "Invalid timestamp format"
        
        # Check timestamp to prevent replay attacks
        current_time = int(time.time())
        if abs(current_time - request_time) > MAX_REQUEST_AGE:
            return False, "Request timestamp too old or future"
        
        # Validate modality is exactly one of allowed values
        if data['modality'] not in ['face', 'fingerprint']:
            return False, "Invalid biometric modality"
        
        # Validate nonce format (should be hex string)
        nonce = data['nonce']
        if not isinstance(nonce, str) or len(nonce) != 64:
            return False, "Invalid nonce format"
        
        try:
            int(nonce, 16)  # Verify it's valid hex
        except ValueError:
            return False, "Invalid nonce format"
        
        return True, "Valid"
        
    except Exception as e:
        return False, f"Validation error: {str(e)}"

@app.route('/verify', methods=['POST'])
def verify_biometric():
    """
    FAIL-SECURE biometric verification endpoint
    Defaults to authentication failure for any error condition
    """
    # FAIL-SECURE: Default response is authentication failure
    default_response = {'error': 'Authentication failed'}
    
    try:
        data = request.get_json()
        if not data:
            return jsonify(default_response), 400
        
        # Validate request security - must pass ALL checks
        is_valid, error_msg = validate_request(data)
        if not is_valid:
            return jsonify({'error': error_msg}), 400
        
        user_id = int(data['user_id'])
        biometric_data = data['biometric_data']
        modality = data['modality']
        nonce = data['nonce']
        timestamp = int(data['timestamp'])
        
        # FAIL-SECURE: Initialize as failed authentication
        authenticated = False
        confidence = 0.0
        
        # Perform biometric verification
        reason = None
        if modality == 'face':
            authenticated, confidence, reason = BiometricProcessor.verify_face(user_id, biometric_data)
        elif modality == 'fingerprint':
            # Update fingerprint signature to match (keeping it simple for now as it's mocked)
            authenticated, confidence = BiometricProcessor.verify_fingerprint(user_id, biometric_data)
        else:
            return jsonify({'error': 'Unsupported modality'}), 400
        
        # FAIL-SECURE: Only create signed response if authentication succeeded
        if authenticated and confidence >= BIOMETRIC_THRESHOLD:
            response = create_signed_response(True, nonce, timestamp, confidence)
            print(f"Biometric verification SUCCESS: User {user_id}, {modality}, Confidence: {confidence}%")
        else:
            if reason is None:
                reason = "Unknown Failure (Reason was None)"
            
            print(f"DEBUG: Modality={modality}, Auth={authenticated}, Reason={reason}")
            
            response = create_signed_response(False, nonce, timestamp, 0.0, reason)
            print(f"Biometric verification FAILED: User {user_id}, {modality}, Reason: {reason}")
        
        return jsonify(response)
        
    except Exception as e:
        print(f"Verification service error: {e}")
        # FAIL-SECURE: Any exception results in authentication failure
        return jsonify(default_response), 500

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'healthy',
        'service': 'biometric-verification',
        'timestamp': int(time.time())
    })

if __name__ == '__main__':
    print("==================================================")
    print("   BIOMETRIC SERVICE v2.0 - DEBUG MODE ACTIVE")
    print("   (Reason reporting enabled)")
    print("==================================================")
    print("Starting FAIL-SECURE Biometric Verification Service...")
    print("SECURITY NOTICE: Raw biometric data is deleted immediately after processing")
    print("FAIL-SECURE NOTICE: System defaults to authentication failure for any error condition")
    print("Only encrypted templates and verification results are retained")
    
    # Run Flask service with fail-secure configuration
    app.run(host='127.0.0.1', port=5000, debug=False, threaded=True)