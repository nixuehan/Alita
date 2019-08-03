<?php
namespace Application\Middleware;

USE Alita\Middleware;
use Alita\Request;
use Alita\Response;
use Alita\Service;
use Application\Error\E;
use Application\Models\PlayerModel;
use Application\Service\Jwt;

//简单鉴权
class SimpleAuth implements Middleware
{
    public function handle(Request $request,Response $response)
    {

        $jwtKey = Service::Config()->get('jwt.key');

        $token = $request->header('token');

        if (!$token) {
            return;
        }

        $jwt = new Jwt($jwtKey);
        $session = $jwt->decode($token);

        if ($session) {
            $playerID = $session['player_id'];

            $playerModel = new PlayerModel();
            $player = $playerModel->getByPlayerID($playerID);

            if (!$player) {
                $request->set("player",$player);
                return;
            }
        }

        $request->set("player",[]);
    }
}