<?php

namespace pms\Task;
/**
 * 任务基类
 * Class Task
 * @package pms\Task
 * @property-read \Swoole\Server $swoole_server Swoole的服务类
 * @property-read Data $data 任务数据对象
 */
class Task extends \pms\Di\Injectable
{
    protected $swoole_server;
    protected $data;
    protected $task_id;
    protected $src_worker_id;
    private $startTime;


    /**
     * 构造函数
     * @param type $swoole_server
     * @param type $data
     */
    final public function __construct(\Swoole\Server $swoole_server, $data)
    {
        $this->startTime = microtime(true);
        $this->swoole_server = $swoole_server;
        $this->data = $data;
        $this->init();
    }

    /**
     * 初始化函数,需要覆盖
     */
    public function init()
    {
        
    }


    /**
     * 设置任务进程id
     * @param $task_id
     */
    final public function setTaskId($task_id)
    {
        $this->task_id = $task_id;
        $this->data->setTaskId($task_id);
    }

    /**
     * 设置工作进程id
     * @param $src_worker_id
     */
    final public function setWorkId($src_worker_id)
    {
        $this->src_worker_id = $src_worker_id;
        $this->data->setWorkId($src_worker_id);
    }
    

    /**
     * 执行方法
     * @return mixed
     */
    final public function execute()
    {
        $re = $this->run($this->data->getData());
        $endTime = microtime(true);
        $data = $this->data;
        $data->setReturn($re);
        $data->setTime($endTime - $this->startTime);
        return $data;
    }

 

    final  public function finish()
    {
        $this->end($this->data->getData());
    }

    /**
     * 获取任务的数据,并非传给swoole的真是数据
     * @return mixed
     */
    protected function getData()
    {
        return $this->data->getData();
    }

    /**
     * 获取任务的name
     * @return mixed
     */
    protected function getName()
    {
        return $this->data->getName();
    }

}