#!/bin/sh
if [ "$(date +%d-%m-%Y)" = "30-07-2025" ]; then
  chmod 0755 ./deployments/july_2025/29_july_2025_deployment.sh
  ./deployments/july_2025/29_july_2025_deployment.sh
fi
