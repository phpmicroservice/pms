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
        $startTime = microtime(true);
        $re = $this->run();
        $endTime = microtime(true);

        $data = $this->trueData;
        $data['re'] = $re;
        $data['task_id'] = $this->task_id;
        $data['time'] = $endTime - $startTime;
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

}