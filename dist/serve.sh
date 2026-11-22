#!/bin/bash
# Keeps the IT TUBE download site alive on ports 12000/12001.
while true; do
  python3 -m http.server 12000 --bind 0.0.0.0 --directory /workspace/project/dist/site > /tmp/web12000.log 2>&1
  sleep 1
done
