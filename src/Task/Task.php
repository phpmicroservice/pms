<?php

namespace pms\Task;
/**
 * 任务基类
 * Class Task
 * @package pms\Task
 */
class Task
{
    protected $swoole_server;
    protected $trueData;
    protected $data;
    protected $task_id;
    protected $src_worker_id;


    public function __construct($swoole_server, $data)
    {
        $this->swoole_server = $swoole_server;
        $this->trueData = $data;
        $this->data = $data['data'] ? $data['data'] : $data[0];
    }

    /**
     * 设置任务进程id
     * @param $task_id
     */
    public function setTaskId($task_id)
    {
        $this->task_id = $task_id;
    }

    /**
     * 设置任务进程id
     * @param $task_id
     */
    public function setWorkId($src_worker_id)
    {
        $this->src_worker_id = $src_worker_id;
    }

    /**
     * 执行方法
     * @return mixed
     */
    final public function execute()
    {
        \Phalcon\Di::getDefault()->get('db')->connect();
        $startTime = microtime(true);
        $re = $this->run();
        $endTime = microtime(true);

        $data = $this->trueData;
        $data['re'] = $re;
        $data['task_id'] = $this->task_id;
        $data['time'] = $endTime - $startTime;
        \Phalcon\Di::getDefault()->get('db')->close();
        return $data;
    }

    final  public function finish()
    {
        $startFinishTime = microtime(true);
        $re = $this->end();
        $endFinishTime = microtime(true);
        $data = $this->trueData;
        $data['re'] = $re;
        $data['task_id'] = $this->task_id;
        $data['time'] = $endFinishTime - $startFinishTime;
        return $data;
    }

    /**
     * 获取任务的数据,并非传给swoole的真是数据
     * @return mixed
     */
    protected function getData()
    {
        return $this->trueData['data']??$this->trueData[1];
    }

    /**
     * 获取任务的name
     * @return mixed
     */
    protected function getName()
    {
        return $this->trueData['name']??$this->trueData[0];
    }

}