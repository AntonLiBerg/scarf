#!/usr/bin/env bash
set -euo pipefail

base_url="${1:-${BASE_URL:-http://127.0.0.1:$((18000 + RANDOM % 1000))}}"
payload='{"name":"Medo","message":"hello"}'
server_log="$(mktemp)"
server_pid=""

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

  cat "$response_file"
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
request GET /health 200
request GET /startgame 200
request POST /echo 200 "$payload"
request GET /missing 404
