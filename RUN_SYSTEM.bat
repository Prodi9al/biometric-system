@echo off
setlocal
title Biometric Banking System - Launcher

echo ==================================================
echo      SECURE BIOMETRIC BANKING SYSTEM (v2.0)     
echo ==================================================
echo.

:: 1. Check for .env Configuration
if not exist .env (
    echo [ERROR] Configuration file .env not found!
    echo Running setup script...
    call setup-env.bat
    echo.
    echo Please edit .env with your specific secrets if needed.
    echo.
    pause
)

:: 2. Check Python Environment
echo [INFO] Checking Python environment...
if not exist "python\venv\Scripts\activate.bat" (
    echo [WARNING] Python virtual environment not found in python\venv
    echo Only proceed if you have installed dependencies globally.
    echo.
)

:: 3. Start Python Service
echo.
echo [1/3] Starting Biometric Service (Background Window)...
start "Biometric Service" cmd /k "cd python && if exist venv\Scripts\activate.bat (call venv\Scripts\activate.bat) && python biometric_service.py"

:: 4. Instructions for XAMPP
echo.
echo [2/3] Ensure XAMPP is Running:
echo    - Open XAMPP Control Panel
echo    - Start Apache and MySQL
echo.

:: 5. Instructions for Ngrok
echo.
echo [3/3] To Host via Ngrok (Run in new terminal):
echo    ngrok http 80
echo.
echo    Then access your system via the ngrok URL:
echo    https://[your-ngrok-id].ngrok-free.app/biometric system/public/atm-login-sequential.php
echo.

echo ==================================================
echo System Launch Sequence Initiated.
echo You may now open your browser.
echo ==================================================
pause
