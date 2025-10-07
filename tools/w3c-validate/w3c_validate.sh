#!/usr/bin/env bash

set -euo pipefail

BASE_URI="http://localhost/"
OUT_DIR="var/html"
VNU_CMD="docker compose run --rm --remove-orphans vnu vnu --format text"

URLS=(
  ""
  "fr"
  "fr/connect"
  "fr/album/dex"
  "fr/album/demolite"
  "fr/election/dex"
  "fr/election/demo"
  "fr/outerroom"
  "fr/policy"
  "fr/legals"
  "fr/cookies"
  "en/policy"
  "en/legals"
  "en/cookies"
  "fr/trainer"
  "fr/istration"
  "fr/istration/action/calculate/pokemon_availabilities"
  "fr/istration/action/invalidate/reports"
  "fr/istration/action/update/labels"
)

authenticate() {
  local url="${BASE_URI}fr/connect/f/c?t=trainer"
  echo "🔐 Authenticating with $url ..."
  if ! curl -fsSL "$url" >/dev/null; then
    echo "❌ Authentication failed"
    exit 1
  fi
  echo "✅ Authenticated successfully."
  echo
}

clean_output_dir() {
  echo "🧹 Cleaning output directory: $OUT_DIR"
  rm -rf "$OUT_DIR"
  mkdir -p "$OUT_DIR"
  echo
}

fetch_and_clean() {
  local website="$1"
  local url="${BASE_URI}${website}"
  local filename="$(echo "$website" | sed 's|^https\?://||; s|[^A-Za-z0-9._-]|_|g')"
  [ -z "$filename" ] && filename="index"
  local outfile="${OUT_DIR}/${filename}.html"

  echo "🌐 Fetching $url ... to $outfile"
  if ! curl -fsSL "$url" | grep -v "Symfony Web Debug Toolbar" > "$outfile"; then
    echo "⚠️ Failed to fetch $url"
    return
  fi
}

run_validation() {
  echo "🔍 Running HTML validation..."
  if ! $VNU_CMD "/app/$OUT_DIR"; then
    echo "⚠️ Validation errors detected"
  else
    echo "✅ All HTML files are valid!"
  fi
}

main() {
  clean_output_dir
  authenticate

  for website in "${URLS[@]}"; do
    fetch_and_clean "$website"
  done

  run_validation
  echo "🏁 Validation completed."
}

main "$@"
