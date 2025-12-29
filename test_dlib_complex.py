import cv2
import numpy as np
import face_recognition

print("Testing COMPLEX cv2 -> dlib interaction...")

# 1. Create a "Mobile-like" image (853x640)
# We use random noise to ensure it's not just black optimization
raw_img = np.random.randint(0, 255, (853, 640, 3), dtype=np.uint8)
print(f"Raw Image: Shape={raw_img.shape}, Strides={raw_img.strides}")

# 2. Mimic the Service Logic EXACTLY
# Resize
resized = cv2.resize(raw_img, (640, 480), interpolation=cv2.INTER_AREA)
print(f"Resized: Shape={resized.shape}, Strides={resized.strides}")

# Convert BGRA -> RGB (simulate cv2 load)
# Since raw_img was random, let's treat it as BGR (standard cv2)
rgb_img = cv2.cvtColor(resized, cv2.COLOR_BGR2RGB)
print(f"RGB: Shape={rgb_img.shape}, Strides={rgb_img.strides}, Contiguous={rgb_img.flags['C_CONTIGUOUS']}")

# Force Contiguous (Service Logic)
if not rgb_img.flags['C_CONTIGUOUS']:
    rgb_img = np.ascontiguousarray(rgb_img)
    print("Forced contiguous.")

# Attempt Encode
try:
    print("Attempting encode...")
    encs = face_recognition.face_encodings(rgb_img)
    print(f"Success! Encodings: {len(encs)}")
except Exception as e:
    print(f"FAILURE: {e}")

# Test Grayscale Fallback logic
try:
    print("Attempting GRAY encode...")
    gray = cv2.cvtColor(rgb_img, cv2.COLOR_RGB2GRAY)
    print(f"Gray: Shape={gray.shape}, Strides={gray.strides}")
    encs = face_recognition.face_encodings(gray)
    print(f"Success! Gray Encodings: {len(encs)}")
except Exception as e:
    print(f"GRAY FAILURE: {e}")
