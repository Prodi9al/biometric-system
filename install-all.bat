@echo off
title GCTU Biometric ATM System - One-Click Installer
color 0f
cls

echo ===============================================================================
echo            GCTU BIOMETRIC ATM SYSTEM - EASY INSTALLER (2024)
echo ===============================================================================
echo.
echo This script will help you set up the entire system automatically.
echo.
echo PREREQUISITES:
echo 1. XAMPP must be installed (https://www.apachefriends.org/)
echo 2. Python 3.10+ must be installed (https://www.python.org/downloads/)
echo.
pause

echo.
echo [STEP 0/4] Checking System Requirements...

echo.
echo [STEP 0/4] Checking System Requirements...

set PYTHON_CMD=python

REM Attempt 1: Check default python command
python -c "import sys; exit(0) if sys.version_info.major==3 and sys.version_info.minor==10 and sys.maxsize > 2**32 else exit(1)" 2>nul
if %errorlevel% equ 0 (
    echo [OK] Valid Python 3.10 detected via 'python' command.
    goto :check_done
)

REM Attempt 2: Check py launcher for 3.10
py -3.10 -c "import sys; exit(0) if sys.version_info.major==3 and sys.version_info.minor==10 and sys.maxsize > 2**32 else exit(1)" 2>nul
if %errorlevel% equ 0 (
    echo [OK] Valid Python 3.10 detected via 'py -3.10' launcher.
    set PYTHON_CMD=py -3.10
    goto :check_done
)

REM If both failed, show error
echo.
echo [ERROR] CRITICAL REQUIREMENT MISSING!
echo.
echo We could not find a valid Python 3.10 (64-bit) installation in your PATH.
echo.
echo DIAGNOSTIC INFO:
echo 'python' command: 
python --version
echo.
echo POSSIBLE FIXES:
echo 1. Did you check "Add Python to PATH" during installation?
echo    (If not, please reinstall Python and CHECK THAT BOX)
echo.
echo 2. Did you restart your computer/terminal after installing?
echo    (Environment variables only update after a restart)
echo.
echo Please install Python 3.10.11 (64-bit) properly and try again.
pause
exit

:check_done
echo.
echo [STEP 1/4] Setting up Security Configuration...
if not exist .env (
    call setup-env.bat
) else (
    echo .env file already exists. Skipping...
)

echo.
echo [STEP 2/4] Installing Python Dependencies...
echo This may take a few minutes...
echo Installing dlib from local wheel...
%PYTHON_CMD% -m pip install "python/dlib-19.22.99-cp310-cp310-win_amd64.whl"
%PYTHON_CMD% -m pip install -r python/requirements.txt
if %errorlevel% neq 0 (
    echo.
    echo WARNING: some packages failed to install.
    echo Please check DOWNLOADS_REQUIRED.txt for manual instructions.
    pause
)

echo.
echo [STEP 3/4] Initializing Database...
echo Please ensure XAMPP (Apache & MySQL) is RUNNING.
echo Opening database setup in your browser...
start http://localhost/biometric%%20system/create_tables.php
echo.
pause

echo.
echo [STEP 4/4] Starting Biometric Service...
echo The secure service will launch in a new window.
echo KEEP THAT WINDOW OPEN!
start start-biometric-service.bat

echo.
echo ===============================================================================
echo                     INSTALLATION COMPLETE!
echo ===============================================================================
echo.
echo Opening the Biometric ATM System Login...
timeout /t 3
start http://localhost/biometric%%20system/public/login.php

echo.
echo You can close this installer window now.
pause
exit
