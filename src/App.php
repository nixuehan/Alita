<?php
namespace Alita;
use Smf\ConnectionPool\ConnectionPool;
use Smf\ConnectionPool\ConnectionPoolTrait;
use Smf\ConnectionPool\Connectors\CoroutineMySQLConnector;
use Smf\ConnectionPool\Connectors\PhpRedisConnector;
use Swoole\Http\Server;

final class AUTHOR
{
    const AUTHOR = '妖';
    const VERSION = 'Alpha.0.1.2';
}


class ORM
{
    private $_tableName = '';
    private $_db = null;

    private $_where = [];
    private $_raw = '';

    //设置数据源
    public function setConnect($conn)
    {
        $this->_db = $conn;
        return $this;
    }

    public function getConnect()
    {
        return $this->_db;
    }

    //返回原始db,用法参照 swoole 异步mysql
    public function raw()
    {
        return $this->_db;
    }

    public function table($tableName) {
        $this->_tableName = $tableName;
        return $this;
    }

    public function getTable() {
        return $this->_tableName;
    }

    public function query(string $sql,array $params = []) {

        $conn = static::getConnect();
        $stmt = $conn->prepare($sql);

        if ($stmt == false) {
            throw new MysqlException("errno : {$conn->errno} {error : $conn->error}");
        }

        if(false == $stmt->execute($params)) {
            return false;
        }

        return $stmt;
    }

    public function insert_id() {
        return static::getConnect()->insert_id;
    }

    public function insert(array $data) {

        $fn = function () use($data){
            $_field = $_value = [];

            foreach($data as $k => $v) {
                $_field[] = $k;
                $_sign[] = '?';
                $_params[] = $v;
            }

            $field = implode(',',$_field);
            $_sign = implode(',',$_sign);

            return ["sql" => sprintf("(%s) VALUES (%s)",$field,$_sign),"value" => $_params];
        };

        $val = $fn->call($this);

        list($sql,$params) = $this->build("INSERT INTO {$this->getTable()} %s",$val);

        $stmt = $this->query($sql,$params);

        if ($stmt === false) { return false; }

        $this->clean();

        return $this->insert_id();
    }

    //清除各种ORM临时中间变量
    private function clean()
    {
        $this->clearWhere();
    }

    private function fetchAll(string $sql,array $params = []) {

        $stmt = $this->query($sql,$params);
        $this->clean();

        if ($stmt === false) { return false; }

        return $stmt->fetchAll();
    }

    private function fetch(string $sql,array $params = []) {

        $stmt = $this->query($sql,$params);
        $this->clean();

        if ($stmt === false) { return false; }

        return $stmt->fetch();
    }

    // left join
    public function leftJoin(string $src,string $target,string $condition)
    {
        //select %s from %s as a  left join bb as b on xx.%s = bb.%s where %s
        //[]

        // $this->db()
        //->leftJoin('aa as a','bb as b','a.id = b.cid')
        //->where()
        //->first()

        $this->_tableName = sprintf("%s  left join %s ON %s",$src,$target,$condition);
        return $this;
    }

    public function first(array $params = null) {

        $field = is_array($params) ? $this->getField($params) : '*';

        list($sql,$value) = $this->build("SELECT {$field} FROM {$this->getTable()} %s  LIMIT 1",$this->getWhere());

        return $this->fetch($sql,$value);
    }

    public function where(...$params) {

        $_params = [];

        $this->_where['sql'] = ' WHERE ' . $params[0];

        if(isset($params[1]) && is_array($params[1])) {
            $_params = $params[1];
        }

        $this->_where['value'] = $_params;

        return $this;
    }

    public function and(...$params) {

        $_params = [];

        $this->_where['sql'] .= ' AND ' . $params[0];

        if(isset($params[1]) && is_array($params[1])) {
            $_params = $params[1];
        }

        $this->_where['value'] = array_merge($this->_where['value'],$_params);

        return $this;
    }

    public function or(...$params) {
        $_params = [];

        $this->_where['sql'] .= ' OR ' . $params[0];

        if(isset($params[1]) && is_array($params[1])) {
            $_params = $params[1];
        }

        $this->_where['value'] = array_merge($this->_where['value'],$_params);

        return $this;
    }

    public function in(string $field,array $values) {

        $val = $_params = [];

        foreach($values as $value) {
            $val[] = '?';
            $_params[] = $value;
        }

        $val = implode(',',$val);

        $this->_where['sql'] = ' WHERE '.$field . " IN (".$val.")";

        $this->_where['value'] = $_params;

        return $this;
    }

    public function notin(string $field,array $values) {

        $val = $_params = [];

        foreach($values as $value) {
            $val[] = '?';
            $_params[] = $value;
        }

        $val = implode(',',$val);

        $this->_where['sql'] = ' WHERE '.$field . " NOT IN (".$val.")";

        $this->_where['value'] = $_params;

        return $this;
    }

