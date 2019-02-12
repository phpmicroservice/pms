<?php

namespace pms\Serialize;
/**
 * 主解码器(JSON)
 * Class Base
 * @package pms\Serialize
 */
class Base implements Inrerface
{

    /**
     * 编码
     * @param $value
     * @return string
     */
    public static function pack($value): string
    {
        return json_encode($value);
    }

    /**
     * 解码
     * @param $string
     * @return mixed
     */
    public static function unpack($string)
    {
        return json_decode($string, true);
    }


}