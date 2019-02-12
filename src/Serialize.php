<?php

namespace pms;

class Serialize
{
    /**
     * 编码
     * @param $value
     * @param string $type
     * @return string
     */
    public static function pack($value, $type = 'Base'): string
    {
        $class = '\pms\\Serialize\\' . $type;
        if (class_exists($class)) {
            return $class::pack($value);
        }
    }

    /**
     * 解码
     * @param $string
     * @param string $type
     */
    public static function unpack($string, $type = 'Base')
    {
        $class = '\pms\\Serialize\\' . $type;
        if (class_exists($class)) {
            return $class::unpack($string);
        }
    }

}