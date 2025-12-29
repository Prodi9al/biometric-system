
import os
import sys

print("Testing Python Invariants...")

# Test 1: Short Secret
os.environ['SHARED_SECRET'] = 'short'

try:
    # Importing the module should trigger the top-level check
    import python.biometric_service
    print("FAIL: Did not raise ValueError on short secret")
    sys.exit(1)
except ValueError as e:
    if "CRITICAL" in str(e) and "too short" in str(e):
         print("PASS: Raised CRITICAL on short secret")
    else:
         print(f"FAIL: Wrong exception: {e}")
         sys.exit(1)
except Exception as e:
    print(f"FAIL: Unexpected exception: {e}")
    sys.exit(1)
