#!/usr/bin/env bash
set -euo pipefail
CHROME="${CHROME:-/Applications/Google Chrome.app/Contents/MacOS/Google Chrome}"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$ROOT/wordpress/wp-content/themes/sparkvn/assets/blocks"
BASE="${BASE:-http://localhost:8080}"
mkdir -p "$OUT"
for slug in hero card form tabs query-programs query-posts query-prizes query-resources; do
  echo "shot block $slug"
  "$CHROME" --headless=new --disable-gpu --hide-scrollbars --window-size=1180,800 \
    --virtual-time-budget=5000 \
    --screenshot="$OUT/${slug}.png" \
    "${BASE}/?sparkvn_block=${slug}" >/dev/null
done
echo "done $(ls "$OUT" | wc -l) files"
