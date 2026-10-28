#!/bin/bash
cd "$(dirname "$0")"
./start-qpos.sh
echo ""
read -r -p "Press Enter to close…"
