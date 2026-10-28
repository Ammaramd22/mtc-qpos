@echo off
REM ============================================================
REM Example ionCube encode step — EDIT PATHS after installing encoder
REM Run on Windows build PC that has ionCube Encoder installed.
REM ============================================================
setlocal

set "ENCODER=C:\Program Files\ionCube Encoder PHP 8.3\ioncube_encoder.exe"
set "SRC=%~dp0..\..\QPOS-Offline-Release\LATEST\app"
set "OUT=%~dp0..\..\QPOS-Offline-Release\LATEST\app_encoded"

if not exist "%ENCODER%" (
  echo ionCube Encoder not found at:
  echo   %ENCODER%
  echo Install encoder and edit this bat file path.
  pause
  exit /b 1
)

if not exist "%SRC%\artisan" (
  echo Source app not found. Build release first, then set SRC= to that app folder.
  pause
  exit /b 1
)

echo Encoding %SRC% -> %OUT%
"%ENCODER%" "%SRC%" -o "%OUT%" --ignore "vendor" --ignore "storage" --ignore "node_modules" --allow-reflection-all

echo Done. Test encoded app on Laragon, then zip for customer.
pause
endlocal
