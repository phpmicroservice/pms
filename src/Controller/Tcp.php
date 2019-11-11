<?php

namespace pms\Controller;

/**
 * Tcp服务的控制器基类
 * @property \pms\bear\Counnect $connect
 * @property \Swoole\Server $server
 * @author Dongasai
 */
abstract class Tcp extends \Phalcon\Di\Injectable
{
    protected $connect;
    protected $server;

    public function initialize()
    {
        $params = $this->dispatcher->getParams();
        $this->connect = $params[0];
        $this->server = $params[1];
    }


    /**
     * 获取数据
     * @param type $index
     * @return type
     */
    public function getData($index = null)
    {
        return $this->connect->getContent($index);
    }

    /**
     * 发送到任务
     * @param $name
     * @param $data
     */
    public function runTask($name,$data)
    {
        return $this->server->task([
            'name'=>$name,
            'data'=>$data
        ]);
    }

}
