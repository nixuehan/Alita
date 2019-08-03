<?php
use Alita\Request;
use Alita\Response;

require (__DIR__ . "/vendor/autoload.php");

$app = new Alita\App();

$app->GET("#^/hello#",function (Request $request,Response $response) {

    $response->string('hello alita!');

});

$app->Run();

