# Python Biometric Setup Instructions

## Prerequisites
1. Install Python 3.8+ on your system
2. Ensure Python is in your system PATH

## Installation Steps

### 1. Install Python Dependencies
```bash
cd "c:\xampp\htdocs\biometric system\python"
pip install -r requirements.txt
```

### 2. Test Camera Access
```bash
python biometric_capture.py face
```

## Integration Points

### Current Features
- **Face Detection**: Uses OpenCV with Haar cascades
- **Quality Assessment**: Calculates image sharpness score
- **Base64 Encoding**: Converts images for database storage

### Ready for Extension
- **Fingerprint SDK**: Replace placeholder in `capture_fingerprint()`
- **Retina Scanner**: Replace placeholder in `capture_retina()`
- **Advanced Face Recognition**: Add face encoding/matching algorithms

### Usage in System
- **Enrollment**: Real camera capture button calls Python script
- **Authentication**: Can be extended for live biometric matching
- **Quality Control**: Automatic quality scoring for captured biometrics

## Extending for Production

### Face Recognition Enhancement
```python
# Add to biometric_capture.py
import face_recognition

def encode_face(self, image):
    encodings = face_recognition.face_encodings(image)
    return encodings[0] if encodings else None
```

### Fingerprint Integration
```python
# Example SDK integration
from fingerprint_sdk import FingerprintScanner

def capture_fingerprint_real(self):
    scanner = FingerprintScanner()
    template = scanner.capture()
    return template
```

## Testing
- Use "Real Camera Capture" button in enrollment
- Check browser console for detailed error messages
- Verify Python script output in command line