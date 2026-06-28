#!/bin/bash
set -euo pipefail

echo "=== Exim / file mail ==="
if command -v exim >/dev/null 2>&1; then
    echo "Queue: $(exim -bpc 2>/dev/null || echo '?') message(s)"
    exim -bp 2>/dev/null | head -20 || true
else
    echo "Exim non trouvé."
fi
