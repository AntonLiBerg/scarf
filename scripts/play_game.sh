#!/usr/bin/env bash
set -euo pipefail

base_url="${1:-${BASE_URL:-http://127.0.0.1:$((18000 + RANDOM % 1000))}}"
server_log="$(mktemp)"
server_pid=""
last_response=""
last_status=""
game_id=""
base_map_json=""
display_map_json=""
display_map_title=""
last_message="Type 'start' to call /startgame."
actions=()

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

request() {
  local method="$1"
  local path="$2"
  local body="${3:-}"
  local response_file

  response_file="$(mktemp)"

  if [[ -n "$body" ]]; then
    last_status="$(curl -sS -o "$response_file" -w '%{http_code}' -X "$method" -H 'Content-Type: application/json' -d "$body" "$base_url$path")"
  else
    last_status="$(curl -sS -o "$response_file" -w '%{http_code}' -X "$method" "$base_url$path")"
  fi

  last_response="$(<"$response_file")"
  rm -f "$response_file"
}

json_read() {
  local path="$1"

  php -r '$data = json_decode(stream_get_contents(STDIN), true); if ($data === null && json_last_error() !== JSON_ERROR_NONE) { fwrite(STDERR, json_last_error_msg() . PHP_EOL); exit(1); } $value = $data; foreach (explode(".", $argv[1]) as $key) { if (!is_array($value) || !array_key_exists($key, $value)) { fwrite(STDERR, "Missing JSON path: " . $argv[1] . PHP_EOL); exit(1); } $value = $value[$key]; } if (is_array($value)) { echo json_encode($value); } else { echo $value; }' "$path" <<<"$last_response"
}

json_try_read() {
  local path="$1"

  php -r '$data = json_decode(stream_get_contents(STDIN), true); if ($data === null && json_last_error() !== JSON_ERROR_NONE) { exit(1); } $value = $data; foreach (explode(".", $argv[1]) as $key) { if (!is_array($value) || !array_key_exists($key, $value)) { exit(1); } $value = $value[$key]; } if (is_array($value)) { echo json_encode($value); } else { echo $value; }' "$path" <<<"$last_response"
}

print_map_from_json() {
  local map_json="$1"

  if [[ -z "$map_json" ]]; then
    printf 'No map loaded yet.\n'
    return
  fi

  php -r '$value = json_decode(stream_get_contents(STDIN), true); if ($value === null && json_last_error() !== JSON_ERROR_NONE) { fwrite(STDERR, json_last_error_msg() . PHP_EOL); exit(1); } if (!is_array($value)) { fwrite(STDERR, "Map is not an array" . PHP_EOL); exit(1); } $isList = array_keys($value) === range(0, count($value) - 1); if ($isList) { foreach ($value as $row) { echo $row, PHP_EOL; } exit(0); } $grid = []; $maxX = 0; $maxY = 0; foreach ($value as $coord => $tile) { if (!preg_match("~^(\\d+),(\\d+)$~", (string) $coord, $matches)) { continue; } $x = (int) $matches[1]; $y = (int) $matches[2]; $grid[$y][$x] = (string) $tile; $maxX = max($maxX, $x); $maxY = max($maxY, $y); } for ($y = 0; $y <= $maxY; $y++) { $row = ""; for ($x = 0; $x <= $maxX; $x++) { $row .= $grid[$y][$x] ?? " "; } echo $row, PHP_EOL; }' <<<"$map_json"
}

actions_to_json() {
  local json='['
  local i

  for i in "${!actions[@]}"; do
    if [[ "$i" -gt 0 ]]; then
      json+=','
    fi

    json+="\"${actions[$i]}\""
  done

  json+=']'
  printf '%s' "$json"
}

