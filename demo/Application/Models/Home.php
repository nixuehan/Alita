<?php
namespace Application\Models;

use Alita\BaseModel;

class Home extends BaseModel
{

    public function test()
    {
        return "hello alita ~~";
    }

    public function getPlayer()
    {
        return $this->db->table('player')
            ->where('player_id = ?',[8])
            ->find();
    }
}