#!/usr/bin/env bash
set -euo pipefail

base_url="${1:-${BASE_URL:-http://127.0.0.1:$((18000 + RANDOM % 1000))}}"
payload='{"name":"Medo","message":"hello"}'
server_log="$(mktemp)"
server_pid=""
last_response=""

cleanup() {
  if [[ -n "$server_pid" ]] && kill -0 "$server_pid" 2>/dev/null; then
    kill "$server_pid" 2>/dev/null || true
    wait "$server_pid" 2>/dev/null || true
  fi

  rm -f "$server_log"
}

start_server() {
  local host_port="$base_url"

  host_port="${host_port#http://}"
  host_port="${host_port#https://}"
  host_port="${host_port%%/*}"

  php -S "$host_port" -t public public/index.php >"$server_log" 2>&1 &
  server_pid="$!"
}

wait_for_server() {
  local attempt

  for attempt in $(seq 1 50); do
    if curl -sS "$base_url/health" >/dev/null 2>&1; then
      return 0
    fi

    if ! kill -0 "$server_pid" 2>/dev/null; then
      cat "$server_log" >&2
      return 1
    fi

    sleep 0.1
  done

  cat "$server_log" >&2
  return 1
}

json_read() {
  local path="$1"

  php -r '$data = json_decode(stream_get_contents(STDIN), true); if ($data === null && json_last_error() !== JSON_ERROR_NONE) { fwrite(STDERR, json_last_error_msg() . PHP_EOL); exit(1); } $value = $data; foreach (explode(".", $argv[1]) as $key) { if (!is_array($value) || !array_key_exists($key, $value)) { fwrite(STDERR, "Missing JSON path: " . $argv[1] . PHP_EOL); exit(1); } $value = $value[$key]; } if (is_array($value)) { echo json_encode($value); } else { echo $value; }' "$path" <<<"$last_response"
}

json_expect_eq() {
  local path="$1"
  local expected="$2"
  local actual

  actual="$(json_read "$path")"
  if [[ "$actual" != "$expected" ]]; then
    printf 'Expected %s=%s, got %s\n' "$path" "$expected" "$actual" >&2
    return 1
  fi
}

print_map() {
  local path="$1"
  local title="$2"

  printf '\n%s\n' "$title"
  php -r '$data = json_decode(stream_get_contents(STDIN), true); if ($data === null && json_last_error() !== JSON_ERROR_NONE) { fwrite(STDERR, json_last_error_msg() . PHP_EOL); exit(1); } $value = $data; foreach (explode(".", $argv[1]) as $key) { if (!is_array($value) || !array_key_exists($key, $value)) { fwrite(STDERR, "Missing JSON path: " . $argv[1] . PHP_EOL); exit(1); } $value = $value[$key]; } if (!is_array($value)) { fwrite(STDERR, "Map is not an array at path: " . $argv[1] . PHP_EOL); exit(1); } $isList = array_keys($value) === range(0, count($value) - 1); if ($isList) { foreach ($value as $row) { echo $row, PHP_EOL; } exit(0); } $grid = []; $maxX = 0; $maxY = 0; foreach ($value as $coord => $tile) { if (!preg_match("~^(\\d+),(\\d+)$~", (string) $coord, $matches)) { continue; } $x = (int) $matches[1]; $y = (int) $matches[2]; $grid[$y][$x] = (string) $tile; $maxX = max($maxX, $x); $maxY = max($maxY, $y); } for ($y = 0; $y <= $maxY; $y++) { $row = ""; for ($x = 0; $x <= $maxX; $x++) { $row .= $grid[$y][$x] ?? " "; } echo $row, PHP_EOL; }' "$path" <<<"$last_response"
}

select_solution_actions() {
  local map_json="$1"

  case "$map_json" in
    '["#######G#####","###     #####","### #########","##  #########","##          #","##########  #","#           #","####### #####","#           #","# R #########"]')
      printf '%s' '["Right","Up","Right","Right","Right","Right","Up","Up","Right","Right","Right","Up","Up","Left","Left","Left","Left","Left","Left","Left","Up","Up","Up","Right","Right","Right","Right","Up"]'
      ;;
    '["#############","###########G#","#########   #","######### ###","#####     ###","##### #######","#     #######","# ###########","#R###########","#############"]')
      printf '%s' '["Up","Up","Right","Right","Right","Right","Up","Up","Right","Right","Right","Right","Up","Up","Right","Right","Up"]'
      ;;
    '["#############","#G        ###","######### ###","###       ###","### #########","### #########","###     #####","####### #####","#######    R#","#############"]')
      printf '%s' '["Left","Left","Left","Left","Up","Up","Left","Left","Left","Left","Up","Up","Up","Right","Right","Right","Right","Right","Right","Up","Up","Left","Left","Left","Left","Left","Left","Left","Left"]'
      ;;
    '["#############","#R    #######","##### #######","##### #######","#####     ###","######### ###","######### ###","######### ###","#########  G#","#############"]')
      printf '%s' '["Right","Right","Right","Right","Down","Down","Down","Right","Right","Right","Right","Down","Down","Down","Down","Right","Right"]'
      ;;
    '["#############","#######    R#","####### #####","###     #####","### #########","###       ###","######### ###","#         ###","#G###########","#############"]')
      printf '%s' '["Left","Left","Left","Left","Down","Down","Left","Left","Left","Left","Down","Down","Right","Right","Right","Right","Right","Right","Down","Down","Left","Left","Left","Left","Left","Left","Left","Left","Down"]'
      ;;
    *)
      printf 'Unknown map from /startgame: %s\n' "$map_json" >&2
      return 1
      ;;
  esac
}

request() {
  local method="$1"
  local path="$2"
  local expected_status="$3"
  local body="${4:-}"
  local response_file
  local status

  response_file="$(mktemp)"

  printf '== %s %s ==\n' "$method" "$path"

  if [[ -n "$body" ]]; then
    status="$(curl -sS -o "$response_file" -w '%{http_code}' -X "$method" \
      -H 'Content-Type: application/json' \
      -d "$body" \
      "$base_url$path")"
  else
    status="$(curl -sS -o "$response_file" -w '%{http_code}' -X "$method" \
      "$base_url$path")"
  fi

  last_response="$(<"$response_file")"
  printf '%s' "$last_response"
  rm -f "$response_file"

  if [[ "$status" != "$expected_status" ]]; then
    printf 'Expected HTTP %s, got %s for %s %s\n' "$expected_status" "$status" "$method" "$path" >&2
    return 1
  fi

  printf '\n'
}

trap cleanup EXIT

start_server
wait_for_server

request GET / 200
json_expect_eq status ok
request GET /health 200
json_expect_eq status ok
request GET /startgame 200
game_id="$(json_read 'gameState.id')"
map_json="$(json_read 'gameState.map')"
solution_actions="$(select_solution_actions "$map_json")"
json_expect_eq gameState.state WaitingForInput
print_map gameState.map 'Start map:'
request POST /echo 200 "$payload"
json_expect_eq received.name Medo
json_expect_eq received.message hello
printf -v solution_payload '{"id":%s,"actions":%s}' "$game_id" "$solution_actions"
request POST /trysolution 200 "$solution_payload"
json_expect_eq result correct
print_map resMap 'Solved map:'
request GET /missing 404
