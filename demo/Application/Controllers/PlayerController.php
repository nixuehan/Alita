<?php
namespace Application\Controllers;

use Alita\Service;
use Application\Error\E;
use Application\Models\Models;
use Application\Models\PlayerModel;
use Application\Service\Jwt;

/**
 * 用户相关
 * @package Application\Controllers
 */
class PlayerController extends Controller
{

    //个人编辑页
    public function profile()
    {
        return "profile";
    }

    //登录
    public function signin($type)
    {
        $code = $this->Request->input('code');

        //微信登录
        if ($type == 'wechat') {

//            $miniprogram = Service::MiniProgram();
//            $session = $miniprogram->auth->session($code);

//            //登录失败
//            if (isset($session['errcode'])) {
//                return $this->output(E::FAIL);
//            }
//
//            $openID = $session['openID'];
//            $player = new PlayerModel();
//            return $player->getByOpenid($openID);
//


            //判断是否存在用户
            //如果存在用户
                //重新生成token返回

            //如果不存在
                //生成用户 返回token


            //微信登录成功

            //插入数据库


            $config = Service::Config();
            $jwt = new Jwt($config->get('jwt.key'));
            $token = $jwt->encode([
                'player_id' => 10
            ]);

//            $player = new PlayerModel();
//            return $player->create([
//                'avatar' => 'asdfasdf',
//                'openid' => 'asdfasdfasdfasdf'
//            ]);


            return $token;
//            return $player->getByOpenid('asdfasdfasdfasdf');
        }


//        $jwt = new Jwt("abcde");
//        $token = $jwt->encode([
//            'player_id' => 99
//        ]);

//        $config = Service::Config();
//
//        return $config->get('jwt.key');
    }

    //基本信息编辑页
    public function base()
    {
        $error = $this->Request->validation([
            'avatar' => 'require',
            'player_name' => 'gt:2',
            'company_id' => 'int', //公司id
            'abbr' => '北海文旅人有限公司', //公司简称
            'title' => 'require',
            'tag_id' => 0, //行业id
            'city' => 'int',//城市
            'phone1' => 'int:11',
            'phone2' => 'int:11',
            'mail' => 'email',
            'wechat' => '',
            'qq' => 'int',
        ]);

        if ($error) {
            $this->Response->debug($error);
        }

        $input = $this->Request->input();

        return $input;
    }
}