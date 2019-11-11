<?php

namespace pms\bear;


use pms\Serialize\SerializeTrait;

/**
 * 客户端,协程
 * Class ClientCoroutine
 * @package pms\bear
 */
class ClientCoroutine
{
    use ClintTrait;
    use SerializeTrait;

    public $swoole_client;
    protected $swoole_server;
    protected $name = 'Client';
    private $server_ip;
    private $server_port;
    private $option = SD_OPTION;
    private $timeout=10;

    /**
     * 配置初始化
     */
    public function __construct($ip, $port, $timeout = 10)
    {
        $this->server_ip = $ip;
        $this->server_port = $port;
        $this->timeout=$timeout;
        \pms\output([$ip, $port], 'ClientCoroutine');
        $this->swoole_client = new \Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
        $this->swoole_client->set($this->option);
        return $this->connect();

    }

    public function connect()
    {
        return $this->swoole_client->connect($this->server_ip, $this->server_port, $this->timeout);
    }


    /**
     * 判断链接
     * @return bool
     */
    public function isConnected()
    {
        return $this->swoole_client->isConnected();
    }


    /**
     * 接收数据
     * @return array
     */
    public function recv()
    {
        $string = $this->swoole_client->recv($this->timeout);
        if ($string === false) {
            $data2 = [
                'e' => 504,
                'm' => 'gateway_timeout'
            ];
            \pms\Output::debug($data2, 'recvs');
            return $data2;
        } else {
            \pms\Output::debug($this->swoole_client->errCode, 'send_recv_e');
            $data2 = $this->decode($string);
            \pms\Output::debug($data2, 'recvs');
            return $data2;
        }

    }


}