<?php

namespace pms;

/**
 * task进程事件
 * Class Task
 * @package pms
 */
class Task extends Base 
{

    protected $name = 'Task';

    /**
     * 在task_worker进程内被调用
     * @param \Swoole\Server $server
     * @param int $task_id
     * @param int $src_worker_id
     * @param mixed $data
     */
    public function onTask(\Swoole\Server $server, int $task_id, int $src_worker_id, $data) {
        \pms\output($data, 'onTask');
        $this->eventsManager->fire($this->name . ':onTask', $this, [$task_id, $src_worker_id, $data]);
        if (is_array($data)) {
            //数组的数据是要进行任务类调用
            $name = $data['name'] ? $data['name'] : $data[0];
            $class_name = $name;
            if (class_exists($class_name)) {
                $handel = new $class_name($server, $data);
                $handel->setTaskId($task_id);
                $handel->setWorkId($src_worker_id);
                return $handel->execute();
            } else {
                throw new Exception('任务类没有找到: '.$class_name, 1);
            }
        }else{
            throw new Exception('任务数据不符合规范 :'.$data, 2);
        }
    }

    /**
     * 当工作进程收到由 sendMessage 发送的管道消息时会触发onPipeMessage事件。
     * @param \Swoole\Server $server
     * @param int $src_worker_id
     * @param mixed $message
     */
    public function onPipeMessage(\Swoole\Server $server, int $src_worker_id, mixed $message) {
        \pms\output('onPipeMessage in task:');
        $this->eventsManager->fire($this->name . ':onPipeMessage', $this, [$src_worker_id, $message]);
    }

    /**
     * 此事件在Task进程启动时发生。
     *
     * @param \Swoole\Server $server
     * @param int $worker_id
     */
    public function onWorkerStart(\Swoole\Server $server, int $worker_id) {
        \pms\output($worker_id, 'onWorkerStart in task');
        $this->eventsManager->fire($this->name . ':onWorkerStart', $this, $worker_id);
    }

    /**
     * task进程发生异常后会在Manager进程内回调此函数。
     * @param \Swoole\Server $server
     * @param int $worker_id 是异常进程的编号
     * @param int $worker_pid 异常进程的ID
     * @param int $exit_code 退出的状态码，范围是 1 ～255
     * @param int $signal 进程退出的信号
     */
    public function onWorkerError(\Swoole\Server $server, int $worker_id, int $worker_pid, int $exit_code, int $signal) {
        \pms\output('task - onWorkerError');
        return false;
    }

   
    
}
