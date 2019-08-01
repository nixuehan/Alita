<?php
use Alita\Request;
use Alita\Response;

require (__DIR__ . "/vendor/autoload.php");

$app = new Alita\App();

$app->GET("#^/ping$#",function (Request $request,Response $response) {

//    $response->string("hahahha");
//    $response->redirect("http://www.baidu.com");
//    $response->json(['a'=>33]);
//    $response->end('正常输出');
//    return $request->input('id');
//    return "就那么简单的启动了";
//    $request->server()
});


$app->POST("#^/ping$#",function (Request $request,Response $response) {


});

$app->PATCH("#^/ping$#",function (Request $request,Response $response) {


});

$app->PUT("#^/ping$#",function (Request $request,Response $response) {


});

$app->DELETE("#^/ping$#",function (Request $request,Response $response) {


});

$app->Run();

