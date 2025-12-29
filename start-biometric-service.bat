@echo off
echo Starting Secure Biometric Verification Service...
echo SECURITY NOTICE: Raw biometric data is deleted immediately after processing
echo FAIL-SECURE NOTICE: System defaults to authentication failure for any error
echo.
cd /d "%~dp0python"
echo Checking Python dependencies...
python -c "import cv2, numpy, face_recognition" 2>nul
if errorlevel 1 (
    echo ERROR: Required Python packages not installed
    echo Please run: install_requirements.bat
    pause
    exit /b 1
)
echo Dependencies OK. Starting service...
python biometric_service.py
pause