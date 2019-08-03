<?php
namespace Application\Error;

class E
{
    public const SUCCESS = 200;
    public const FAIL  = 300;

    private static $_msg = [
        200 => 'ok',
        300 => 'fail',
    ];

    public static function getMessage(int $code) :string
    {
        return self::$_msg[$code];
    }
}