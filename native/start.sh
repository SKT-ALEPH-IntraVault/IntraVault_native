#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."
[[ -f vendor/autoload.php ]] || { echo '먼저 bash native/setup.sh를 실행하세요.' >&2; exit 1; }

export PHP_INI_SCAN_DIR="${PHP_INI_SCAN_DIR:-}:$PWD/native"
php artisan config:clear
php native/check-local.php

exec php artisan serve \
    --host="${INTRAVAULT_BIND:-127.0.0.1}" \
    --port="${INTRAVAULT_PORT:-18080}"
