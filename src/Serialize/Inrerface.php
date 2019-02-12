<?php

namespace pms\Serialize;


interface Inrerface
{
    /**
     * 编码
     * @param $value
     * @return mixed
     */
    public static function pack($value): string;

    /**
     * 解码
     * @param $string
     * @return mixed
     */
    public static function unpack($string);

}