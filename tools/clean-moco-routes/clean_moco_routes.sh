#!/usr/bin/env bash

MOCK_FILE="${1:-mocks.json}"
TMP_DIR="var/tmp_moco_check"
LOG_FILE="var/log/moco_cleanup.log"
TEST_CMD="make restart-mocks tests"

mkdir -p "$(dirname "$LOG_FILE")" "$TMP_DIR"
echo "=== Moco cleanup started: $(date) ===" > "$LOG_FILE"

if ! command -v ${TEST_CMD%% *} >/dev/null 2>&1; then
  echo "Test command not found: $TEST_CMD"
  exit 1
fi

if [ ! -f "$MOCK_FILE" ]; then
  echo "Mock file not found: $MOCK_FILE"
  exit 1
fi

BACKUP_FILE="$TMP_DIR/mocks_backup.json"
cp "$MOCK_FILE" "$BACKUP_FILE"

count=$(jq length "$MOCK_FILE")
echo "Found $count routes" | tee -a "$LOG_FILE"

get_route_label() {
  local index="$1"
  jq -r --argjson idx "$index" '
    .[$idx].request as $req |
    # method fallback
    ($req.method // "ANY") as $method |

    # path fallback
    ($req.path
     // (if $req.uri | type == "object" then
           $req.uri.match // $req.uri.equals // "(unknown)"
         elif $req.uri | type == "string" then
           $req.uri
         else
           "(unknown)"
         end)) as $path |

    "\($method) \($path)"
  ' "$MOCK_FILE"
}

check_route() {
  local index="$1"
  local tmp_file="$TMP_DIR/mocks_tmp.json"

  local route_path
  route_path=$(get_route_label "$index")

  echo "Testing without route: $route_path" >> "$LOG_FILE"

  jq "del(.[${index}])" "$MOCK_FILE" > "$tmp_file"
  mv "$tmp_file" "$MOCK_FILE"

  $TEST_CMD >/dev/null 2>&1
  local status=$?

  if [ $status -eq 0 ]; then
    echo "✅ OK without route $route_path — deleted" | tee -a "$LOG_FILE"
    cp "$MOCK_FILE" "$BACKUP_FILE"
    return 0
  else
    echo "❌ Failed without route $route_path — restored" | tee -a "$LOG_FILE"
    cp "$BACKUP_FILE" "$MOCK_FILE"
    return 1
  fi
}

i=0
while [ $i -lt $count ]; do
  check_route "$i"
  status=$?
  if [ $status -eq 0 ]; then
    ((count--))
  else
    ((i++))
  fi
done

echo "=== Cleanup finished: $(date) ===" >> "$LOG_FILE"
echo "See $LOG_FILE for details."
