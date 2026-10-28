@echo off
REM Stop artisan serve window if used; Laragon/XAMPP stop manually or via their UI.
setlocal
echo Stopping QPOS Artisan server (if running)...
taskkill /FI "WINDOWTITLE eq QPOS-PHP*" /T /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq QPOS-PHP" /T /F >nul 2>&1
echo Done. If using Laragon/XAMPP, stop Apache/MySQL from Laragon or XAMPP Control.
pause
endlocal
