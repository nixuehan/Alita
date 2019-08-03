<?php
namespace Application\Middleware;

USE Alita\Middleware;
use Alita\Request;
use Alita\Response;
use Alita\Service;
use Application\Error\E;
use Application\Models\PlayerModel;
use Application\Service\Jwt;

//深度鉴权
class DeepAuth implements Middleware
{
    public function handle(Request $request,Response $response)
    {

        $jwtKey = Service::Config()->get('jwt.key');

        $token = $request->header('token');

        if (!$token) {
            $response->abort([
                'code' => E::ERROR_AUTH_TOKEN,
                'msg' => E::getMessage(E::ERROR_AUTH_TOKEN),
                'data' => ''
            ]);
        }

        $jwt = new Jwt($jwtKey);
        $session = $jwt->decode($token);

        if (!$session) {
            $response->abort([
                'code' => E::ERROR_AUTH_TOKEN,
                'msg' => E::getMessage(E::ERROR_AUTH_TOKEN),
                'data' => ''
            ]);
        }

        $playerID = $session['player_id'];

        $playerModel = new PlayerModel();
        $player = $playerModel->getByPlayerID($playerID);

        if (!$player) {
            $response->abort([
                'code' => E::ERROR_AUTH_TOKEN,
                'msg' => E::getMessage(E::ERROR_AUTH_TOKEN),
                'data' => ''
            ]);
        }

        $request->set("player",$player);
    }
}