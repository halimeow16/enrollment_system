@echo off
setlocal

set "PROJECT_DIR=%~dp0"
for %%I in ("%PROJECT_DIR%.") do set "PROJECT_DIR=%%~fI"
set "XAMPP_DIR=C:\xampp"
set "PHP_EXE=php"
set "MYSQL_DEFAULTS_FILE=%XAMPP_DIR%\mysql\bin\my.ini"
set "MYSQL_RUNTIME_DEFAULTS=%PROJECT_DIR%\storage\app\mysql-runtime-my.ini"

where php >nul 2>nul
if errorlevel 1 if exist "%XAMPP_DIR%\php\php.exe" (
    set "PHP_EXE=%XAMPP_DIR%\php\php.exe"
)

title COMTEQ Enrollment System

echo.
echo ==========================================
echo   COMTEQ Enrollment System Local Server
echo ==========================================
echo.

if not exist "%PROJECT_DIR%\artisan" (
    echo This file must stay inside the Laravel project folder.
    echo Missing: %PROJECT_DIR%\artisan
    echo.
    pause
    exit /b 1
)

if not exist "%XAMPP_DIR%" (
    echo XAMPP was not found at %XAMPP_DIR%.
    echo Update XAMPP_DIR inside this file if your XAMPP is installed elsewhere.
    echo.
    pause
    exit /b 1
)

echo Starting MySQL...

netstat -ano | findstr /R /C:":3306 .*LISTENING" >nul 2>nul
if not errorlevel 1 (
    echo MySQL is already running on port 3306.
) else if exist "%XAMPP_DIR%\mysql\bin\mysqld.exe" (
    if exist "%MYSQL_DEFAULTS_FILE%" (
        powershell -NoProfile -ExecutionPolicy Bypass -Command "(Get-Content -LiteralPath '%MYSQL_DEFAULTS_FILE%') -replace '^(\s*)key_buffer\s*=', '${1}key_buffer_size=' | Set-Content -LiteralPath '%MYSQL_RUNTIME_DEFAULTS%' -Encoding ASCII" >nul 2>nul
        if exist "%MYSQL_RUNTIME_DEFAULTS%" set "MYSQL_DEFAULTS_FILE=%MYSQL_RUNTIME_DEFAULTS%"
    )
    start "" /B "%XAMPP_DIR%\mysql\bin\mysqld.exe" --defaults-file="%MYSQL_DEFAULTS_FILE%" --standalone
) else (
    echo MySQL executable not found: %XAMPP_DIR%\mysql\bin\mysqld.exe
)

echo Waiting for services to start...
timeout /t 5 /nobreak >nul

pushd "%PROJECT_DIR%"

if errorlevel 1 (
    echo Unable to enter project folder:
    echo %PROJECT_DIR%
    echo.
    pause
    exit /b 1
)

echo.
echo Local access:
echo   http://127.0.0.1:8000
echo.
echo Phone access:
for /f %%A in ('powershell -NoProfile -Command "Get-NetIPAddress -AddressFamily IPv4 | Where-Object { $_.IPAddress -notlike '127.*' -and $_.IPAddress -notlike '169.254.*' } | Select-Object -ExpandProperty IPAddress"') do (
    echo   http://%%A:8000
)
echo.
echo Laravel command:
echo   "%PHP_EXE%" "%PROJECT_DIR%\artisan" serve --host=0.0.0.0 --port=8000
echo.
echo Keep this window open while using the system.
echo Press CTRL+C to stop Laravel.
echo.

"%PHP_EXE%" "%PROJECT_DIR%\artisan" serve --host=0.0.0.0 --port=8000

echo.
echo Laravel server stopped.
popd
pause
