<?php
//路由规则
$version = 'v0.1';
return [
    //登录
    "#^GET /{$version}/hello$#" => 'IndexController@index',
];