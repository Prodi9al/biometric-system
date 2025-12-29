@echo off
title Biometric Service Launcher
echo Starting Biometric Verification Service...
echo.
cd /d "%~dp0"

:: Check for Python
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo Python is not installed or not in PATH.
    pause
    exit /b
)

:: Install dependencies if missing (quick check)
pip install flask opencv-python face_recognition numpy python-dotenv mysql-connector-python >nul 2>&1

:: Run Service
echo Service is starting on port 5000...
echo DO NOT CLOSE THIS WINDOW
echo.
"python/venv/Scripts/python.exe" "python/biometric_service.py"
pause
