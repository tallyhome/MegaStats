#!/bin/bash
# Normalise les scripts deployes (LF, permissions, shebang).
set -euo pipefail

normalize_file() {
    local file="$1"
    [[ -f "$file" ]] || return 0
    sed -i 's/\r$//' "$file"
    chmod 755 "$file"
    chown root:root "$file"
}

normalize_file "$1"
normalize_file "$2"
normalize_file "$3"
