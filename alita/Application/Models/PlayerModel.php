<?php
namespace Application\Models;

class PlayerModel extends Model
{

    private function table()
    {
        return 'player';
    }

    public function getByPlayerID(int $id)
    {
        return $this->db()->table($this->table())
            ->where('player_id = ?',[$id])
            ->first();
    }
}