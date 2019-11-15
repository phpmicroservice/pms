<?php


namespace pms\Serialize;


trait SerializeTrait
{
    /**
     * 解码
     * @param $string
     */
    private function decode1($msg)
    {
        return \pms\Serialize::unpack($msg);
    }

    /**
     * 解码
     * @param $string
     */
    private static function decode($data_string): array
    {
        if(empty($data_string)){
            return [];
        }
        $length = unpack("N", $data_string)[1];
        $msg = substr($data_string, -$length);
        echo $msg;
        return \pms\Serialize::unpack($msg);
    }


    /**
     * 编码
     * @param array $data
     * @return string
     */
    private function encode1($data): string
    {
        $msg_normal = \pms\Serialize::pack($data);
        return $msg_normal;
    }

    /**
     * 编码
     * @param array $data
     * @return string
     */
    private static function encode(array $data): string
    {
        $msg_normal = \pms\Serialize::pack($data);
        $msg_length = pack("N", strlen($msg_normal)) . $msg_normal;
        return $msg_length;
    }


}