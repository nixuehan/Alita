<?php
namespace Application\Models;

class PlayerModel extends Model
{

    private function table()
    {
        return 'player';
    }
    //创建用户
    public function create(array $user) : int
    {
        $user = array_merge($user,[
            'create_time' => time()
        ]);

        $this->db()->table($this->table())->insert($user);

        return $this->db()->insert_id();
    }

    //根据openid查询
    public function getByOpenid(string $openid)
    {
        return $this->db()->table($this->table())
            ->where('openid = ?',[$openid])
            ->first();
    }

    public function getByPlayerID(int $id)
    {
        return $this->db()->table($this->table())
            ->where('player_id = ?',[$id])
            ->first();
    }

    public function updateByPlayerID(int $id,array $value)
    {
        return $this->db()->table($this->table())
            ->update($value)
            ->where('player_id = ?',[$id]);
    }
}