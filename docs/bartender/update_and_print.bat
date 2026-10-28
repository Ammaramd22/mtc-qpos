@echo off
REM ============================================================
REM  QPOS → BarTender label print
REM  Folder layout (IMPORTANT):
REM
REM    C:\BarTender\
REM      update_and_print.bat     ← this file
REM      ProductLabel.btw         ← your BarTender label design
REM      barcode_labels.csv       ← auto-downloaded from QPOS
REM
REM  QPOS Settings → Barcode / Bartender:
REM    - Enable Bartender CSV Export = ON
REM    - Save CSV to fixed server URL = ON
REM    - Batch launcher URL =
REM        file:///C:/BarTender/update_and_print.bat
REM ============================================================

setlocal EnableExtensions

REM ---------- EDIT THESE 3 LINES ----------
set "QPOS_CSV_URL=https://proway-qpos.avenque.io/exports/barcode_labels.csv?t=1"
set "LABEL_DIR=C:\BarTender"
set "BTW_NAME=ProductLabel.btw"
REM ----------------------------------------

set "CSV_FILE=%LABEL_DIR%\barcode_labels.csv"
set "BTW_FILE=%LABEL_DIR%\%BTW_NAME%"

REM Find BarTender (edit if yours is elsewhere)
set "BARTEND="
if exist "C:\Program Files\Seagull\BarTender Suite\bartend.exe" set "BARTEND=C:\Program Files\Seagull\BarTender Suite\bartend.exe"
if exist "C:\Program Files (x86)\Seagull\BarTender Suite\bartend.exe" set "BARTEND=C:\Program Files (x86)\Seagull\BarTender Suite\bartend.exe"
if exist "C:\Program Files\Seagull\BarTender 2022\bartend.exe" set "BARTEND=C:\Program Files\Seagull\BarTender 2022\bartend.exe"
if exist "C:\Program Files\Seagull\BarTender 2021\bartend.exe" set "BARTEND=C:\Program Files\Seagull\BarTender 2021\bartend.exe"
if exist "C:\Program Files\Seagull\BarTender 2019\bartend.exe" set "BARTEND=C:\Program Files\Seagull\BarTender 2019\bartend.exe"
if exist "C:\Program Files\Seagull\BarTender 2016\bartend.exe" set "BARTEND=C:\Program Files\Seagull\BarTender 2016\bartend.exe"

if not exist "%LABEL_DIR%" mkdir "%LABEL_DIR%"

echo.
echo === QPOS BarTender Print ===
echo CSV URL : %QPOS_CSV_URL%
echo Save to : %CSV_FILE%
echo Label   : %BTW_FILE%
echo.

REM --- Download latest CSV from QPOS ---
echo Downloading label data...
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "try { Invoke-WebRequest -Uri '%QPOS_CSV_URL%' -OutFile '%CSV_FILE%' -UseBasicParsing; exit 0 } catch { Write-Host $_; exit 1 }"

if errorlevel 1 (
  echo.
  echo FAILED to download CSV from QPOS.
  echo 1^) Open Print Labels in QPOS and click Export Label Data first
  echo 2^) Check QPOS_CSV_URL in this BAT file
  echo 3^) Confirm file exists: %QPOS_CSV_URL%
  pause
  exit /b 1
)

if not exist "%CSV_FILE%" (
  echo CSV file missing: %CSV_FILE%
  pause
  exit /b 1
)

for %%A in ("%CSV_FILE%") do echo CSV size: %%~zA bytes

if not exist "%BTW_FILE%" (
  echo.
  echo BTW label file not found:
  echo   %BTW_FILE%
  echo.
  echo Create ProductLabel.btw in BarTender and save it here:
  echo   %LABEL_DIR%\
  echo Then connect its database to:
  echo   %CSV_FILE%
  pause
  exit /b 1
)

if "%BARTEND%"=="" (
  echo.
  echo bartend.exe not found.
  echo Install BarTender, or set BARTEND path at the top of this BAT.
  pause
  exit /b 1
)

echo.
echo Printing with BarTender...
echo Engine: %BARTEND%

REM /F = format (btw)   /D = data (csv)   /P = print   /X = exit when done
"%BARTEND%" /F="%BTW_FILE%" /D="%CSV_FILE%" /P /X

if errorlevel 1 (
  echo.
  echo BarTender reported an error.
  echo Open ProductLabel.btw manually and check database path = barcode_labels.csv
  pause
  exit /b 1
)

echo.
echo Done.
timeout /t 3 >nul
exit /b 0
