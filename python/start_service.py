#!/usr/bin/env python3
"""
Service startup script with dependency checking
"""

import sys
import subprocess
import importlib.util

def check_dependencies():
    """Check if required packages are installed"""
    required_packages = {
        'cv2': 'opencv-python', 
        'numpy': 'numpy',
        'face_recognition': 'face-recognition',
        'dlib': 'dlib'
    }
    
    missing = []
    for module, package in required_packages.items():
        if importlib.util.find_spec(module) is None:
            missing.append(package)
    
    if missing:
        print("❌ Missing required packages:")
        for package in missing:
            print(f"   pip install {package}")
        print("\nInstall missing packages and try again.")
        return False
    
    print("✅ All dependencies satisfied")
    return True

def start_service():
    """Start the biometric verification service"""
    if not check_dependencies():
        sys.exit(1)
    
    print("🚀 Starting Secure Biometric Verification Service...")
    print("📍 Service will run on http://127.0.0.1:5000")
    print("🔒 SECURITY: Raw biometric data deleted after processing")
    print("📝 Only encrypted templates and results are retained")
    print("\nPress Ctrl+C to stop the service\n")
    
    try:
        from biometric_service import app
        app.run(host='127.0.0.1', port=5000, debug=False)
    except KeyboardInterrupt:
        print("\n🛑 Service stopped by user")
    except Exception as e:
        print(f"❌ Service error: {e}")
        sys.exit(1)

if __name__ == '__main__':
    start_service()