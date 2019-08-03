<?php
namespace Application\Consoles;

use Alita\Console;

class Test implements Console
{
    public function initialize(...$params)
    {

    }

    //这里做你想做的事
    public function handle()
    {
        echo "test";
    }
}