<?php

namespace pms;


/**
 * task进程事件
 * Class Task
 * @package pms
 */
class Task extends Base
{
    protected $name='Task';

    /**
     * 在task_worker进程内被调用
     * @param \Swoole\Server $server
     * @param int $task_id
     * @param int $src_worker_id
     * @param mixed $data
     */
    public function onTask(\Swoole\Server $server, int $task_id, int $src_worker_id, $data)
    {
        output($data, 'onTask');
        $this->eventsManager->fire($this->name . ':onTask', $this, [$task_id, $src_worker_id, $data]);
        if ($data == 'codeUpdata') {
            $this->codeUpdata();
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
        output('onPipeMessage in task:');
        $this->eventsManager->fire($this->name.':onPipeMessage', $this, [$src_worker_id, $message]);

    }

    /**
     * 此事件在Task进程启动时发生。
     *
     * @param \Swoole\Server $server
     * @param int $worker_id
     */
    public function onWorkerStart(\Swoole\Server $server, int $worker_id)
    {
        output($worker_id, 'onWorkerStart in task');
        $this->eventsManager->fire($this->name.':onWorkerStart', $this, $worker_id);
    }

    /**
     * task进程发生异常后会在Manager进程内回调此函数。
     * @param \Swoole\Server $server
     * @param int $worker_id 是异常进程的编号
     * @param int $worker_pid 异常进程的ID
     * @param int $exit_code 退出的状态码，范围是 1 ～255
     * @param int $signal 进程退出的信号
     */
    public function onWorkerError(\Swoole\Server $server, int $worker_id, int $worker_pid, int $exit_code, int $signal)
    {
        output('task - onWorkerError');
        return false;
    }


    /**
     * 重新加载
     * @param $dir
     */
    public function codeUpdata()
    {
        $array = $this->dConfig->codeUpdata;
        output(ROOT_DIR, 'codeUpdata');
        foreach ($array as $dir) {
            $this->codeUpdateCall(ROOT_DIR . $dir);
        }
        output(ROOT_DIR, 'codeUpdata2');
        $this->swoole_server->finish('codeUpdata');
    }

    /**
     * 更新代码的执行部分
     * @param $timer_id
     * @param $dir
     */
    private function codeUpdateCall($dir)
    {
        static $last_mtime = START_TIME;
        // recursive traversal directory
        $dir_iterator = new \RecursiveDirectoryIterator($dir);
        $iterator = new \RecursiveIteratorIterator($dir_iterator);
        foreach ($iterator as $file) {
            if (substr($file, -1) != '.') {
                if ($file->getExtension() == 'php') {
                    // 只检查php文件
                    // 检查时间
                    $getMTime = $file->getMTime();
                    if ($last_mtime < $getMTime) {
                        $last_mtime = time();
                        echo $file . " ---|lasttime : " . date('Y-m-d H:i:s', $last_mtime) . "and getMTime: " . date('Y-m-d H:i:s', $getMTime) . " update and reload \n";
                        echo "关闭系统!自动重启!";
                        $this->swoole_server->shutdown();
                        break;
                    }
                }
            }
        }
    }


}