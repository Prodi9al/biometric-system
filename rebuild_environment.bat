@echo off
title Biometric Environment Rebuilder
echo ==================================================
echo      REBUILDING PYTHON ENVIRONMENT
echo ==================================================
echo.

cd /d "%~dp0"

:: 1. Cleanup
if exist "python\venv" (
    echo [1/4] Removing corrupted environment...
    rmdir /s /q "python\venv"
)

:: 2. Create VENV
echo [2/4] Creating fresh Virtual Environment (Inheriting Global Packages)...
:: CRITICAL: Use --system-site-packages to use the pre-installed dlib
:: This avoids needing Visual Studio to compile dlib from source
python -m venv python\venv --system-site-packages
if %errorlevel% neq 0 (
    echo [ERROR] Failed to create venv. Is Python installed?
    pause
    exit /b
)

:: 3. Upgrade Pip
echo [3/4] Upgrading pip...
"python\venv\Scripts\python.exe" -m pip install --upgrade pip

:: 4. Install Dependencies
echo [4/4] Installing dependencies...
:: Note: We omit dlib here because we expect to inherit it from global system
"python\venv\Scripts\pip.exe" install flask opencv-python face_recognition numpy python-dotenv mysql-connector-python

echo.
echo ==================================================
echo [SUCCESS] Environment Rebuilt!
echo You can now close this window and run RUN_SYSTEM.bat
echo ==================================================
pause
