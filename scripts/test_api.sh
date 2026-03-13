#!/usr/bin/env bash
set -euo pipefail

base_url="${1:-http://localhost:8080}"
payload='{"name":"Medo","message":"hello"}'

request() {
  local method="$1"
  local path="$2"
  local body="${3:-}"

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
request POST /echo "$payload"
request GET /missing
