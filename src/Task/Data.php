<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace pms\Task;

/**
 * 任务数据
 * @author dongasai
 */
class Data
{

    private $data;
    private $task_id;
    private $worker_id;
    private $execTime;
    private $return;

    /**
     * 初始化
     * @param \Swoole\Server $server 服务
     * @param type $data task传入数据
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * 获取对象使用名字和数据a
     * @param type $name
     * @param type $data
     */
    public static function get4NameData($name, $data = []): self
    {
        return new self([
            'name' => $name,
            'data' => $data
        ]);
    }

    /**
     * 获取任务数据,非真实数据
     * @return type
     */
    public function getData()
    {
        return $this->data['data'] ? $this->data['data'] : $this->data[1];
    }

    /**
     * 设置进程id
     * @param type $worker_id
     */
    public function setWorkId($worker_id)
    {
        $this->worker_id = $worker_id;
    }

    /**
     * 获取进程id
     * @return type
     */
    public function getWorkerId()
    {
        return $this->worker_id;
    }

    /**
     * 设置任务ID
     * @param type $id
     */
    public function setTaskId($id)
    {
        $this->task_id = $id;
    }

    /**
     * 返回任务id
     * @return type
     */
    public function getTaskId()
    {
        return $this->task_id;
    }

    /**
     * 设置执行时间
     * @param type $time
     */
    public function setTime($time)
    {
        $this->execTime = $time;
    }

    /**
     * 返回task进程的执行时间
     * @return type
     */
    public function getExecTime()
    {
        return $this->execTime;
    }

    /**
     * 设置返回数据(task进程)
     * @param type $re
     */
    public function setReturn($re)
    {
        $this->return = $re;
    }

    /**
     * 返回task进程返回的数据
     * @return type
     */
    public function getReturn()
    {
        return $this->return;
    }

    /**
     * 获取名字
     * @return type
     */
    public function getName(): string
    {
        return $this->data['name'] ? $this->data['name'] : $this->data[0];
    }

    /**
     * 序列化
     * @return type
     */
    public function __sleep()
    {
        return array('data', 'task_id', 'return', 'execTime', 'worker_id');
    }

    /**
     * 字符串转换
     * @return type
     */
    public function __toString(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * 数组转换
     * @return array
     */
    public function toArray(): array
    {
        return array(
            'data' => $this->data,
            'task_id' => $this->task_id,
            'return' => $this->return,
            'execTime' => $this->execTime,
            'worker_id' => $this->worker_id
        );
    }

}
