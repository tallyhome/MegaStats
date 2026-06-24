#!/bin/bash
# Archive de distribution (sans .git, storage, dev).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
VERSION="$(grep -oP "'version'\s*=>\s*'\K[^']+" "$ROOT/config/app.php" | head -1)"
VERSION="${VERSION:-2.5.0}"
OUT="${1:-/tmp/megastats-whm-${VERSION}.tar.gz}"

tar -czf "$OUT" \
    --exclude='.git' \
    --exclude='storage/logs/*' \
    --exclude='storage/metrics/*' \
    --exclude='MODERNIZATION.md' \
    -C "$(dirname "$ROOT")" \
    "$(basename "$ROOT")"

echo "OK : $OUT ($(wc -c < "$OUT") octets)"
