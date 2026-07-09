@echo off
setlocal enabledelayedexpansion

TITLE DRRM Compliance System - Auto Start
echo ==========================================
echo    DRRM COMPLIANCE SYSTEM AUTO-START
echo ==========================================
echo.
color 0a

:: Detect XAMPP location
set XAMPP_PATH=C:\xampp
if not exist "%XAMPP_PATH%\xampp_start.exe" (
    echo ERROR: XAMPP not found at %XAMPP_PATH%
    echo Please install XAMPP or adjust the XAMPP_PATH variable.
    pause
    exit /b 1
)

:: 1. CLEANUP: Kill previous system service instances (NOT browsers)
echo [1/5] Cleaning up previous service sessions...
taskkill /f /im httpd.exe /t >nul 2>&1
taskkill /f /im mysqld.exe /t >nul 2>&1
:: taskkill /f /im php.exe /t >nul 2>&1   <--- CHANGED: Commented out
taskkill /f /im xampp-control.exe /t >nul 2>&1

:: 2. START XAMPP SERVICES
echo [2/5] Starting XAMPP services (Apache ^& MySQL)...
cd /d "%XAMPP_PATH%"
start /min "" "xampp_start.exe"

:: 3. WAIT FOR SERVICES: Critical for stability
echo [3/5] Waiting for services to be ready...
timeout /t 3 /nobreak >nul
set retry_count=0
:check_http
if %retry_count% geq 10 (
    echo WARNING: HTTP service may not be responding, proceeding anyway...
    goto :skip_check
)
timeout /t 1 /nobreak >nul
set /a retry_count+=1
goto :check_http

:skip_check
echo Services ready!

:: 4. START LARAVEL SERVER - CHANGED: host to 127.0.0.1
echo [4/5] Starting Laravel Development Server...
cd /d "c:\xampp\htdocs\drrmcompliance"
start /min "DRRM Server" php artisan serve --host=127.0.0.1 --port=8000

:: 5. WAIT FOR SERVER
echo Waiting for Laravel server to start...
set server_wait_count=0
:wait_server
timeout /t 1 /nobreak >nul
netstat -ano | findstr :8000 >nul
if errorlevel 1 (
    set /a server_wait_count+=1
    if !server_wait_count! geq 20 goto :server_timeout
    goto :wait_server
)
echo Server is up!
goto :open_browser

:server_timeout
echo WARNING: Laravel server is taking too long to respond.

:open_browser
:: 6. OPEN SYSTEM URL
echo [5/5] Opening DRRM Compliance System Login in browser...
set APP_URL=http://127.0.0.1:8000/
echo.
echo Launching: %APP_URL%
echo.

:: Try to use default browser
start "" "%APP_URL%"

:: 5. COMPLETION
echo [5/5] System started successfully!
echo.
echo ==========================================
echo    System is running and ready to use
echo ==========================================
echo.
echo NOTES:
echo - XAMPP services are running in the background
echo - The system is accessible at: %APP_URL%
echo - To stop the system, close XAMPP Control Panel
echo.
timeout /t 3 /nobreak >nul

exit /b 0