    private function orderby($val) {
        if(strpos($val,'ORDER BY') === false) {
            return ' ORDER BY ';
        }else{
            return ',';
        }
    }

    public function desc(...$params) {

        $val = implode(',',array_map(function($k){
            return $k . ' DESC';
        },$params));

        $this->_where['sql'] .= $this->orderby($this->_where['sql']) . $val;
        return $this;
    }

    public function asc(...$params) {

        $val = implode(',',array_map(function($k){
            return $k . ' ASC';
        },$params));

        $this->_where['sql'] .= $this->orderby($this->_where['sql']) . $val;
        return $this;
    }

    private function getField(array $data) {
        return implode(',',$data);
    }

    public function forUpdate() {
        $this->_where['sql'] .= ' FOR UPDATE';
        return $this;
    }

    public function group(...$params) {
        $this->_where['sql'] .= ' GROUP BY ' . $this->getField($params);
        return $this;
    }

    private function getWhere() {
        return $this->_where ?? $this->_raw;
    }

    public function limit($start,$end=0) {
        if($end){
            $_sql = " LIMIT $start,$end";
        }else{
            $_sql = " LIMIT $start";
        }

        $this->_where['sql'] .= $_sql;
        return $this;
    }

    private function noWhere() {
        if(!$this->_where && !$this->_raw){
            throw new MysqlAlitaException('sql conditions miss!');
        }
    }

    private function clearWhere() {
        $this->_where = $this->_raw = '';
    }

    public function delete(bool $force=false) {

        if($force) $this->noWhere();

        list($sql,$value) = $this->build("DELETE FROM {$this->getTable()} %s",$this->getWhere());

        $stmt = $this->query($sql,$value);

        if (false === $stmt) { return false; }

        $this->clean();

        return $stmt;
    }

    private function getFieldValuePair(array $data) :array {
        $_pair = $_params = [];

        foreach($data as $k => $v) {
            $_pair[] = $k . '= ?';
            $_params[] = $v;
        }

        return ["sql" => implode(',', $_pair),"value" => $_params];
    }

    //组装sql和对应?的值,每一个sql片段都带着对应的值
    private function build($tpl,...$params)
    {
        $sqlstr = '';
        $value = [];

        foreach($params as $param) {
            $sqlstr .= $param['sql'];
            $value = array_merge($value,$param['value']);
        }

        $sql = sprintf($tpl,$sqlstr);
        return [$sql,$value];
    }

    public function update(array $params) {

        $this->noWhere($params);

        $pair = $this->getFieldValuePair($params);

        list($sql,$params) = $this->build("UPDATE {$this->getTable()} SET %s",$pair,$this->getWhere());

        $stmt = $this->query($sql,$params);

        if (false === $stmt) { return false; }

        $this->clean();

        return $stmt;
    }

    public function find($params = null) {

        $field = is_array($params) ? $this->getField($params) : '*';

        list($sql,$value) = $this->build("SELECT {$field} FROM {$this->getTable()} %s",$this->getWhere());

        return $this->fetchAll($sql,$value);
    }

    public function transaction(callable $fn) {

        $db = static::getConnect();

        $db->begin();
        $data = $fn();

        if(!$data){
            $db->rollback();
            return false;
        }
        $db->commit();
        return $data;
    }
}

class Log
{
    public static function print(array $message)
    {
        $message = implode('    ',$message);

        go(function () use($message){
            fwrite(STDOUT,$message ."\n");
        });
    }
}

class AlitaException extends \Exception
{
    public function __construct($e)
    {
        $msg = "\n#{$this->getFile()}  {$this->getLine()}\n";
        foreach($e as $key => $val) {
            $msg .= "#{$key} : {$val}\n";
        }

        parent::__construct($msg);
    }
}

class HttpNotFound extends AlitaException{}
class NoFoundRule extends AlitaException{}
class NoFoundController extends AlitaException{}
class NoFoundAction extends AlitaException{}
class MysqlException extends \Exception{}
class ConfigException extends \Exception{}

class WorkerException extends \Exception{} //普通业务异常

//中间件接口
interface Middleware
{
    function handle(Request $request,Response $response);
}

interface Event
{
    function handle(array $params) ;
}


//路由接口
interface Route
{
    function initialize();
    function getControllerActionParams(string $pathInfo); //控制器
}

//自定义路由
class RulesRoute implements Route
{
    public $rules = [];
    public $controllerAction = '';
    public $isMatch = false;
    private $_coreRules = [];

    public function __construct($coreRules)
    {
        $this->_coreRules = $coreRules;
    }

