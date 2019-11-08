<?php


namespace pms\bear;

/**
 *
 * Trait ClintTrait
 * @property-read  \Swoole\Client $swoole_client
 * @package pms\bear
 */
trait ClintTrait
{

    /**
     * 判断链接
     * @return bool
     */
    public function isConnected()
    {
        return $this->swoole_client->isConnected();
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

    /**
     * 发送一个错误的消息
     * @param $m 错误消息
     * @param array $d 错误数据
     * @param int $e 错误代码
     * @param int $t 类型,路由
     */
    public function send_error($m, $d = [], $e = 1, $t = '')
    {
        $data = [
            'm' => $m,
            'd' => $d,
            'e' => $e,
            't' => empty($t) ? $this->getRouter() : $t
        ];
        return $this->send($data);
    }

    /**
     * 发送一个成功
     * @param $m 消息
     * @param array $d 数据
     * @param int $t 类型
     */
    public function send_succee($d = [], $m = '成功', $t = '')
    {
        $data = [
            'm' => $m,
            'd' => $d,
            'e' => 0,
            't' => empty($t) ? $this->getRouter() : $t
        ];
        return $this->send($data);
    }

}