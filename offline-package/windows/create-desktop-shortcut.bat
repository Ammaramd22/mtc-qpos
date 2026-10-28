@echo off
setlocal
set "TARGET=%~dp0start-qpos.bat"
set "DESKTOP=%USERPROFILE%\Desktop\QPOS Offline.lnk"
set "ICON=%~dp0..\electron-wrapper\icon.ico"
if not exist "%ICON%" set "ICON=%~dp0..\assets\qpos-icon-256.png"

powershell -NoProfile -Command ^
  "$s=(New-Object -ComObject WScript.Shell).CreateShortcut('%DESKTOP%');" ^
  "$s.TargetPath='%TARGET%';" ^
  "$s.WorkingDirectory='%~dp0';" ^
  "$s.WindowStyle=1;" ^
  "$s.Description='Start QPOS Offline POS';" ^
  "if (Test-Path '%ICON%') { $s.IconLocation='%ICON%' };" ^
  "$s.Save()"

echo Desktop shortcut created: QPOS Offline.lnk
echo Icon: %ICON%
pause
endlocal
