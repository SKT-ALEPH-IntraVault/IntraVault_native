#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

if [[ "$EUID" -eq 0 ]]; then
    echo 'intravault 서비스 계정으로 실행하세요. root로 실행하지 마세요.' >&2
    exit 1
fi

php -r 'if (PHP_VERSION_ID < 80300) {fwrite(STDERR,"PHP 8.3 이상이 필요합니다.\n"); exit(1);}'
[[ -f .env ]] || { echo '.env.example을 .env로 복사하고 값을 설정하세요.' >&2; exit 1; }

chmod 600 .env
mkdir -p storage/app/private storage/framework/{sessions,views,cache/data} storage/logs bootstrap/cache
export PHP_INI_SCAN_DIR="${PHP_INI_SCAN_DIR:-}:$PWD/native"

composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
composer check-platform-reqs --no-dev
php artisan config:clear
php native/check-local.php

if ! grep -Eq '^APP_KEY=base64:.+' .env; then
    php artisan key:generate
fi

php artisan migrate --seed --force
php artisan intravault:check

echo '설치 완료. 최초 관리자는 php artisan intravault:create-admin 명령으로 생성하세요.'
