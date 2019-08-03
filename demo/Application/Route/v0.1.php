<?php
//路由规则
$version = 'v0.1';
return [
    //登录
    "#^GET /{$version}/signin/(\w.+)$#" => 'PlayerController@signin',

    //个人编辑页
    "#^GET /{$version}/player/profile#" => 'PlayerController@profile',

    //基本信息编辑
    "#^POST /{$version}/player/profile/base$#" => 'PlayerController@base',
];