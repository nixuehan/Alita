<?php
require (__DIR__ . "/vendor/autoload.php");

$app = new Alita\App([
    'project_root' => __DIR__
]);

$app->startInitialize(function () {
    date_default_timezone_set("Asia/Shanghai");
});

//运行模式
$app->prod(false);

$app->Run();