    public function initialize()
    {
        $this->import(Setting::$ROOT_DIR . '/Application/Route');
    }

    //导入路由规则
    public function import(string $path)
    {
        //导入硬路由规则
        if(!empty($this->_coreRules)) {
            $this->rules = $this->_coreRules;
        }else{
            //导入路由规则
            $paths = glob($path . '/*.php');

            if (empty($paths)) {
                throw new ConfigException("Configuration file: *.php cannot be found");
            }

            foreach($paths as $path) {
                $this->rules = array_merge($this->rules,require $path);
            }
        }
    }


    public function getCbParams(string $pathInfo) :array
    {
        foreach ($this->rules as $rule => $do) {

            if (preg_match($rule, $pathInfo, $matches)) {

                $_cb = $do;
                $this->isMatch = true;

                array_shift($matches);

                return [$_cb,$matches];
            }
        }
        return [null,[]];
    }

    //处理路由规则
    public function getControllerActionParams(string $pathInfo) :array
    {

        foreach ($this->rules as $rule => $do) {

            if(is_callable($do)) {

                if (preg_match($rule, $pathInfo, $matches)) {
                    $_cb = $do;
                    $this->isMatch = true;

                    array_shift($matches);

                    return [$_cb,$matches];
                }

            }else{

                if (preg_match($rule, $pathInfo, $matches)) {
                    list($controller,$action) = explode("@",$do);
                    $this->controllerAction = $do;
                    $this->isMatch = true;

                    array_shift($matches);

                    return ['Application\Controllers\\' . ucfirst($controller),$action,$matches];
                }
            }
        }
        return ['','',[]];
    }
}

//内置路由
class PathRoute implements Route
{
    public function initialize()
    {

    }

    public function getControllerActionParams(string $pathInfo) :array
    {
        $pathInfo = trim($pathInfo,"/");

        $pos = strrpos($pathInfo,"/");

        if(false === $pos){
            // /home
            $controller = $pathInfo;
            $action = "Index";
        }else{
            // /home/profile
            $controller = substr($pathInfo,0,$pos);
            $action = substr($pathInfo,$pos+1);
        }

        return ['Application\Controllers\\' . ucfirst($controller),$action,[]];
    }
}

class ConnPool
{
    public function addMysqlConnectionPool($mysqlSetting)
    {
        return function() use($mysqlSetting){
            // All MySQL connections: [4 workers * 2 = 8, 4 workers * 10 = 40]
            $pool1 = new ConnectionPool(
                Setting::get('pool')
                ,
                new CoroutineMySQLConnector,$mysqlSetting);
            $pool1->init();

            $this->addConnectionPool('mysql', $pool1);
        };
    }


    public function addRedisConnectionPool($redisSetting)
    {
        return function() use($redisSetting){


            // All Redis connections: [4 workers * 5 = 20, 4 workers * 20 = 80]
            $pool2 = new ConnectionPool(
                Setting::get('pool')
                ,
                new PhpRedisConnector,$redisSetting);
            $pool2->init();
            $this->addConnectionPool('redis', $pool2);
        };
    }

    public function closeConnectionPools()
    {
        return function () {
            $this->closeConnectionPools();
        };
    }
}

class RuntimeException extends \Exception
{

}

//事件
class Events
{
    use getInstance;

    private $_events = []; //所有事件

    public function setEvents(array $events)
    {
        $this->_events = $events;
    }

    //todo csp 异步
    public function emit(string $event,array $params) :array
    {
        $result = [];

        if(isset($this->_events[$event])) {

            $events = $this->_events[$event];

            foreach($events as $eventCls) {
                $obj = new $eventCls();

                if (!method_exists($obj,'handle')) {
                    throw new RuntimeException("{$event} handle method Not Found");
                }

                //收集结果
                $result[] = $obj->handle($params);
            }
        }

        return $result;
    }
}

//配置
class Setting
{
    public static $ROOT_DIR = ''; //项目路径
    public static $SETTING = []; //服务配置

    public static $app_mysql = false;
    public static $app_redis = false;

    //服务器配置
    public static function server(array $project = [])
    {

        if (!isset($project['project_root'])) {
            print("project root path is not set\n");
            exit;
        }

        self::$ROOT_DIR = $project['project_root'];
        Runtime::$ROOT_PATH = $project['project_root'];

        if (isset($project['server'])) {
            self::$SETTING = $project;
            return;
        }

        if (file_exists(self::$ROOT_DIR . '/.env.php')) {
            $setting = require self::$ROOT_DIR . '/.env.php';


            if(!isset($setting['server'])) {
                print("server config not found\n");
                exit;
            }

            self::$SETTING = $setting;
            return;
        }

        self::$SETTING = [
            'server' => [
                'host' => '',
                'port' => 9521,
                'daemonize'             => false,
                'dispatch_mode'         => 3,
            ]
        ];
    }