format_actions() {
  local joined=""
  local action

  if [[ ${#actions[@]} -eq 0 ]]; then
    printf 'none'
    return
  fi

  for action in "${actions[@]}"; do
    if [[ -n "$joined" ]]; then
      joined+=', '
    fi

    joined+="$action"
  done

  printf '%s' "$joined"
}

render_screen() {
  if [[ -t 1 ]] && command -v clear >/dev/null 2>&1; then
    clear
  fi

  printf '====================\n'
  printf 'Scarf Terminal Game\n'
  printf '====================\n'
  printf 'Base URL: %s\n' "$base_url"

  if [[ -n "$game_id" ]]; then
    printf 'Game ID: %s\n' "$game_id"
  else
    printf 'Game ID: not started\n'
  fi

  printf 'Queued actions: %s\n' "$(format_actions)"
  printf 'Status: %s\n' "$last_message"

  if [[ -n "$display_map_title" ]]; then
    printf '\n%s\n' "$display_map_title"
    print_map_from_json "$display_map_json"
  fi

  printf '\nCommands\n'
  printf '  start : call /startgame\n'
  printf '  w     : queue Up\n'
  printf '  s     : queue Down\n'
  printf '  a     : queue Left\n'
  printf '  d     : queue Right\n'
  printf '  p     : pop latest action\n'
  printf '  c     : clear queued actions\n'
  printf '  r     : run queued actions\n'
  printf '  m     : show start map\n'
  printf '  n     : start a new game\n'
  printf '  q     : quit\n'
}

start_game() {
  request GET /startgame
  if [[ "$last_status" != '200' ]]; then
    last_message="startgame failed: HTTP $last_status $last_response"
    return
  fi

  game_id="$(json_read 'gameState.id')"
  base_map_json="$(json_read 'gameState.map')"
  display_map_json="$base_map_json"
  display_map_title='Start map:'
  actions=()
  last_message="Game $game_id started. Queue actions with w/a/s/d."
}

run_actions() {
  local payload
  local result
  local map_json

  if [[ -z "$game_id" ]]; then
    last_message="Start a game first."
    return
  fi

  if [[ ${#actions[@]} -eq 0 ]]; then
    last_message="Queue at least one action before running."
    return
  fi

  printf -v payload '{"id":%s,"actions":%s}' "$game_id" "$(actions_to_json)"
  request POST /trysolution "$payload"
  if [[ "$last_status" != '200' ]]; then
    last_message="trysolution failed: HTTP $last_status $last_response"
    return
  fi

  result="$(json_read 'result')"
  if map_json="$(json_try_read 'resMap')"; then
    display_map_json="$map_json"
    display_map_title='Latest result map:'
  fi

  last_message="Server result: $result"
}

queue_action() {
  local action="$1"

  if [[ -z "$game_id" ]]; then
    last_message="Start a game first."
    return
  fi

  actions+=("$action")
  last_message="Queued $action."
}

pop_action() {
  local last_index
  local removed

  if [[ ${#actions[@]} -eq 0 ]]; then
    last_message='No queued actions to pop.'
    return
  fi

  last_index=$((${#actions[@]} - 1))
  removed="${actions[$last_index]}"
  unset 'actions[last_index]'
  actions=("${actions[@]}")
  last_message="Removed $removed from the queue."
}

trap cleanup EXIT

start_server
wait_for_server

while true; do
  render_screen
  read -r -p '> ' command || break

  case "$command" in
    start|n)
      start_game
      ;;
    w)
      queue_action Up
      ;;
    s)
      queue_action Down
      ;;
    a)
      queue_action Left
      ;;
    d)
      queue_action Right
      ;;
    p)
      pop_action
      ;;
    c)
      actions=()
      last_message='Cleared the queued actions.'
      ;;
    r)
      run_actions
      ;;
    m)
      if [[ -n "$base_map_json" ]]; then
        display_map_json="$base_map_json"
        display_map_title='Start map:'
        last_message='Showing the start map.'
      else
        last_message='Start a game first.'
      fi
      ;;
    q)
      break
      ;;
    '')
      last_message='Enter a command.'
      ;;
    *)
      last_message="Unknown command: $command"
      ;;
  esac
done
