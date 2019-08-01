<?php
namespace Application\Middleware;

USE Alita\Middleware;
use Alita\Request;
use Alita\Response;

class Cache implements Middleware
{
    public function handle(Request $request,Response $response)
    {
        $request->set("myName","阿丽塔x");
    }
}