    public static function mysql(array $conf)
    {
        if (isset($conf['mysql']) && !empty($conf['mysql'])) {
            self::$app_mysql = true;
        }
        self::$SETTING = array_merge(self::$SETTING,$conf);
    }

    public static function redis(array $conf)
    {
        if (isset($conf['redis']) && !empty($conf['redis'])) {
            self::$app_redis = true;
        }
        self::$SETTING = array_merge(self::$SETTING,$conf);
    }

    //应用的配置
    public static function app($conf)
    {
        if (!empty($conf))  {
            self::$SETTING = array_merge(self::$SETTING,$conf);
        }

    }

    //获取配置选项
    public static function get(string $key = '')
    {

        if (empty($key)) {
            return self::$SETTING;
        }

        $values = explode('.',$key);

        switch (sizeof($values))
        {
            case 1:
                return self::$SETTING[$values[0]];
                break;
            case 2:
                return self::$SETTING[$values[0]][$values[1]];
                break;
        }
    }
}


//侏罗纪
class App
{

    private $_providers = [];

    private $_service = []; //请求过程里的全局对象
    private $_middleware = []; //中间件处理

    //启动初始化
    private $_startInitialize = null;

    private $_prod = false; //目前运行模式

    use ConnectionPoolTrait;

    public function __construct(array $project = [])
    {
        Setting::server($project);

        $this->setProvider([
            ConnPool::class,
        ]);

    }

    public function startInitialize(\Closure $fn)
    {
        $this->_startInitialize = $fn;
    }

    //设置开发模式
    public function prod(bool $mode)
    {
        $this->_prod = $mode;
    }

    public function getMode() :string
    {
        return $this->_prod ? 'prod' : 'dev';
    }

    private $_settingFN = [];
    public function setting(\Closure $fn)
    {
        $this->_settingFN = $fn;
    }

    private $_mysqlConfigFN = [];
    public function mysql(\Closure $fn)
    {
        $this->_mysqlConfigFN = $fn;
    }

    private $_redisConfigFN = [];
    public function redis(\Closure $fn)
    {
        $this->_redisConfigFN = $fn;
    }

    public $_coreRULEs = []; //路由

    private function ruleRework($regEx,$method)
    {
        return substr_replace($regEx,$method,2,0);
    }

    private function baseMethod(string $regEx,\Closure $cb,$method)
    {
        $filing = $this->ruleRework($regEx,"{$method} ");
        $this->_coreRULEs[$filing] = $cb;
    }

    public function GET(string $regEx,\Closure $cb)
    {
        $this->baseMethod($regEx,$cb,'GET');
    }

    public function POST(string $regEx,\Closure $cb)
    {
        $this->baseMethod($regEx,$cb,'POST');
    }

    public function PUT(string $regEx,\Closure $cb)
    {
        $this->baseMethod($regEx,$cb,'PUT');
    }

    public function PATCH(string $regEx,\Closure $cb)
    {
        $this->baseMethod($regEx,$cb,'PATCH');
    }

    public function DELETE(string $regEx,\Closure $cb)
    {
        $this->baseMethod($regEx,$cb,'DELETE');
    }

    //提供者
    private function setProvider(array $providers)
    {
        foreach ($providers as $cls => $provider) {

            if(is_object($provider)) {
                $this->_providers[$cls] = $provider;
                $provider = $cls;
            }else{
                $this->_providers[$provider] = new $provider();
            }

            if (method_exists($this->_providers[$provider],'initialize')) {
                $this->_providers[$provider]->initialize();
            }
        }
    }

    //获取提供者
    private function getProvider(string $providerName)
    {
        return isset($this->_providers[$providerName]) ? $this->_providers[$providerName] : false;
    }

    public function Service(array $obj)
    {
        $this->_service = $obj;
    }

    private $_events = [];

    //事件
    public function events(\Closure $fn)
    {
        $this->_events = $fn();

        $service = Service::instance();
        $events = Events::instance();
        $events->setEvents($this->_events);

        $service->set('Events',$events);
    }

    public function middleware(array $middleware)
    {
        $this->_middleware = $middleware;
        return $this;
    }

    private function getMiddleware(string $key = '')
    {
        return $key ? $this->_middleware[$key] : $this->_middleware;
    }

    //中间件处理
    public function process(array $handle)
    {
        $this->_pipeline = $handle;
    }

    private function getPipeline(string $key = '')
    {
        return $key ? $this->_pipeline[$key] : $this->_pipeline;
    }

