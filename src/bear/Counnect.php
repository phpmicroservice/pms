<?php

namespace pms\bear;

use pms\Serialize\SerializeTrait;

/**
 * TCP 链接对象
 * Class Counnect
 * @property \swoole_server $swoole_server
 * @package pms
 */
class Counnect
{
    use SerializeTrait;
    use CounnectTrait;
    public $swoole_server;
    public $request;
    protected $name = 'Counnect';
    private $fd;
    private $reactor_id;

    public function __construct(\Swoole\Server $server, int $fd, int $reactor_id, array $data)
    {
//        echo "创建一个链接对象 \n";
        $this->swoole_server = $server;
        $this->fd = $fd;
        if (!empty($data)) {
            $this->data = $data;
            $this->request = $this->data['d'];
        }
        $this->cache = \Phalcon\Di\FactoryDefault\Cli::getDefault()->getShared('cache');

        $this->analysisRouter();
    }


    /**
     * @param $name
     * @return mixed|null
     */
    public function __get($name)
    {
        return $this->request[$name] ?? null;
    }


    /**
     * 想客户端发送数据
     * @param array $data
     */
    public function send(array $data)
    {

        return $this->swoole_server->send($this->fd, $this->encode($data));
    }


    /**
     * 销毁一个链接对象
     */
    public function __destruct()
    {
        \pms\Output::debug('销毁一个链接对象');
    }
}