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
        $events = Service::Events();

        return $events->emit('payment',[
            'orderID' => 'alita2019'
        ]);
    }
}