    //中间件处理
    private function middlewareProcess(string $type,Request $request,Response $response,$route=null)
    {
        $_pipeline = $this->getPipeline($type);
        $_middleware = $this->getMiddleware();


        $fn = function ($request,$response,&$_pipeline,&$_middleware) {

            array_walk($_pipeline,function(&$value,$key) use($_middleware){

                $value = $_middleware[$value];
            });

            //因为全局共享一个request  response 对象,虽有每一层的修改 都直接产生影响
            array_reduce($_pipeline,function($context, $next) use($request,$response){

                $next($request,$response);
                return $context;
            });

        };

        if(!empty($_middleware) && !empty($_pipeline)) {

            //路由级别
            if($type === 'route') {
                $_handle = [];

                foreach ($_pipeline as $key => $val) {

                    //* 包含了所有路由
                    if($val == '*') {
                        $_handle[] = $key;
                    }

                    if(is_array($val)){

                        //只有这些路由需要执行
                        if(isset($val['only'])) {
                            if(in_array($route->controllerAction,$val['only'])) {
                                $_handle[] = $key;
                            }
                        }

                        //取反
                        if(isset($val['except'])) {

                            if(!in_array($route->controllerAction,$val['except'])) {
                                $_handle[] = $key;
                            }
                        }

                        //手机要执行的 中间件
                        if(in_array($route->controllerAction,$val)) {
                            $_handle[] = $key;
                        }
                    }
                }

                $fn($request,$response,$_handle,$_middleware);

            }else {
                $fn($request,$response,$_pipeline,$_middleware);
            }
        }
    }

    //引擎
    private function engine(callable $dispatch)
    {
        //初始化路由
        $this->setProvider([
            RulesRoute::class => new RulesRoute($this->_coreRULEs),
        ]);


        $service = Service::instance();

        //初始化
        if ($this->_startInitialize) {
            ($this->_startInitialize)();
        }

        //注册全局对象
        foreach($this->_service as $k => $v) {
            $service->set($k,$v());
        }

        //加载配置
        Setting::app(($this->_settingFN)());
        Setting::mysql(['mysql' => ($this->_mysqlConfigFN)()]);
        Setting::redis(['redis' => ($this->_redisConfigFN)()]);

        $http = new Server(Setting::get('server.host'), Setting::get('server.port'));

        $http->set(Setting::get('server'));

        $http->on('Start', function (Server $http) {
            swoole_set_process_name("App Master");
        });

        $http->on('ManagerStart', function (Server $http) {
            swoole_set_process_name("App Manager");
        });

        $http->on('WorkerStart', function (Server $http, int $workerId) {
            swoole_set_process_name("App Worker #{$workerId}");

            if (Setting::$app_mysql) {
                $connPool = $this->getProvider(ConnPool::class);
                $connPool->addMysqlConnectionPool(Setting::get('mysql'))->call($this);
            }

            if (Setting::$app_redis) {
                $connPool = $this->getProvider(ConnPool::class);
                $connPool->addRedisConnectionPool(Setting::get('redis'))->call($this);
            }

        });

        $http->on('WorkerError',function () {
            if(Setting::$app_mysql || Setting::$app_redis) {
                $connPool = $this->getProvider(ConnPool::class);
                $connPool->closeConnectionPools()->call($this);
            }
        });

        $http->on('WorkerStop', function () {
            if(Setting::$app_mysql || Setting::$app_redis) {
                $connPool = $this->getProvider(ConnPool::class);
                $connPool->closeConnectionPools()->call($this);
            }
        });

        $http->on('request',$dispatch);

        \Swoole\Runtime::enableCoroutine(true);
        $http->start();
    }

    private function getConnPool($type = 'mysql')
    {
        return $this->getConnectionPool($type);
    }

    private $_before_fn = null;
    private $_after_fn = null;

    public function before(\Closure $fn) {
        $this->_before_fn = $fn;
    }

    public function after(\Closure $fn)
    {
        $this->_after_fn = $fn;
    }


    private $_consoles = null;

    public function getConsole(int $argc , array $argv)
    {
        $this->_consoles = new Consoles($argc,$argv);
        return $this;
    }

