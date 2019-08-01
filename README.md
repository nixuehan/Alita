## 快速启动 alita

首先项目根目录 composer 来安装 Alita 框架

```composer require nixuehan/alita```

然后在项目根目录写下面这段代码：

main.php

```
<?php
use Alita\Request;
use Alita\Response;

require (__DIR__ . "/vendor/autoload.php");

$app = new Alita\App();

$app->GET("#^/ping$#",function (Request $request,Response $response) {
    return "就那么简单的启动了";
});

$app->Run();


```
在控制台运行
`php main.php`

输出如下：

```Alita Server Alpha.0.0.4 Started ....```


swoole 默认监听 本地地址的9521端口

然后我们请求下
```
yehuiming$ curl http://127.0.0.1:9521/ping
就那么简单的启动了
```

带参数
```
$app->GET("#^/ping/(\d+)$#",function (Request $request,Response $response,$id) {
    return $id;
//    return "就那么简单的启动了";
});
```

GET() 里面是我们的正则公式 (不懂正则的，赶紧去学下，不会很复杂）, 利用正则分组分配 (\d+) 来捕获我们需要的URL上的参数，然后通过 匿名函数 参数 $id 来进行获取

```
yehuiming$ curl http://127.0.0.1:9521/ping/999
999
```

那 post delete put patch 等同理

```
$app->POST("#^/ping$#",function (Request $request,Response $response) {


});

$app->PATCH("#^/ping$#",function (Request $request,Response $response) {


});

$app->PUT("#^/ping$#",function (Request $request,Response $response) {


});

$app->DELETE("#^/ping$#",function (Request $request,Response $response) {


});
```

Request 代表 HTTP请求对象
Response 代表 HTTP输出对象

这两个对象包含了我们很多常用的方法.... 

### 常用操作

```$request->server()  //返回 $_SERVER```
```$request->server('path_info') //返回 $_SERVER['path_info']```


```$request->input()  //返回 $_GET + $_POST```
```$_request->input('id',0)  // 0 是默认值```  


```$response->abort('中断输出')```

```$response->end('正常输出');```

```$response->json(['a'=>33]); //输出json```

```$response->redirect("http://www.baidu.com"); //默认是302```

```$response->redirect("http://www.baidu.com",301); //301跳转```

```return ['a' => 1,'b' => 2]; //直接return 数组  那么会自动转成json输出```

## $_GET  $_POST


比如  curl http://127.0.0.1/ping?id=6666

```
$app->GET("#^/ping$#",function (Request $request,Response $response) {

    return $request->input('id',0);
//    return "就那么简单的启动了";
});
```

$_GET 和 $_POST 统一使用 $request->input 来获取


## alita服务设置

可参考swoole文档

[swoole 的 server 参数解释](https://wiki.swoole.com/wiki/page/14.html)

[swoole 的 server 参数解释](https://wiki.swoole.com/wiki/page/13.html)
```
$app = new Alita\App([
    'project_root' => __DIR__, // * 设置项目根目录。

    // 下面的swoole 请参照 swoole 的详细介绍
    'server' => [
        'host' => '', 
        'port' => 9521,
        'daemonize'             => false,
        'dispatch_mode'         => 3,
    ]
]);

//一些启动初始化工作 可以在这里做，看自己业务需求，不是必须的
$app->startInitialize(function () {
    //启动初始化
    date_default_timezone_set("Asia/Shanghai");
});

```

对于大一点的项目。
可能我们需要定制的东西就稍微多一点了，比如 控制器、业务模型等 都需要独立到其他目录去

## 控制器和业务模型

### 项目目录结构
----Application

--------Controllers

--------------Home.php

--------Models

--------------Home.php

--------Route

--------------v1.0.1.php

--------------v1.0.2.php

----vendor

----main.php

按照上面结构。我们新建  Application 目录 然后在里面 建我们自己的 控制器目录 Controllers 和 业务模型目录 Models 和 路由规则目录。

main.php

```
<?php
//先引入 composer的autoload.php
require (__DIR__ . "/vendor/autoload.php");

$app = new Alita\App([
    'project_root' => __DIR__
]);

$app->Run();

```

然后我们修改一下 composer.json

把我们Application目录 加入composer的 psr-4里。这一步很重要。否则会提示找不到 控制器的哦。
还不懂 composer  psr-4的  先去学习一下 

[composer psr-4](https://getcomposer.org/doc/04-schema.md#psr-4)

```
    "autoload": {
      "psr-4": {
        "Application\\": "Application"
      }
    },
```


控制器 Home.php
```
<?php
namespace Application\Controllers;

use Alita\BaseController;

class Home extends BaseController
{
    public function index()
    {
        $home = new \Application\Models\Home();
        return $home->test();
    }

    public function profile()
    {
        return 'hello alita ~~';
    }
}
```

模型 Home.php

```
<?php
namespace Application\Models;

use Alita\BaseModel;

class Home extends BaseModel
{

    public function test()
    {
        return "module";
    }
}
```

路由文件 v1.0.1.php

```
<?php
//路由规则
return [
    "#^GET /$#" => 'home@index',
];
```

路由文件 v1.0.2.php

```
<?php
//路由规则
return [
    "#^GET /home/profile/(\d+)$#" => 'home@profile',
];
```


好了  控制器  模型   路由  都写完了

然后启动 服务   

php main.php

请求一下
```
curl http://127.0.0.1/home/profile/222

```

输出 hello alita ~~

## Mysql

alita框架内置了一个基于 swoole mysql异步客户端的简单的orm 。当然你可以可以composer其他好用的orm

```
<?php
require (__DIR__ . "/vendor/autoload.php");

$app = new Alita\App([
    'project_root' => __DIR__
]);

//当设置了mysql的信息后， 会自动创建 mysql连接池。
//连接池 使用的是 [connection-pool](https://packagist.org/packages/open-smf/connection-pool) 库  稳当靠谱

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

$app->Run();
```

连接上数据库后。 我们修改一下 模型 Home.php

```
<?php
namespace Application\Models;

use Alita\BaseModel;

class Home extends BaseModel
{

    public function test()
    {
        return "hello alita ~~";
    }

    //新加的方法，从数据库查询
    public function getPlayer()
    {
        return $this->db->table('player')
            ->where('player_id = ?',[6])
            ->and('gender = ?',[1])
            ->or('player_id = ?',[2])
            ->find();
    }
}
```

控制器Home.php里调用

```
<?php
namespace Application\Controllers;

use Alita\BaseController;

class Home extends BaseController
{
    public function index()
    {

    }


    public function profile()
    {
        $profile = new \Application\Models\Home();

        return $profile->getPlayer();
    }
}
```

最后输出：

```
[{"player_id":2,"avatar":"sdfasdfasdfasdf.jpg","player_name":"\u8bf4\u7684","openid":"023JSdQ51AiWRS1R5uS51kMpQ51JSdQu","gender":0,"create_time":1563349887},{"player_id":6,"avatar":"asdfads","player_name":"ddsd","openid":"2323","gender":1,"create_time":1564241772}]

```

## 中间件

```
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

    //除了用匿名函数定义外，还可以直接使用类名
    'cache' => \Application\Middleware\Cache::class
]);

//定义中间件类别
$app->process([
    //系统级
    "system" => ['log'],
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

$app->Run();

```

中间件分  系统级 和 路由级。
系统级，是每一个http 都会调用，而路由级主要是 命中路由规则之后，立刻调用

中间件除了用匿名函数定义外，还可以直接使用类名.这样我们可以把中间件统一放在一个目录下

### 项目目录结构
----Application

--------Controllers

--------------Home.php

--------Models

--------------Home.php

--------Route

--------------v1.0.1.php

--------------v1.0.2.php

--------Middleware

--------------Cache.php

----vendor

----main.php


在Application 目录里建了一个 Middleware 目录 用来放中间件

Cache.php

```
<?php
namespace Application\Middleware;

USE Alita\Middleware;
use Alita\Request;
use Alita\Response;

class Cache implements Middleware
{
    //中间件必须要实现  handle方法哦
    public function handle(Request $request,Response $response)
    {
        $request->set("myName","阿丽塔");
    }
}
```

### 在中间件里， $request->set() 设置要传递到下一个中间件的值   $request->get() 获取

main.php
里 我们要配置一下 这个中间件是什么类型的  路由级还是系统级

```
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

$app->Run();

```

我们还可以调整下，让 Cache 中间件 成 路由级 ，把他设置在 home@index 

```
$app->process([
    //系统级
    "system" => ['log','cache'],

    //路由级
    "route" => [
        //只在这个路由上应用
        'cache' => [
            'home@index'
        ],

        //所有路由都调用， 和系统级类似
        'cache' => '*', //所有路由

        //只对这路由有效
        'cache' => [
            'only' => ['home@index'],
        ],
        
        //除了这个路由之外
        'cache' => [
            'except' => ['home@index'],
        ]
    ]
]);
```

//设置运行环境
测试环境和生产环境
$app->prod(true); 

//获取目前运行环境
$app->getMode() == 'dev'


# 全局对象定义和获取

main.php

```
$app->Service([
    'Cache' => function() {
        return new stdClass();
    },
]);
```

也可以

```
$app->Service([
    'Cache' => \Application\Service\Cache::class,
]);
```

定义以后， 就可以在 其他地方随意调用了，对象生命周期是一个http周期

```
$cache = Service::Request();
```


# 事件

alita框架默认已经支持事件。能更好的解耦模块

main.php 里我们提前设置一下 事件以及对应的触发实体

```
$app->events(function () {
    return [
        'payment' => [
            \Application\Events\Payment::class,
        ]
    ];
});
```

payment 是事件名

\Application\Events\Payment  是我们提前编写好的一个事件类

我们在 Application 目录里 建好 Events (目录名随意..  只要在 Application目录下就好，为啥？ 因为这个目录我们已经加入了 composer psr-4, 那么在 实例化类的时候 很方便对吧)

```
<?php
namespace Application\Events;

use Alita\Event;

class Payment implements Event
{
    public function handle(array $params)
    {
        return $params;
    }
}
```

只是简单的返回了 传入的参数

然后我们就可以在控制器或者模型里任意调用了

```
<?php
namespace Application\Controllers;

use Alita\BaseController;
use Alita\Service;

class Home extends BaseController
{
    public function index()
    {
        return $this->Request->get('myName');
    }

    public function profile()
    {
        $profile = new \Application\Models\Home();

        return $profile->getPlayer();
    }

    //支付
    public function payment()
    {
        //初始化事件类
        $events = Service::Events();

        return $events->emit('payment',[
            'orderID' => 'alita2019'
        ]);
    }
}
```


Events->emit('事件名',[参数])   * 事件名我们已经在 main.php 里定义


//TODO
命令行
批量测试脚本
