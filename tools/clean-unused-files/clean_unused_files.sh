#!/usr/bin/env bash

ROOT_DIR="${1:-.}"
TMP_DIR=".tmp_file_check"
LOG_FILE="var/log/cleanup_test.log"
TEST_CMD="make tu ti"

mkdir -p "$TMP_DIR"
echo "=== Cleanup started: $(date) ===" > "$LOG_FILE"

if ! command -v ${TEST_CMD%% *} >/dev/null 2>&1; then
  echo "Test command not found: $TEST_CMD"
  exit 1
fi

check_file() {
  local file="$1"
  local rel_path="${file#$ROOT_DIR/}"
  mkdir -p "$TMP_DIR/$(dirname "$rel_path")"
  mv "$file" "$TMP_DIR/$rel_path"
  echo "Testing without $rel_path..." >> "$LOG_FILE"

  $TEST_CMD >/dev/null 2>&1
  local status=$?

  if [ $status -eq 0 ]; then
    echo "✅ OK without $rel_path — deleted" | tee -a "$LOG_FILE"
    rm -f "$TMP_DIR/$rel_path"
  else
    echo "❌ Failed without $rel_path — restored" | tee -a "$LOG_FILE"
    mv "$TMP_DIR/$rel_path" "$file"
  fi
}

export -f check_file
export ROOT_DIR TMP_DIR LOG_FILE TEST_CMD

find "$ROOT_DIR" -type f \
  ! -path "$TMP_DIR/*" \
  ! -path "*/vendor/*" \
  ! -path "*/node_modules/*" \
  ! -name "*.log" \
  -print0 | xargs -0 -n1 bash -c 'check_file "$@"' _

echo "=== Cleanup finished: $(date) ===" >> "$LOG_FILE"
echo "See $LOG_FILE for details."
