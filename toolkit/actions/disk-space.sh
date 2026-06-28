#!/bin/bash
set -euo pipefail

echo "=== Espace disque ==="
df -hT --exclude-type=tmpfs --exclude-type=devtmpfs 2>/dev/null || df -h
