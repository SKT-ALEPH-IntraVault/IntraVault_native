<?php
// Docker's process environment must not override PHPUnit's isolated database.
foreach (['APP_ENV'=>'testing','DB_DATABASE'=>'intravault_testing','DB_CONNECTION'=>'mariadb',
    'APP_KEY'=>'base64:'.base64_encode(str_repeat('t',32)), 'CACHE_STORE'=>'array',
    'SESSION_DRIVER'=>'array','LAB_ENABLED'=>'true','BCRYPT_ROUNDS'=>'4'] as $name=>$value) {
    putenv("$name=$value"); $_ENV[$name]=$value; $_SERVER[$name]=$value;
}
require dirname(__DIR__).'/vendor/autoload.php';
