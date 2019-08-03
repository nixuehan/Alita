<?php

return [
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
        'app_id' => 'wx3cf0f39249eb0exx',
        'secret' => 'f1c242f4f28f735d4687abb469072axx',

        'response_type' => 'array',

        'log' => [
            'level' => 'debug',
            'file' => __DIR__.'/wechat.log',
        ],
    ]
];