<?php
namespace Application\Controllers;

use Application\Error\E;

class IndexController extends Controller
{

    public function index()
    {
        return $this->output(E::SUCCESS,'hello alita!');
    }
}