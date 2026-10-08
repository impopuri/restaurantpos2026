@echo off
setlocal
cd /d "%~dp0"

if not exist artisan (
    echo Laravel's artisan file was not found in:
    echo %~dp0
    pause
    exit /b 1
)

where php >nul 2>&1
if errorlevel 1 (
    echo PHP was not found on PATH. Install PHP or add it to PATH, then try again.
    pause
    exit /b 1
)

echo Starting ETIVACSILOG POS...
echo When the server is ready, open http://127.0.0.1:8001 in your browser.
echo From another device on this network, use http://<this-PC's-LAN-IP>:8001.
echo Find this PC's LAN IP with ipconfig. Allow PHP through Windows Firewall if prompted.
echo Keep both console windows open. Press Ctrl+C in each to stop the app and scheduler.
echo.
start "ETIVACSILOG Scheduler" /D "%~dp0" cmd /k "php artisan schedule:work"
php artisan serve --host=0.0.0.0 --port=8001

echo.
echo The Laravel server has stopped.
pause
