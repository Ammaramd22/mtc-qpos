@echo off
REM ============================================================
REM  QPOS Offline — MySQL backup
REM  Requires mysqldump on PATH (Laragon/XAMPP bin).
REM ============================================================
setlocal EnableExtensions

set "DB_NAME=qpos_tyre"
set "DB_USER=root"
set "DB_PASS="
set "MYSQLDUMP=C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqldump.exe"
REM Fallback XAMPP:
if not exist "%MYSQLDUMP%" set "MYSQLDUMP=C:\xampp\mysql\bin\mysqldump.exe"

set "BACKUP_DIR=%~dp0..\data\backups"
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

for /f %%i in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMdd_HHmmss"') do set "STAMP=%%i"
set "OUT=%BACKUP_DIR%\qpos_%STAMP%.sql"

echo Backing up %DB_NAME% to:
echo   %OUT%

if "%DB_PASS%"=="" (
  "%MYSQLDUMP%" -u%DB_USER% %DB_NAME% > "%OUT%"
) else (
  "%MYSQLDUMP%" -u%DB_USER% -p%DB_PASS% %DB_NAME% > "%OUT%"
)

if errorlevel 1 (
  echo BACKUP FAILED. Check mysqldump path and DB name/password.
  pause
  exit /b 1
)

echo Backup OK.
pause
endlocal
