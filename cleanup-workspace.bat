@echo off
echo Cleaning up workspace...

REM Remove Python cache
for /d /r . %%d in (__pycache__) do @if exist "%%d" rd /s /q "%%d"

REM Remove logs
if exist "*.log" del /q "*.log"

REM Remove temporary test files
if exist "test_db.php" del /q "test_db.php"
if exist "api\debug_data.php" del /q "api\debug_data.php"

echo Cleanup complete!
pause
