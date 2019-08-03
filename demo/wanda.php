<?php
require (__DIR__ . "/vendor/autoload.php");

$app = new Alita\App([
    'project_root' => __DIR__
]);

$app->startInitialize(function () use($app) {
    date_default_timezone_set("Asia/Shanghai");
    //初始化 config
    if ($app->getMode() == 'dev') {
        $config = new \Noodlehaus\Config(__DIR__ . '/Application/Config/App.dev.php');
    }else{
        $config = new \Noodlehaus\Config(__DIR__ . '/Application/Config/App.prod.php');
    }
    $app->config = $config;
});

$app->prod(false);

$app->Service([
    //配置
    'Config' => function() use($app) {
        return $app->config;
    },

    //小程序
    'MiniProgram' => function() use($app) {
        return $app = \EasyWeChat\Factory::miniProgram($app->config->get('wechat'));
    }

]);

$app->setting(function () use($app) {
    return [
        'pool' => $app->config->get('pool')
    ];
});

//设置内置mysql
$app->mysql(function () use($app) {
    return $app->config->get('mysql');
});

$app->redis(function () use($app) {
    return $app->config->get('redis');
});

//定义中间件
$app->middleware([
    'deepAuth' => \Application\Middleware\DeepAuth::class

])->process([
    //系统级
//    "system" => ['deepAuth'],

    //路由级
    "route" => [
//        'deepAuth' => [
//            'PlayerController@signin'
//        ],

//        'cache' => '*', //所有路由

//        'deepAuth' => [
//            'only' => ['home@profile'],
//        ],

        'deepAuth' => [
            'except' => ['PlayerController@signin'],
        ]
    ]
]);

$app->Run();

