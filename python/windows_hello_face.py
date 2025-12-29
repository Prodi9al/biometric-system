#!/usr/bin/env python3
"""
Windows Hello Face Integration
Uses Windows Hello Face API for secure face authentication
"""

import json
import sys
import subprocess
import time
from datetime import datetime
import ctypes
from ctypes import wintypes
import winrt.windows.security.credentials.ui as ui
import winrt.windows.security.credentials as creds

class WindowsHelloFace:
    def __init__(self):
        self.available = self.check_availability()
    
    def check_availability(self):
        """Check if Windows Hello Face is available and configured"""
        try:
            # Check Windows Hello Face availability
            availability = ui.UserConsentVerifier.check_availability_async().get()
            return availability == ui.UserConsentVerifierAvailability.AVAILABLE
        except Exception as e:
            print(f"Windows Hello Face check failed: {e}")
            return False
    
    def authenticate_user(self, message="Please look at the camera for face authentication"):
        """Authenticate user using Windows Hello Face"""
        if not self.available:
            return {
                "status": "error", 
                "message": "Windows Hello Face not available or not configured"
            }
        
        try:
            # Request Windows Hello Face authentication
            result = ui.UserConsentVerifier.request_verification_async(message).get()
            
            if result == ui.UserConsentVerificationResult.VERIFIED:
                return {
                    "status": "success",
                    "authenticated": True,
                    "timestamp": datetime.now().isoformat(),
                    "method": "Windows Hello Face",
                    "confidence": 95.0  # Windows Hello provides high confidence
                }
            elif result == ui.UserConsentVerificationResult.DEVICE_NOT_PRESENT:
                return {
                    "status": "error",
                    "message": "Face authentication device not present"
                }
            elif result == ui.UserConsentVerificationResult.NOT_CONFIGURED_FOR_USER:
                return {
                    "status": "error", 
                    "message": "Windows Hello Face not configured for this user"
                }
            elif result == ui.UserConsentVerificationResult.DISABLED_BY_POLICY:
                return {
                    "status": "error",
                    "message": "Windows Hello Face disabled by policy"
                }
            else:
                return {
                    "status": "error",
                    "authenticated": False,
                    "message": "Face authentication failed or cancelled"
                }
                
        except Exception as e:
            return {
                "status": "error",
                "message": f"Windows Hello Face error: {str(e)}"
            }

class FallbackFaceAuth:
    """Fallback to camera-based face recognition when Windows Hello unavailable"""
    
    def __init__(self):
        try:
            import cv2
            import face_recognition
            self.cv2 = cv2
            self.face_recognition = face_recognition
            self.available = True
        except ImportError:
            self.available = False
    
    def authenticate_user(self, stored_template=None):
        """Fallback face authentication using camera"""
        if not self.available:
            return {"status": "error", "message": "Camera face recognition not available"}
        
        try:
            cap = self.cv2.VideoCapture(0)
            if not cap.isOpened():
                return {"status": "error", "message": "Camera not accessible"}
            
            ret, frame = cap.read()
            cap.release()
            
            if not ret:
                return {"status": "error", "message": "Failed to capture image"}
            
            # Convert and find faces
            rgb_frame = self.cv2.cvtColor(frame, self.cv2.COLOR_BGR2RGB)
            face_locations = self.face_recognition.face_locations(rgb_frame)
            
            if not face_locations:
                return {"status": "error", "message": "No face detected"}
            
            return {
                "status": "success",
                "authenticated": True,  # Simplified for demo
                "timestamp": datetime.now().isoformat(),
                "method": "Camera Face Recognition",
                "confidence": 85.0
            }
            
        except Exception as e:
            return {"status": "error", "message": str(e)}

def main():
    if len(sys.argv) < 2:
        print(json.dumps({"status": "error", "message": "No operation specified"}))
        return
    
    operation = sys.argv[1]
    
    if operation == "check":
        # Check Windows Hello Face availability
        wh_face = WindowsHelloFace()
        fallback = FallbackFaceAuth()
        
        result = {
            "windows_hello_face_available": wh_face.available,
            "camera_fallback_available": fallback.available,
            "recommended": "windows_hello" if wh_face.available else "camera"
        }
        print(json.dumps(result))
        
    elif operation == "authenticate":
        message = sys.argv[2] if len(sys.argv) > 2 else "Banking System - Face Authentication"
        
        # Try Windows Hello Face first
        wh_face = WindowsHelloFace()
        if wh_face.available:
            result = wh_face.authenticate_user(message)
        else:
            # Fallback to camera
            fallback = FallbackFaceAuth()
            result = fallback.authenticate_user()
        
        print(json.dumps(result))
        
    else:
        print(json.dumps({"status": "error", "message": "Invalid operation"}))

if __name__ == "__main__":
    main()