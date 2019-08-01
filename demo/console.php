<?php
require (__DIR__ . "/vendor/autoload.php");

$console = (new \Alita\App())->getConsole($argc,$argv);

$console->setting(function () {
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
    ];
});

$console->run();