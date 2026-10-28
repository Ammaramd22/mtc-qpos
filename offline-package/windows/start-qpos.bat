@echo off
REM ============================================================
REM  QPOS Offline — Start (Windows)
REM  Edit the paths below to match this PC (Laragon / XAMPP).
REM ============================================================
setlocal EnableExtensions

REM --- CONFIG (change these on each customer PC) ---
set "LARAGON_ROOT=C:\laragon"
set "QPOS_URL=http://127.0.0.1/qpos/public"
REM If using php artisan serve instead of Apache, set USE_ARTISAN=1
set "USE_ARTISAN=0"
set "QPOS_APP=%~dp0..\app"
set "ARTISAN_PORT=8000"

title QPOS Offline
echo.
echo  Starting QPOS Offline...
echo.

REM Start Laragon services if present
if exist "%LARAGON_ROOT%\laragon.exe" (
  echo  Starting Laragon services...
  start "" "%LARAGON_ROOT%\laragon.exe"
  timeout /t 4 /nobreak >nul
) else if exist "C:\xampp\xampp-control.exe" (
  echo  Laragon not found — start Apache + MySQL in XAMPP Control Panel.
  start "" "C:\xampp\xampp-control.exe"
  timeout /t 3 /nobreak >nul
) else (
  echo  WARNING: Laragon/XAMPP not found. Start MySQL yourself.
)

if "%USE_ARTISAN%"=="1" (
  if not exist "%QPOS_APP%\artisan" (
    echo  ERROR: app not found at %QPOS_APP%
    pause
    exit /b 1
  )
  echo  Starting Laravel on port %ARTISAN_PORT%...
  start "QPOS-PHP" cmd /c "cd /d \"%QPOS_APP%\" && php artisan serve --host=127.0.0.1 --port=%ARTISAN_PORT%"
  set "QPOS_URL=http://127.0.0.1:%ARTISAN_PORT%"
  timeout /t 2 /nobreak >nul
)

echo  Opening POS: %QPOS_URL%
start "" "%QPOS_URL%/pos/create"
timeout /t 1 /nobreak >nul
start "" "%QPOS_URL%"

echo.
echo  QPOS started. Keep this window open if using Artisan mode.
echo  For Apache/Laragon mode you can close this window.
echo.
endlocal
