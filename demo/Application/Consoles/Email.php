<?php
namespace Application\Consoles;

use Alita\Console;
use Application\Models\Home;

class Email implements Console
{
    public function initialize(...$params)
    {

    }

    //批量发送邮件
    public function handle()
    {
        $home = new Home();
        print_r($home->getPlayer());
    }
}