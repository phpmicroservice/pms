<?php

namespace pms\Controller;

/**
 * Tcp服务的控制器基类
 * @property \Swoole\Http\Request $request
 * @property \Swoole\Http\Response $response
 * @author Dongasai
 */
abstract class Http extends \pms\Di\Injectable
{
    protected $request;
    protected $response;
    protected $params;


    use ControllerTrait;

    /**
     * 初始化,因为phalcon的cli调度器对控制器的实例化采用的时共享模式,每个控制器只会实例化一次,然后在
     */
    public function initialize()
    {
        $params = $this->dispatcher->getParams();
        $this->request = $params[0];
        $this->response = $params[1];
        $this->params = $params[2];
    }

    public function getReturnedValue()
    {
        return $this->dispatcher->getReturnedValue();
    }



    /**
     * 获取数据
     * @param type $index
     * @return type
     */
    public function getData()
    {
        return $this->request->getData();
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
