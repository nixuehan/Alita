<?php
require (__DIR__ . "/vendor/autoload.php");

$app = new Alita\App([
    'project_root' => __DIR__
]);

//定义中间件
$app->middleware([
    "log" => function(\Alita\Request $request,\Alita\Response $response) {
        $request->set("token",sha1(time()));
    },

    'cache' => \Application\Middleware\Cache::class
]);

$app->process([
    //系统级
    "system" => ['log','cache'],

    //路由级
    "route" => [
        'cache' => [
            'home@index'
        ],
//
//        'cache' => '*', //所有路由
//
//        'cache' => [
//            'only' => ['home@profile'],
//        ]
    ]
]);

$app->setting(function () {
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
    ];
});

$app->events(function () {
    return [
        'payment' => [
            \Application\Events\Payment::class,
        ]
    ];
});

$app->Run();

