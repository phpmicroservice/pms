<?php

namespace pms\bear;


/**
 * 客户端,协程
 * Class ClientCoroutine
 * @package pms\bear
 */
class ClientCoroutine
{
    use ClintTrait;

    public $swoole_client;
    public $isConnected = false;
    protected $swoole_server;
    protected $name = 'Client';
    private $server_ip;
    private $server_port;
    private $option = SD_OPTION;

    /**
     * 配置初始化
     */
    public function __construct($ip, $port, $timeout = 10)
    {
        $this->server_ip = $ip;
        $this->server_port = $port;
        \pms\output([$ip, $port], 'ClientCoroutine');
        $this->swoole_client = new \Swoole\Client(SWOOLE_SOCK_TCP);
        $this->swoole_client->set($this->option);
        if (!$this->swoole_client->connect($this->server_ip, $this->server_port, $timeout)) {
            $this->isConnected = true;
            exit("connect failed. Error: {$this->swoole_client->errCode}\n");
        }

    }


    /**
     * 判断链接
     * @return bool
     */
    public function isConnected()
    {
        return $this->isConnected;
    }






    /**
     * 接收数据
     * @return array
     */
    public function recv()
    {
        $string = $this->swoole_client->recv();
        if($string ===false){
            $data2=[
                'e'=>504,
                'm'=>'gateway_timeout'
            ];
            \pms\Output::debug($data2, 'recvs');
            return $data2;
        }else{
            \pms\Output::debug($this->swoole_client->errCode, 'send_recv_e');
            $data2 = $this->decode($string);
            \pms\Output::debug($data2, 'recvs');
            return $data2;
        }

    }



}