<?php
namespace Application\Service;

class Jwt
{
    private $key = '';

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public function encode(array $data)
    {
        $token = array(
            "exp" => time() + 365 * 24 * 60 * 60,  //token 有效时间
            "iat" => time(), //token创建时间
        );

        $token = array_merge($token,$data);

        return \Firebase\JWT\JWT::encode($token, $this->key);
    }

    public function decode($token)
    {
        return (array)\Firebase\JWT\JWT::decode($token, $this->key, array('HS256'));
    }
}