import cv2
import numpy as np
import face_recognition
import json
import sys
import base64
from io import BytesIO
from PIL import Image

class FaceRecognitionCore:
    def __init__(self):
        self.face_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')
        
    def capture_face(self):
        """Capture face from camera and return encoded template"""
        try:
            cap = cv2.VideoCapture(0)
            if not cap.isOpened():
                return {"status": "error", "message": "Camera not accessible"}
            
            ret, frame = cap.read()
            cap.release()
            
            if not ret:
                return {"status": "error", "message": "Failed to capture image"}
            
            # Convert to RGB for face_recognition
            rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
            
            # Find face encodings
            face_locations = face_recognition.face_locations(rgb_frame)
            if not face_locations:
                return {"status": "error", "message": "No face detected"}
            
            face_encodings = face_recognition.face_encodings(rgb_frame, face_locations)
            if not face_encodings:
                return {"status": "error", "message": "Could not encode face"}
            
            # Convert encoding to base64 for storage
            encoding_bytes = face_encodings[0].tobytes()
            template = base64.b64encode(encoding_bytes).decode('utf-8')
            
            return {
                "status": "success",
                "template": template,
                "quality_score": self._calculate_quality(rgb_frame, face_locations[0])
            }
            
        except Exception as e:
            return {"status": "error", "message": str(e)}
    
    def match_faces(self, live_template, stored_template, threshold=0.6):
        """Match two face templates"""
        try:
            # Decode templates
            live_encoding = np.frombuffer(base64.b64decode(live_template), dtype=np.float64)
            stored_encoding = np.frombuffer(base64.b64decode(stored_template), dtype=np.float64)
            
            # Calculate distance
            distance = face_recognition.face_distance([stored_encoding], live_encoding)[0]
            confidence = (1 - distance) * 100
            
            is_match = distance < threshold
            
            return {
                "status": "success",
                "is_match": is_match,
                "confidence": round(confidence, 2),
                "distance": round(distance, 4)
            }
            
        except Exception as e:
            return {"status": "error", "message": str(e)}
    
    def _calculate_quality(self, image, face_location):
        """Calculate face image quality score"""
        top, right, bottom, left = face_location
        face_img = image[top:bottom, left:right]
        
        # Calculate sharpness using Laplacian variance
        gray = cv2.cvtColor(face_img, cv2.COLOR_RGB2GRAY)
        sharpness = cv2.Laplacian(gray, cv2.CV_64F).var()
        
        # Normalize to 0-100 scale
        quality = min(100, max(0, sharpness / 10))
        return round(quality, 2)

def main():
    if len(sys.argv) < 2:
        print(json.dumps({"status": "error", "message": "No command specified"}))
        return
    
    command = sys.argv[1]
    face_core = FaceRecognitionCore()
    
    if command == "capture":
        result = face_core.capture_face()
        print(json.dumps(result))
        
    elif command == "match":
        if len(sys.argv) < 4:
            print(json.dumps({"status": "error", "message": "Missing templates"}))
            return
        
        live_template = sys.argv[2]
        stored_template = sys.argv[3]
        threshold = float(sys.argv[4]) if len(sys.argv) > 4 else 0.6
        
        result = face_core.match_faces(live_template, stored_template, threshold)
        print(json.dumps(result))
        
    else:
        print(json.dumps({"status": "error", "message": "Unknown command"}))

if __name__ == "__main__":
    main()