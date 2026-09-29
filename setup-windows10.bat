@echo off
setlocal EnableExtensions
cd /d "%~dp0"

if not exist artisan (
    echo Laravel's artisan file was not found in:
    echo %~dp0
    pause
    exit /b 1
)

where winget >nul 2>&1
if errorlevel 1 (
    echo WinGet is missing. Install Microsoft's App Installer from the Microsoft Store,
    echo then run this setup again. An internet connection is also required.
    pause
    exit /b 1
)

php -r "exit(PHP_VERSION_ID >= 80200 && extension_loaded('pdo_sqlite') && extension_loaded('fileinfo') ? 0 : 1)" >nul 2>&1
if errorlevel 1 (
    echo Installing PHP 8.3...
    winget install --id PHP.PHP.8.3 --exact --accept-package-agreements --accept-source-agreements
    if errorlevel 1 goto failed
)

if exist "%ProgramFiles%\nodejs\node.exe" set "NODE_BIN_PATH=%ProgramFiles%\nodejs"
if not defined NODE_BIN_PATH if exist "%LOCALAPPDATA%\Programs\nodejs\node.exe" set "NODE_BIN_PATH=%LOCALAPPDATA%\Programs\nodejs"
if not defined NODE_BIN_PATH (
    echo Installing Node.js LTS...
    winget install --id OpenJS.NodeJS.LTS --exact --accept-package-agreements --accept-source-agreements
    if errorlevel 1 goto failed
)

for /f "usebackq delims=" %%P in (`powershell -NoProfile -Command "$u=[Environment]::GetEnvironmentVariable('Path','User'); $m=[Environment]::GetEnvironmentVariable('Path','Machine'); [Environment]::SetEnvironmentVariable('Path',($u+';'+$m),'Process'); $env:Path"`) do set "PATH=%%P"

for /f "usebackq delims=" %%P in (`powershell -NoProfile -Command "$p=Get-ChildItem (Join-Path $env:LOCALAPPDATA 'Microsoft\WinGet\Packages\PHP.PHP.8.3*') -Filter php.exe -Recurse -ErrorAction SilentlyContinue | Select-Object -First 1; if($p){$p.DirectoryName}"`) do set "WINGET_PHP_PATH=%%P"
if defined WINGET_PHP_PATH set "PATH=%WINGET_PHP_PATH%;%PATH%"

if exist "%ProgramFiles%\nodejs\node.exe" set "NODE_BIN_PATH=%ProgramFiles%\nodejs"
if not defined NODE_BIN_PATH if exist "%LOCALAPPDATA%\Programs\nodejs\node.exe" set "NODE_BIN_PATH=%LOCALAPPDATA%\Programs\nodejs"
if not defined NODE_BIN_PATH (
    echo Node.js was installed but its executable was not found. Close this window and run setup-windows10.bat again.
    pause
    exit /b 1
)
set "PATH=%NODE_BIN_PATH%;%PATH%"

for /f "delims=" %%P in ('where.exe php 2^>nul') do if not defined PHP_EXE set "PHP_EXE=%%P"
if not defined PHP_EXE (
    echo PHP was not found after installation. Close this window and run setup-windows10.bat again.
    pause
    exit /b 1
)
for %%P in ("%PHP_EXE%") do set "PHP_BIN_PATH=%%~dpP"

powershell -NoProfile -ExecutionPolicy Bypass -Command "$ini=Join-Path '%PHP_BIN_PATH%' 'php.ini'; if(!(Test-Path $ini)){Copy-Item (Join-Path '%PHP_BIN_PATH%' 'php.ini-production') $ini}; $text=Get-Content $ini -Raw; if($text -notmatch '(?m)^\s*extension\s*=\s*fileinfo\s*$'){$text=[regex]::Replace($text,'(?m)^\s*;extension=fileinfo\s*$','extension=fileinfo'); Set-Content -Path $ini -Value $text -Encoding ASCII -NoNewline}"
if errorlevel 1 goto failed

where php >nul 2>&1
if errorlevel 1 (
    echo PHP was installed but is not available in this console. Close this window and run setup-windows10.bat again.
    pause
    exit /b 1
)

php -r "exit(PHP_VERSION_ID >= 80200 && extension_loaded('pdo_sqlite') && extension_loaded('fileinfo') ? 0 : 1)"
if errorlevel 1 (
    echo PHP 8.2 or newer with the pdo_sqlite and fileinfo extensions is required.
    echo The detected PHP executable is:
    where php
    pause
    exit /b 1
)

node -e "const [major, minor] = process.versions.node.split('.').map(Number); process.exit(major > 22 || (major === 22 && minor >= 12) || (major === 20 && minor >= 19) ? 0 : 1)" >nul 2>&1
if errorlevel 1 (
    echo Node.js 20.19 or newer, or 22.12 or newer, is required to build frontend assets.
    pause
    exit /b 1
)

if not exist "%NODE_BIN_PATH%\npm.cmd" (
    echo npm was not found beside Node.js at %NODE_BIN_PATH%.
    pause
    exit /b 1
)

if not exist .env copy .env.example .env
if not exist database\database.sqlite type nul > database\database.sqlite

if not exist "%LOCALAPPDATA%\ETIVACSILOG" mkdir "%LOCALAPPDATA%\ETIVACSILOG"
if not exist "%LOCALAPPDATA%\ETIVACSILOG\composer.phar" (
    echo Downloading Composer...
    powershell -NoProfile -ExecutionPolicy Bypass -Command "Invoke-WebRequest -Uri 'https://getcomposer.org/composer-stable.phar' -OutFile (Join-Path $env:LOCALAPPDATA 'ETIVACSILOG\composer.phar')"
    if errorlevel 1 goto failed
)

findstr /R /C:"^APP_KEY=." .env >nul
if errorlevel 1 (
    php artisan key:generate --force
    if errorlevel 1 goto failed
)

echo Installing PHP dependencies...
php "%LOCALAPPDATA%\ETIVACSILOG\composer.phar" install --no-interaction --prefer-dist --optimize-autoloader
if errorlevel 1 goto failed

echo Installing frontend dependencies and building assets...
npm install
if errorlevel 1 goto failed
npm run build
if errorlevel 1 goto failed

echo Preparing the SQLite database...
php artisan migrate --seed --force
if errorlevel 1 goto failed

echo.
echo Setup is complete. Starting ETIVACSILOG POS...
call "%~dp0start-pos.bat"
exit /b %errorlevel%

:failed
echo.
echo Setup stopped because a command failed. Review the message above, fix the issue, and run this script again.
pause
exit /b 1