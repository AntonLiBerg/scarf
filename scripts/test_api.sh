#!/usr/bin/env bash
set -euo pipefail

base_url="${1:-${BASE_URL:-http://localhost:8000}}"
payload='{"name":"Medo","message":"hello"}'

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

request GET /
request GET /health
request GET /startgame
request POST /echo "$payload"
request GET /missing