    private function dispatch()
    {
        $_dispatch = function(\Alita\Request $request,\Alita\Response $response)
        {
            $service = Service::instance();

            //默认走目录寻址
            $Router = $this->getProvider(RulesRoute::class);

            $pathInfo = $request->server('request_method') . " " .$request->server('path_info');

            try {

                //            //系统级中间件
                $this->middlewareProcess('system',$request,$response);

                //获取连接池
                if (Setting::$app_mysql) {
                    $mysqlConn = $this->getConnPool('mysql');
                    $mysql = $mysqlConn->borrow();
                    $service->set('mysql', $mysql);
                    $service->set('orm', new ORM());
                }

                if (Setting::$app_redis) {
                    $redisConn = $this->getConnPool('redis');
                    $redis = $redisConn->borrow();
                    $service->set('redis', $redis);
                }

                if (!empty($this->_before_fn)) { ($this->_before_fn)($request,$response); }

                //硬路由
                if (!empty($this->_coreRULEs)) {
                    list($cb,$params) = $Router->getCbParams($pathInfo);

                    if ($cb !== null) {
                        $content = call_user_func_array($cb,array_merge([$request,$response],$params));

                        //todo 后面要重整
                        if (Setting::$app_mysql) {
                            $mysqlConn->return($mysql);
                        }

                        if (Setting::$app_redis) {
                            $redisConn->return($redis);
                        }

                        return $content;
                    }

                    throw new NoFoundRule([
                        'path_info' => $pathInfo,
                        'message' => "{{$pathInfo}} Routing Rules No Found",
                    ]);
                }

                list($controller,$action,$params) = $Router->getControllerActionParams($pathInfo);


                //找不到路由匹配
                if (!$Router->isMatch) {
                    throw new HttpNotFound([
                        'path_info' => $pathInfo,
                        'controller' => $controller,
                        'action' => $action,
                        'params' => implode(',', $params),
                        'message' => "Http Not found",
                    ]);
                }

                if (!class_exists($controller)) {

                    throw new NoFoundController([
                        'path_info' => $pathInfo,
                        'controller' => $controller,
                        'action' => $action,
                        'params' => implode(',', $params),
                        'message' => "{{$controller}} No Found Controller",
                    ]);
                }

                $controllerObj = new $controller();

                if (!method_exists($controllerObj, $action)) {
                    throw new NoFoundAction([
                        'path_info' => $pathInfo,
                        'controller' => $controller,
                        'action' => $action,
                        'params' => implode(',', $params),
                        'message' => "{{$action}} No Found Action",
                    ]);
                }

                //路由级中间件
                $this->middlewareProcess('route',$request,$response,$Router);

                $content = call_user_func_array([
                    $controllerObj,
                    $action
                ],
                    $params
                );

                if (!empty($this->_after_fn)) { ($this->_after_fn)($request,$response); }

                if (Setting::$app_mysql) {
                    $mysqlConn->return($mysql);
                }

                if (Setting::$app_redis) {
                    $redisConn->return($redis);
                }

                return $content;

            }catch(WorkerException $e) { //普通业务中断
                return $e;
            }catch (\Throwable $e) {
                return $e;
            }
        };

        return function (\Swoole\Http\Request $request,\Swoole\Http\Response $response) use($_dispatch)
        {
            //http请求开始
            $_startTime = microtime(true);


            $service = Service::instance();

            $_request = new Request($request);
            $_response = new Response($response,$request);

            $_response->startTime =  $_startTime;

            $service->set('Request',$_request);
            $service->set('Response',$_response);

            //注册全局中间件
            //中间件 兼容函数和对象
            foreach($this->_middleware as $name => &$handle) {
                if(!is_callable($handle)) {
                    //类
                    $handle = function ($request,$response) use($handle){
                        return (new $handle())->handle($request,$response);
                    };
                }
            }

            $_response->end($_dispatch($_request,$_response));

        };
    }

