import numpy as np
import face_recognition

print("Testing dlib/face_recognition installation...")

# Create a standard black image (480x640 RGB)
# This mimics exactly what we are seeing in the debug logs
img = np.zeros((480, 640, 3), dtype=np.uint8)

print(f"Created image: Shape={img.shape}, Dtype={img.dtype}, Strides={img.strides}")

try:
    print("Attempting to find face locations...")
    # We expect 0 faces, but NO CRASH
    locs = face_recognition.face_locations(img)
    print(f"Success! Found {len(locs)} faces.")
    
    print("Attempting to encode faces...")
    # This might fail if no faces found, so we tolerate empty return
    # But it should NOT crash with "Unsupported image type"
    encs = face_recognition.face_encodings(img, locs)
    print("Success! Encoding complete.")

except Exception as e:
    print(f"CRITICAL FAILURE: {e}")
