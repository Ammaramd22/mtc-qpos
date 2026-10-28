@echo off
REM Open POS screen only (services already running)
set "QPOS_URL=http://127.0.0.1/qpos/public"
start "" "%QPOS_URL%/pos/create"
