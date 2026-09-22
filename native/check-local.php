<?php

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$main = config('database.connections.mariadb');
$lab = config('database.connections.lab_read');

$valid = app()->environment('local')
    && !config('app.debug')
    && config('database.default') === 'mariadb'
    && $main['host'] === '127.0.0.1'
    && $main['database'] === 'intravault_native'
    && $main['username'] === 'intravault_native'
    && empty($main['url'])
    && empty($main['unix_socket'])
    && $lab['database'] === 'intravault_native_lab'
    && $lab['username'] === 'intravault_native_reader';

if (!$valid) {
    fwrite(STDERR, "CentOS native용 .env.example 값을 기준으로 .env를 설정하세요.\n");
    exit(1);
}

foreach ([$main['password'], $lab['password']] as $password) {
    if (!$password || str_starts_with($password, 'replace-with-')) {
        fwrite(STDERR, ".env와 native/database.sql의 비밀번호를 먼저 변경하세요.\n");
        exit(1);
    }
}

echo "CentOS native 설정 확인 완료.\n";
