<?php

namespace pms\Controller;

/**
 * Tcp服务的控制器基类
 * @property \pms\bear\Counnect $counnect
 * @property \Swoole\Server $server
 * @author Dongasai
 */
abstract class Tcp extends \Phalcon\Di\Injectable
{
    protected $counnect;
    protected $server;
    
    use ControllerTrait;

    /**
     * 初始化,因为phalcon的cli调度器对控制器的实例化采用的时共享模式,每个控制器只会实例化一次,然后在
     */
    public function initialize()
    {
        $params = $this->dispatcher->getParams();
        $this->counnect = $params[0];
        $this->server = $params[1];
    }

    /**
     * 在进行完了数据绑定之后,进行映射
     */
    public function afterBinding(\Phalcon\Cli\Dispatcher $dispatcher)
    {

    }


    /**
     * 获取数据
     * @param type $index
     * @return type
     */
    public function getData($index = null)
    {
        return $this->counnect->getContent($index);
    }

    /**
     * 发送到任务
     * @param $name
     * @param $data
     */
    public function runTask($name, $data)
    {
        return $this->server->task([
            'name' => $name,
            'data' => $data
        ]);
    }

}
