#!/usr/bin/env bash

ROOT_DIR="${1:-.}"
TMP_DIR="var/tmp_file_check"
LOG_FILE="var/log/cleanup_test.log"
TEST_CMD="make restart-mocks tests-api-mocked"

mkdir -p "$TMP_DIR"
echo "=== Cleanup started: $(date) ===" >"$LOG_FILE"

if ! command -v ${TEST_CMD%% *} >/dev/null 2>&1; then
	echo "Test command not found: $TEST_CMD"
	exit 1
fi

# --- Collect all files first ---
mapfile -t FILES < <(find "$ROOT_DIR" -type f \
	! -path "$TMP_DIR/*" \
	! -path "*/vendor/*" \
	! -path "*/node_modules/*" \
	! -name "*.log")

TOTAL=${#FILES[@]}
echo "Found $TOTAL files to test." | tee -a "$LOG_FILE"

check_file() {
	local index="$1"
	local file="$2"
	local rel_path="${file#$ROOT_DIR/}"
	local tmp_path="$TMP_DIR/$rel_path"

	echo "[$((index + 1))/$TOTAL] Testing without $rel_path..."
	echo "[$((index + 1))/$TOTAL] Testing without $rel_path..." >>"$LOG_FILE"

	mkdir -p "$(dirname "$tmp_path")"
	mv "$file" "$tmp_path"

	local start_time=$(date +%s.%N)
	$TEST_CMD >/dev/null 2>&1
	local status=$?
	local end_time=$(date +%s.%N)

	local duration=$(awk "BEGIN {printf \"%.2f\", $end_time - $start_time}")

	if [ $status -eq 0 ]; then
		echo "✅ OK without $rel_path — deleted (${duration}s)" | tee -a "$LOG_FILE"
		rm -f "$tmp_path"
	else
		echo "❌ Failed without $rel_path — restored (${duration}s)" | tee -a "$LOG_FILE"
		mv "$tmp_path" "$file"
	fi
}

i=0
for file in "${FILES[@]}"; do
	check_file "$i" "$file"
	((i++))
done

echo "=== Cleanup finished: $(date) ===" >>"$LOG_FILE"
echo "See $LOG_FILE for details."
