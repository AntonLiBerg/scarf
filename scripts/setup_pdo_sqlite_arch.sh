#!/usr/bin/env bash
set -euo pipefail

package_name="php-sqlite"
ini_file_name="50-sqlite.ini"

require_command() {
  local command_name="$1"

  if ! command -v "$command_name" >/dev/null 2>&1; then
    printf 'Missing required command: %s\n' "$command_name" >&2
    exit 1
  fi
}

run_as_root() {
  if [[ "${EUID}" -eq 0 ]]; then
    "$@"
    return
  fi

  require_command sudo
  sudo "$@"
}

php_module_loaded() {
  local module_name="$1"

  php -m | grep -qx "$module_name"
}

php_ini_value() {
  local label="$1"

  php --ini | awk -F': ' -v label="$label" '$1 == label { gsub(/"/, "", $2); print $2 }'
}

extension_declared() {
  local extension_name="$1"
  shift

  local config_file
  local pattern="^[[:space:]]*extension[[:space:]]*=[[:space:]]*(${extension_name}|${extension_name}\\.so)[[:space:]]*$"

  for config_file in "$@"; do
    if [[ -f "$config_file" ]] && grep -Eq "$pattern" "$config_file"; then
      return 0
    fi
  done

  return 1
}

require_command pacman
require_command php
require_command awk
require_command grep
require_command install
require_command mktemp

printf 'Installing %s if needed...\n' "$package_name"
run_as_root pacman -S --needed "$package_name"

if php_module_loaded "sqlite3" && php_module_loaded "pdo_sqlite"; then
  printf 'sqlite3 and pdo_sqlite are already loaded in CLI PHP.\n'
  exit 0
fi

loaded_ini="$(php_ini_value "Loaded Configuration File")"
scan_dir="$(php_ini_value "Scan for additional .ini files in")"

if [[ -z "$scan_dir" || "$scan_dir" == "(none)" ]]; then
  printf 'Could not determine PHP additional ini directory from `php --ini`.\n' >&2
  exit 1
fi

declare -a config_files=()

if [[ -n "$loaded_ini" && "$loaded_ini" != "(none)" ]]; then
  config_files+=("$loaded_ini")
fi

while IFS= read -r ini_file; do
  config_files+=("$ini_file")
done < <(find "$scan_dir" -maxdepth 1 -type f -name '*.ini' | sort)

if ! extension_declared "sqlite3" "${config_files[@]}"; then
  needs_sqlite3=1
else
  needs_sqlite3=0
fi

if ! extension_declared "pdo_sqlite" "${config_files[@]}"; then
  needs_pdo_sqlite=1
else
  needs_pdo_sqlite=0
fi

if [[ "$needs_sqlite3" -eq 0 && "$needs_pdo_sqlite" -eq 0 ]]; then
  printf 'PHP config already declares sqlite extensions, but CLI PHP still does not load them.\n' >&2
  printf 'Check `php --ini` and `php -m` for a conflicting PHP binary or broken module load.\n' >&2
  exit 1
fi

tmp_ini="$(mktemp)"
trap 'rm -f "$tmp_ini"' EXIT

{
  printf '; Added by scripts/setup_pdo_sqlite_arch.sh\n'

  if [[ "$needs_sqlite3" -eq 1 ]]; then
    printf 'extension=sqlite3\n'
  fi

  if [[ "$needs_pdo_sqlite" -eq 1 ]]; then
    printf 'extension=pdo_sqlite\n'
  fi
} >"$tmp_ini"

target_ini="${scan_dir%/}/${ini_file_name}"

printf 'Writing %s...\n' "$target_ini"
run_as_root install -m 644 "$tmp_ini" "$target_ini"

if ! php_module_loaded "sqlite3" || ! php_module_loaded "pdo_sqlite"; then
  printf 'PHP still does not report sqlite3/pdo_sqlite after writing %s.\n' "$target_ini" >&2
  printf 'Run `php --ini` and `php -m` to inspect the active CLI configuration.\n' >&2
  exit 1
fi

printf 'sqlite3 and pdo_sqlite are now enabled for CLI PHP.\n'
printf 'If you use php-fpm or Apache, restart that service before retrying the app.\n'
