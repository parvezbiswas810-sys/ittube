#!/usr/bin/env bash
# Serves the IT TUBE downloads on ports 12000/12001 and restarts them if they die.
for port in 12000 12001; do
  pgrep -f "http.server $port" > /dev/null || \
    setsid nohup python3 -m http.server "$port" --bind 0.0.0.0 \
      --directory /workspace/project/dist/site \
      >> "/tmp/web$port.log" 2>&1 < /dev/null &
done
