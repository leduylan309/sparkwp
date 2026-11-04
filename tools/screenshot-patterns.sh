#!/usr/bin/env bash
set -euo pipefail
CHROME="${CHROME:-/Applications/Google Chrome.app/Contents/MacOS/Google Chrome}"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$ROOT/wordpress/wp-content/themes/sparkvn/assets/patterns"
BASE="${BASE:-http://localhost:8080}"
mkdir -p "$OUT"
shopt -s nullglob
for f in "$ROOT/wordpress/wp-content/themes/sparkvn/patterns/"*.php; do
  slug="$(basename "$f" .php)"
  echo "shot $slug"
  "$CHROME" --headless=new --disable-gpu --hide-scrollbars --window-size=1180,800 \
    --virtual-time-budget=5000 \
    --screenshot="$OUT/${slug}.png" \
    "${BASE}/?sparkvn_pattern=${slug}" >/dev/null
done
echo "done $(ls "$OUT" | wc -l) files"
