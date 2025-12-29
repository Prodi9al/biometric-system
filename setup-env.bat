@echo off
setlocal
title Biometric System Setup

echo ==================================================
echo      BIOMETRIC SYSTEM SETUP INITIALIZER
echo ==================================================
echo.

:: 1. Check if Python is available (required for secret generation)
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Python is not installed or not in PATH.
    echo Python is required to generate secure cryptographic keys.
    pause
    exit /b 1
)

:: 2. Generate Secrets
echo [INFO] Generating secure cryptographic keys...

for /f %%i in ('python -c "import secrets; print(secrets.token_hex(32))"') do set RAND_SHARED_SECRET=%%i
for /f %%i in ('python -c "import secrets; print(secrets.token_hex(32))"') do set RAND_ENCRYPTION_KEY=%%i

:: 3. Create .env file
echo [INFO] Creating .env config file...
if exist .env (
    echo [ERROR] .env file already exists!
    echo [SECURITY] To prevent accidental secret overwrite, this script will NOT proceed.
    echo If you really want to reset, delete the .env file manually first.
    pause
    exit /b 1
)

(
echo # Database Configuration
echo DB_HOST=localhost
echo DB_USER=root
echo DB_PASS=
echo DB_NAME=biometric_atm_db
echo.
echo # Security Configuration [GENERATED]
echo # CRITICAL: Keep these secrets safe. Back them up.
echo SHARED_SECRET=%RAND_SHARED_SECRET%
echo ENCRYPTION_KEY=%RAND_ENCRYPTION_KEY%
echo.
echo # Application Settings
echo BIOMETRIC_THRESHOLD=85.0
echo MAX_LOGIN_ATTEMPTS=3
echo SESSION_TIMEOUT=1800
echo.
echo # Environment
echo APP_ENV=development
echo DEBUG=true
) > .env

echo.
echo [SUCCESS] .env file created with secure keys.
echo.
echo [WARNING] IMPORTANT:
echo 1. Edit .env to set your Database Password if needed.
echo 2. BACK UP the generated SHARED_SECRET and ENCRYPTION_KEY!
echo    If you lose them, you may lose access to encrypted data.
echo.
pause
