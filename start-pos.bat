@echo off
setlocal
cd /d "%~dp0"

if not exist artisan (
    echo Laravel's artisan file was not found in:
    echo %~dp0
    pause
    exit /b 1
)

if not exist .env (
    echo The app has not been installed yet. Run setup-windows10.bat first.
    pause
    exit /b 1
)

where php >nul 2>&1
if errorlevel 1 (
    echo PHP was not found on PATH. Install PHP or add it to PATH, then try again.
    pause
    exit /b 1
)

if not exist vendor\autoload.php (
    echo Laravel dependencies are missing. Run setup-windows10.bat first.
    pause
    exit /b 1
)

for /f "usebackq delims=" %%I in (`powershell -NoProfile -Command "(Get-NetIPConfiguration | Where-Object { $_.IPv4DefaultGateway } | Select-Object -First 1).IPv4Address.IPAddress"`) do set "LAN_IP=%%I"
if not defined LAN_IP set "LAN_IP=127.0.0.1"

echo Starting ETIVACSILOG POS...
echo POS address: http://%LAN_IP%:8001/posetivacsilogpos
echo On this PC, use http://127.0.0.1:8001/posetivacsilogpos if needed.
echo Allow PHP through Windows Firewall on Private networks if prompted.
echo Keep both console windows open. Press Ctrl+C in each to stop the app and scheduler.
echo.
start "" "http://%LAN_IP%:8001/posetivacsilogpos"
start "ETIVACSILOG Scheduler" /D "%~dp0" cmd /k "php artisan schedule:work"
php artisan serve --host=0.0.0.0 --port=8001

echo.
echo The Laravel server has stopped.
pause
