@echo off
echo Installing Windows Hello Face dependencies...
echo.

REM Check if Python is available
python --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Python is not installed or not in PATH
    echo Please install Python 3.10+ first
    pause
    exit /b 1
)

echo Installing Windows Hello dependencies...
pip install pywin32==306
pip install winrt==1.0.21215.1
pip install winrt-Windows.Security.Credentials==1.0.0b0
pip install winrt-Windows.Security.Credentials.UI==1.0.0b0

echo.
echo Testing Windows Hello Face availability...
python windows_hello_face.py check

echo.
echo Installation complete!
echo.
echo If Windows Hello Face is not available, the system will automatically
echo fall back to camera-based face recognition.
echo.
pause