<?php

return [
    'pool' => [
        'minActive'         => 10,
        'maxActive'         => 30,
        'maxWaitTime'       => 5,
        'maxIdleTime'       => 20,
        'idleCheckInterval' => 10,
    ],

    'mysql' => [
        'host'        => 'mysql',
        'port'        => '3306',
        'user'        => 'root',
        'password'    => '1',
        'database'    => 'wanda',
        'timeout'     => 10,
        'charset'     => 'utf8mb4',
        'strict_type' => true,
        'fetch_mode'  => true,
    ],

    'redis' => [
        'host'     => 'redis',
        'port'     => '6379',
        'database' => 0,
        'password' => null,
    ],

    'jwt' => [
        'key' => 'wanda1983'
    ],

    'wechat' => [
        'app_id' => 'wxae07c7af7a70e590',
        'secret' => 'f1de9bf7501832d6151ca97204c31a28',

        'response_type' => 'array',

        'log' => [
            'level' => 'debug',
            'file' => \Alita\Runtime::$ROOT_PATH.'/Runtime/wechat.log',
        ],
    ]
];