    private function slogan($version)
    {
        print <<<SLOGAN
        \e[38;5;4;1m
     _    _ _ _        
    / \  | (_) |_ __ _ 
   / _ \ | | | __/ _` |
  / ___ \| | | || (_| |
 /_/   \_\_|_|\__\__,_| 

Alita Server {$version} Started :\e[0m


SLOGAN;
    }

    public function Run()
    {

        if ($this->_consoles === null) {

            $this->slogan(AUTHOR::VERSION);
            $this->engine($this->dispatch());

        }else{
            go(function () {
                $this->_consoles->Run();
            });
        }
    }
}

//用户级对象
//外部对象
class Service
{
    private $o = [];

    use getInstance;

    public function get($key)
    {
        return $this->o[$key];
    }

    public function set($key,$val)
    {
        $this->o[$key] = $val;
    }

    public static function __callStatic($name, $arguments)
    {
        return (static::instance()->get($name));
    }
}

class Request
{
    private $container = [];
    private $request = null;
    private $input = [];

    public function __construct(\Swoole\Http\Request $request)
    {
        $this->request = $request;
    }

    private function mergeGP()
    {
        if (empty($this->input)) {
            $_get = $this->request->get ?? [];
            $_post = $this->request->post ?? [];

            $this->input = array_merge($_get,$_post);
        }
    }

    public function input(string $key='',$default=null)
    {
        $this->mergeGP();

        if (!$key) {
            return $this->input;
        }

        return isset($this->input[$key]) ? $this->input[$key] : $default;
    }

    //验证
    public function validation(array $input)
    {

        $this->mergeGP();

        $error = new class() {
            private $_message = '';
            public function setMessage($message)
            {
                $this->_message = $message;
            }
            public function getMessage()
            {
                return $this->_message;
            }
        };

        $_validation = function($key,$rule) use($error) :bool {

            $ops = explode(',',$rule);

            foreach($ops as $op) {
                //指令,参数
                list($order,$factor) = explode(':',$op);

                if ($order == 'require') {
                    //必须
                    if (!isset($this->input[$key])) {
                        $error->setMessage([
                            $key,$rule
                        ]);
                        return false;
                    }
                }else if ($order == 'int') {
                    //判断是数字
                    if (!isset($this->input[$key])) {
                        $error->setMessage([
                            $key,$rule
                        ]);
                        return false;
                    }

                    if (!is_numeric($this->input[$key])) {
                        $error->setMessage([
                            $key,$rule
                        ]);
                        return false;
                    }

                }else if($order == 'gt') {
                    //长度要求
                    if (!isset($this->input[$key])) {
                        $error->setMessage([
                            $key,$rule
                        ]);
                        return false;
                    }

                    if (is_numeric($this->input[$key])) {
                        if (intval($this->input[$key]) <= $factor) {
                            $error->setMessage([
                                $key,$rule
                            ]);
                            return false;
                        }
                    }else {
                        if (mb_strlen($this->input[$key]) <= $factor) {
                            $error->setMessage([
                                $key,$rule
                            ]);
                            return false;
                        }
                    }

                }else if ($order == 'lt') {
                    //字数要求
                    if (!isset($this->input[$key])) {
                        $error->setMessage([
                            $key,$rule
                        ]);
                        return false;
                    }

                    if (is_numeric($this->input[$key])) {
                        if (intval($this->input[$key]) > $factor) {
                            $error->setMessage([
                                $key,$rule
                            ]);
                            return false;
                        }
                    }else {
                        if (mb_strlen($this->input[$key]) > $factor) {
                            $error->setMessage([
                                $key,$rule
                            ]);
                            return false;
                        }
                    }

                }else if ($order{0} == '#' && $order{strlen($order)-1} == "#") {

                    //正则表达式
                    if (!isset($this->input[$key])) {
                        $error->setMessage([
                            $key,$rule
                        ]);
                        return false;
                    }

                    if (!preg_match($order,$this->input[$key])) {
                        $error->setMessage([
                            $key,$rule
                        ]);
                        return false;
                    }

                }else if($order == 'email') {
                    //邮箱
                    if (!isset($this->input[$key])) {
                        $error->setMessage([
                            $key,$rule
                        ]);
                        return false;
                    }

                    if (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/",$this->input[$key])) {
                        $error->setMessage([
                            $key,$rule
                        ]);
                        return false;
                    }

                }else if ($order == 'default') {
                    //默认值
                    if (!isset($this->input[$key])) {
                        $this->input[$key] = $factor;
                    }
                }else {
                    //默认值
                    if (!isset($this->input[$key])) {
                        $this->input[$key] = $order;
                    }
                }
            }

            return true;
        };

        foreach($input as $key => $rule) {
            if (!$_validation->call($this,$key,$rule)) {
                return $error;
            }
        }

        return null;
    }

    //$_SERVER
    public function server(string $key='')
    {
        return $key ? $this->request->server[$key] : $this->request->server;
    }

    //获取头信息
    public function header(string $key = '')
    {
        return $key ? $this->request->header[$key] : $this->request->header;
    }

    //设置中间值
    public function set($key,$val)
    {
        $this->container[$key] = $val;
    }

    //获取中间值
    public function get($key)
    {
        return $this->container[$key];
    }
}

trait getInstance
{
    public static $_instance = null;

    public static function instance()
    {
        if (self::$_instance == null) {
            self::$_instance = new static();
        }
        return self::$_instance;
    }
}

class Response
{

    private $response = null;

    public $startTime = 0; //启动时间

    public function __construct(\Swoole\Http\Response $response,\Swoole\Http\Request $request)
    {
        $this->response = $response;
        $this->request = $request;
    }

    //给错误用
    private function getTraceAsString($file,$line,$message)
    {
        $_message = <<<EOD
#1 : $file : $line
#2 : $message
EOD;
        return $_message;

    }

    private function write($httpCode = 200,$msg)
    {
        Log::print([
            'protocol' => $this->request->server['server_protocol'],
            'client_ip' => $this->request->server['remote_addr'],
            'method' => $this->request->server['request_method'],
            'code' => $httpCode != 200 ? "\e[38;5;1m{$httpCode}\e[0m" : $httpCode,
            'path' => $this->request->server['path_info'],
            'user_agent' => $this->request->header['user-agent'],
            'execution_time' => round(microtime(true) - $this->startTime,3) . ' second',
            'memory_usage' => round(memory_get_usage() / 1024,3) . ' kb',
        ]);

        $this->response->status($httpCode);
        return $this->response->end($msg."\n");
    }

    public function redirect($url,$code=302)
    {
        $this->response->redirect($url,$code);
        throw new WorkerException();
    }

    //中断
    public function abort(array $val)
    {
        $this->end($val);
        throw new WorkerException();
    }

    public function debug($val)
    {
        $this->end(var_export($val,true));
        throw new WorkerException();
    }

    //输出字符串
    public function string(string $val)
    {
        $this->end($val);
        throw new WorkerException();
    }

    //输出json
    public function json(array $val)
    {
        $this->end($val);
        throw new WorkerException();
    }

    //最后输出
    public function end($content = '')
    {
        if (is_array($content)) {

            $this->response->header('Content-type', 'application/json');
            $this->write(200,json_encode($content));

        }elseif (is_string($content)) {
            $this->write(200,$content);

        }elseif($content instanceof WorkerException) {

            return ;

        }elseif($content instanceof NoFoundRule){

            $this->write(404,$content->getMessage());

        }elseif($content instanceof HttpNotFound){
            //404
            $this->write(404,$content->getMessage());

        }elseif($content instanceof NoFoundController) {

            $this->write(404,$content->getMessage());

        }elseif($content instanceof NoFoundAction) {

            $this->write(404,$content->getMessage());

        }elseif($content instanceof \Exception ){

            $message = "\n";
            $message .= $content->getMessage() ."  ";
            $message .= $content->getFile() . "  ";
            $message .= $content->getLine() . "\n\n";
            $message .= $content->getTraceAsString();

            $this->write(500,$message);

        }elseif($content instanceof \Error) {

            $this->write(500,$this->getTraceAsString($content->getFile(),$content->getLine(),$content->getMessage()));

        }elseif (is_object($content)) {

            $struct = var_export($content,true);
            $this->write(200,$struct);

        }else {
            $this->write(200,$content);
        }
    }
}

class BaseController
{
    protected $Request = null;
    protected $Response = null;

    public function __construct()
    {
        $this->Request = Service::Request();
        $this->Response = Service::Response();
    }
}


class BaseModel
{
    private $db = null;

    public function __construct()
    {
        static::initialize();
    }

    protected function initialize()
    {
        if (Setting::$app_mysql) {
            $this->db = Service::orm()->setConnect(Service::mysql());
        }
    }

    protected function db()
    {
        return $this->db;
    }
}

interface Console
{
    public function initialize(...$params);
    public function handle();
};

class Consoles
{

    private $_type = '';
    private $_do = '';
    private $_params = [];

    public function __construct($argc,$argv)
    {
        //[type:do][params array]
        list($this->_type,$this->_do) = explode(':',$argv[1]);
        $this->_params = array_slice($argv,2);

    }

    private $_redisPool = null;
    private function redisPool()
    {
        $pool = new ConnectionPool(
            Setting::get('pool')
            ,

            new PhpRedisConnector,Setting::get('redis'));
        $pool->init();

        $this->_redisPool = $pool;

        $connection = $this->_redisPool->borrow();

        $service = Service::instance();

        //模型需要
        $service->set('redis', $connection);

        defer(function () {
            $this->_redisPool->close();
        });
    }

    //连接池

    private $_mysqlPool = null;

    private function mysqlPool()
    {
        $pool = new ConnectionPool(
            Setting::get('pool')
            ,
            new CoroutineMySQLConnector,Setting::get('mysql')
        );

        $pool->init();

        $this->_mysqlPool = $pool;

        $connection = $this->_mysqlPool->borrow();

        $service = Service::instance();

        //模型需要
        $service->set('mysql', $connection);
        $service->set('orm', new ORM());

        defer(function () {
            $this->_mysqlPool->close();
        });

    }

    //任务
    private function service($do,...$params)
    {
        $do = "Application\Consoles\\" . ucfirst($do);

        $service = new $do;
        $service->initialize($params);
        $service->handle();


        $service = Service::instance();

        if (Setting::$app_mysql) {
            $this->_mysqlPool->return($service->get('mysql'));
        }

        if (Setting::$app_redis) {
            $this->_redisPool->return($service->get('redis'));
        }

    }

    public function Run()
    {
        //初始化连接池
        if (Setting::$app_mysql) {
            $this->mysqlPool();
        }

        if (Setting::$app_redis) {
            $this->redisPool();
        }

        try {
            switch ($this->_type)
            {
                case "service": $this->service($this->_do,$this->_params);
            }
        }
        catch (\Throwable $e)
        {
            //todo 完善输出
            print $e;
        }
    }
}

//项目运行的所有信息
class Runtime
{
    //项目根路径
    public static $ROOT_PATH = '';
}