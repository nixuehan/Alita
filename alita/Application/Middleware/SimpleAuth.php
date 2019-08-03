<?php
namespace Application\Middleware;

USE Alita\Middleware;
use Alita\Request;
use Alita\Response;

//鉴权
class SimpleAuth implements Middleware
{
    public function handle(Request $request,Response $response)
    {
        $request->set("player",[
            'player_id' => 99,
            'name' => 'alita',
        ]);
    }
}