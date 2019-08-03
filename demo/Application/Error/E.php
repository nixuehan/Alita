<?php
namespace Application\Error;

class E
{
    public const SUCCESS = 200;
    public const FAIL  = 300;
    public const INVALID_PARAMS = 400;
    public const ERROR = 500;

    public const ERROR_AUTH_CHECK_TOKEN_TIMEOUT = 20001;
    public const ERROR_AUTH_TOKEN = 20002;

    private static $_msg = [
        200 => 'ok',
        300 => 'fail',
        400 => '请求参数错误',
        500 => 'fail',
        20001 => 'token超时',
        20002 => '鉴权错误',
    ];

    public static function getMessage(int $code) :string
    {
        return self::$_msg[$code];
    }
}