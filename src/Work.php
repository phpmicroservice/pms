<?php

namespace pms;

/**
 * work进程
 * Class Work
 * @package pms
 */
class Work extends Base
{

    protected $name = 'Work';

    /**
     * task_worker进程 在task完成时,触发
     * @param swoole_server $serv
     * @param int $task_id
     * @param mixed $data
     */
    public function onFinish(\Swoole\Server $server, int $task_id, $data)
    {
        output([$task_id, $data], 'onFinish');
        $this->eventsManager->fire($this->name . ':onFinish', $this, [$task_id, $data]);
        if (is_array($data)) {
            //数组的数据是要进行任务类调用
            $name = $data['name'] ? $data['name'] : $data[0];
            $class_name = 'app\\task\\' . ucfirst($name);
            $handel = new $class_name($server, $data);
            $handel->setTaskId($task_id);
            return $handel->finish();
        }

    }

    /**
     * 当工作进程收到由 sendMessage 发送的管道消息时会触发onPipeMessage事件。
     * @param \Swoole\Server $server
     * @param int $src_worker_id
     * @param mixed $message
     */
    public function onPipeMessage(\Swoole\Server $server, int $src_worker_id, mixed $message)
    {
        output([$src_worker_id, $message], 'onPipeMessage');
        $this->eventsManager->fire($this->name . ':onPipeMessage', $this, [$src_worker_id, $message]);
    }


    /**
     * 此事件在Worker进程启动时发生。
     * 这里创建的对象可以在进程生命周期内使用
     * @param \Swoole\Server $server
     * @param int $worker_id
     */
    public function onWorkerStart(\Swoole\Server $server, int $worker_id)
    {
        output($worker_id, 'onWorkerStart in work');
        $this->eventsManager->fire($this->name . ':onWorkerStart', $this, $worker_id);
    }

    /**
     * 当工作进程停止
     * @param \Swoole\Server $server
     * @param int $worker_id
     */
    public function onWorkerStop(\Swoole\Server $server, int $worker_id)
    {
        $this->eventsManager->fire($this->name . ':onWorkerStop', $this, $worker_id);
    }

    /**
     * 当worker出错
     * @param \Swoole\Server $server
     * @param int $worker_id 是异常进程的编号
     * @param int $worker_pid 异常进程的ID
     * @param int $exit_code 退出的状态码，范围是 1 ～255
     * @param int $signal 进程退出的信号
     */
    public function onWorkerError(\Swoole\Server $server, int $worker_id, int $worker_pid, int $exit_code, int $signal)
    {
        output('worker - onWorkerError');
        return 1;
    }

    /**
     * 仅在开启reload_async特性后有效。
     * @param \Swoole\Server $server
     * @param int $worker_id
     */
    public function onWorkerExit(\Swoole\Server $server, int $worker_id)
    {
        $this->eventsManager->fire($this->name . ':onWorkerExit', $this, $worker_id);
    }


}