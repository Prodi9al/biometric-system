@echo off
echo Installing Python requirements for Biometric ATM System...
echo.

REM Check if Python is installed
python --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Python is not installed or not in PATH
    echo Please install Python 3.8+ from https://python.org
    pause
    exit /b 1
)

REM Upgrade pip
echo Upgrading pip...
python -m pip install --upgrade pip

REM Install dlib from local wheel (Critical for Windows)
echo Installing dlib from local wheel...
pip install "dlib-19.22.99-cp310-cp310-win_amd64.whl"

REM Install requirements
echo Installing biometric processing packages...
pip install -r requirements.txt

REM Test installations
echo.
echo Testing installations...
python -c "import cv2; print('OpenCV:', cv2.__version__)"
python -c "import face_recognition; print('Face Recognition: OK')"
python -c "import numpy; print('NumPy:', numpy.__version__)"

echo.
echo Installation complete!
echo You can now use the biometric features.
pause