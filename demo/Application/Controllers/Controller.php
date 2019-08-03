<?php
namespace Application\Controllers;

use Application\Error\E;

class Controller extends \Alita\BaseController
{
    protected function output($code,$data='') :array
    {
        return [
            'code' => $code,
            'msg' => E::getMessage($code),
            'data' => $data
        ];
    }
}
