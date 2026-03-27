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
  local body="${3:-}"

  printf '== %s %s ==\n' "$method" "$path"

  if [[ -n "$body" ]]; then
    curl -sS -X "$method" \
      -H 'Content-Type: application/json' \
      -d "$body" \
      "$base_url$path"
  else
    curl -sS -X "$method" \
      "$base_url$path"
  fi
  printf '\n'
}

trap cleanup EXIT

start_server
wait_for_server

request GET /
request GET /health
request GET /startgame
request POST /echo "$payload"
request GET /missing
