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
        'pool' => [
            'minActive'         => 10,
            'maxActive'         => 30,
            'maxWaitTime'       => 5,
            'maxIdleTime'       => 20,
            'idleCheckInterval' => 10,
        ]
    ],

    'redis' => [
        'host'     => 'redis',
        'port'     => '6379',
        'database' => 0,
        'password' => null,
        'pool' => [
            'minActive'         => 10,
            'maxActive'         => 30,
            'maxWaitTime'       => 5,
            'maxIdleTime'       => 20,
            'idleCheckInterval' => 10,
        ]
    ],
];