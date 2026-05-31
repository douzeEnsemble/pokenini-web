#!/usr/bin/env bash
# Checks that:
# 1. All files referenced in moco.json exist on disk
# 2. All files in the responses directory are referenced in moco.json

set -euo pipefail

MOCO_JSON="${1}"
LOCAL_DIR="${2}"
RESPONSES_DIR="${LOCAL_DIR}/responses"
MOCO_PREFIX="/var/moco/"

errors=0

mapfile -t REFERENCED_FILES < <(jq -r '.[].response.file // empty' "$MOCO_JSON" | sed "s|${MOCO_PREFIX}|${LOCAL_DIR}/|g" | sort)

for local_path in "${REFERENCED_FILES[@]+"${REFERENCED_FILES[@]}"}"; do
	if [ ! -f "$local_path" ]; then
		echo "❌ Missing file referenced in moco.json: $local_path"
		errors=$((errors + 1))
	fi
done

if [ -d "$RESPONSES_DIR" ]; then
	while IFS= read -r file; do
		if ! printf '%s\n' "${REFERENCED_FILES[@]+"${REFERENCED_FILES[@]}"}" | grep -qxF "$file"; then
			echo "❌ Unreferenced file in responses: $file"
			errors=$((errors + 1))
		fi
	done < <(find "$RESPONSES_DIR" -type f | sort)
fi

if [ "$errors" -gt 0 ]; then
	echo "Found $errors issue(s) in $MOCO_JSON."
	exit 1
fi

echo "✅ $MOCO_JSON — all references OK."
