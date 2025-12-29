@echo off
echo Starting Ngrok Tunnel...
echo.
echo Make sure your XAMPP Apache server is running on Port 80.
echo.

REM Set the command to use (prioritize global npm install)
set NGROK_CMD=ngrok
where ngrok >nul 2>nul
if %errorlevel% neq 0 (
    set NGROK_CMD=npx ngrok
)

REM Try running ngrok
call %NGROK_CMD% http 80
if %errorlevel% equ 0 goto :end

echo.
echo -----------------------------------------------------
echo  NGROK AUTHENTICATION REQUIRED
echo -----------------------------------------------------
echo  It looks like this is your first time using Ngrok.
echo  You need to enter your Authtoken to continue.
echo.
echo  1. Go to: https://dashboard.ngrok.com/get-started/your-authtoken
echo  2. Copy your Authtoken.
echo.
set /p NGROK_TOKEN="Paste your Authtoken here: "

if "%NGROK_TOKEN%"=="" goto :error

echo.
echo Saving Authtoken...
call %NGROK_CMD% config add-authtoken %NGROK_TOKEN%

echo.
echo Retrying Connection...
call %NGROK_CMD% http 80
goto :end

:error
echo.
echo [ERROR] Ngrok setup failed.
echo Please restart this script and enter a valid token.
pause

:end
