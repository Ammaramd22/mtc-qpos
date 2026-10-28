#!/bin/bash
cd "$(dirname "$0")"
./stop-qpos.sh
read -r -p "Press Enter to close…"
