<?php


namespace pms\bear;


trait ClintTrait
{

    /**
     * 判断链接
     * @return bool
     */
    public function isConnected()
    {
        return $this->isConnected;
    }

    /**
     * 发送数据
     * @param $data
     */
    public function send(array $data)
    {
        $data['f'] = $data['f'] ?? strtolower(SERVICE_NAME);
        return $this->swoole_client->send($this->encode($data));
    }

    /**
     * 编码
     * @param array $data
     * @return string
     */
    private function encode(array $data): string
    {
        $msg_normal = \pms\Serialize::pack($data);
        $msg_length = pack("N", strlen($msg_normal)) . $msg_normal;
        return $msg_length;
    }

    /**
     * 解码
     * @param $string
     */
    private function decode($data): array
    {
        $length = unpack("N", $data)[1];
        $msg = substr($data, -$length);
        return \pms\Serialize::unpack($msg);
    }


    public function ask_recv($server, $router, $data)
    {
        return $this->send_recv([
            's' => $server,
            'r' => $router,
            'd' => $data
        ]);
    }

    /**
     * 发送并接受返回
     * @param $data
     */
    public function send_recv($data)
    {

        $re = $this->send($data);
        if (!$re) {
            return $re;
        }
        return $this->recv();
    }


    /**
     * 请求和返回,自动加秘钥
     * @param $sername
     * @param $router
     * @param $data
     * @return mixed
     */
    public function request_return($sername, $router, $data)
    {
        return $this->send_recv([
            's' => $sername,
            'r' => $router,
            'accessKey' => \pms\get_access(\pms\get_env(strtoupper($sername) . '_APP_SECRET_KEY'), $data, strtolower(SERVICE_NAME)),
            'd' => $data,
        ]);
    }


    /**
     * 发送一个请求
     * @param $router
     * @param $data
     * @return bool
     */
    public function send_ask($server, $router, $data)
    {
        return $this->send([
            's' => $server,
            'r' => $router,
            'd' => $data
        ]);
    }


}