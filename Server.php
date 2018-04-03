<?php

namespace pms;

use Phalcon\Events\ManagerInterface;

/**
 * 服务启动
 * Class Server
 * @property \pms\Work $work;
 * @property \pms\Task $task;
 * @property \pms\App $app;
 * @property \Swoole\Server $swoole_server;
 * @package pms
 */
class Server extends Base
{
    protected $swoole_server;
    private $task;
    private $work;
    private $app;
    private $logo;
    protected $name='Server';


    /**
     * 初始化
     * Server constructor.
     * @param $ip
     * @param $port
     * @param $mode
     * @param $tcp
     * @param array $option
     */
    public function __construct($ip, $port, $mode, $tcp, $option = [])
    {
//        $this->logo = require 'logo.php';
        # 加载依赖注入
        require ROOT_DIR.'/app/di.php';
        $this->swoole_server = new \Swoole\Server($ip, $port, $mode, $tcp);
        parent::__construct( $this->swoole_server);
        # 设置运行参数
        $this->swoole_server->set($option);
        $this->task = new  Task($this->swoole_server);
        $this->work = new Work($this->swoole_server);
        $this->app = new App($this->swoole_server);
        # 注册进程回调函数
        $this->workCall();
        # 注册链接回调函数
        $this->tcpCall();
    }



    /**
     * 启动服务
     */
    public function start()
    {

        $this->eventsManager->fire($this->name . ':beforeStart', $this, $this->swoole_server);
        $this->swoole_server->start();
    }

    /**
     * 处理连接回调
     */
    private function tcpCall()
    {
        # 设置连接回调
        $this->swoole_server->on('Connect', [$this->app, 'onConnect']);
        $this->swoole_server->on('Receive', [$this->app, 'onReceive']);
        $this->swoole_server->on('Packet', [$this->app, 'onPacket']);
        $this->swoole_server->on('Close', [$this->app, 'onClose']);
        $this->swoole_server->on('BufferEmpty', [$this->app, 'onBufferEmpty']);
        $this->swoole_server->on('BufferFull', [$this->app, 'onBufferFull']);
    }

    /**
     * 处理进程回调
     */
    private function workCall()
    {

        $this->swoole_server->on('Task', [$this->task, 'onTask']);
        $this->swoole_server->on('Finish', [$this->work, 'onFinish']);
        # 主进程启动
        $this->swoole_server->on('Start', [$this, 'onStart']);
        # 正常关闭
        $this->swoole_server->on('Shutdown', [$this, 'onShutdown']);
        # Work/Task进程 启动
        $this->swoole_server->on('WorkerStart', [$this, 'onWorkerStart']);
        # work进程停止
        $this->swoole_server->on('WorkerStop', [$this->work, 'onWorkerStop']);
        # work 进程退出
        $this->swoole_server->on('WorkerExit', [$this->work, 'onWorkerExit']);
        # 进程出错 work/task
        $this->swoole_server->on('WorkerError', [$this, 'onWorkerError']);
        # 收到管道消息
        $this->swoole_server->on('PipeMessage', [$this, 'onPipeMessage']);
        # 管理进程开启
        $this->swoole_server->on('ManagerStart', [$this, 'onManagerStart']);
        # 管理进程结束
        $this->swoole_server->on('ManagerStop', [$this, 'onManagerStop']);
    }

    /**
     * 主进程开始事件
     * @param swoole_server $server
     */
    public function onStart(\Swoole\Server $server)
    {
        echo $this->logo;
        output('onStart');
        $this->eventsManager->fire($this->name.':onStart', $this, $server);
    }

    /**
     *
     * 此事件在Worker进程/Task进程启动时发生。
     * 这里创建的对象可以在进程生命周期内使用
     */
    public function onWorkerStart(\Swoole\Server $server, int $worker_id)
    {
        output('WorkerStart','onWorkerStart');
        # 加载依赖注入器
        include_once ROOT_DIR . '/app/di.php';
        # 加载辅助函数库
        include_once ROOT_DIR . '/tool/function.php';

        $this->eventsManager->fire($this->name.':onWorkerStart', $this, $server);
        if ($server->taskworker) {
            #task
            $this->task->onWorkerStart($server, $worker_id);
        } else {
            $this->work->onWorkerStart($server, $worker_id);
        }
        if ($worker_id == 1) {
            # 热更新
            global $last_mtime;
            $last_mtime = time();
            \swoole_timer_tick(3000, [$this, 'codeUpdata'], ROOT_DIR . '/tool/');
            \swoole_timer_tick(3000, [$this, 'codeUpdata'], ROOT_DIR . '/app/');
            \swoole_timer_tick(3000, [$this, 'codeUpdata'], ROOT_DIR . '/pms/');
            \swoole_timer_tick(3000, [$this, 'codeUpdata'], ROOT_DIR . '/start/');
            # 应用初始化
            $this->app->init($server, $worker_id);
        }
        if ($this->dConfig->config_init) {
            # 从缓存更新配置
            \swoole_timer_tick(5000, function () {
                \pms\ConfigInit::updata_cache();
            });
        }

        # 准备判断事件
        \swoole_timer_tick(2000, [$this, 'readyJudge']);
    }


    /**
     * 准备判断事件,可以再这个事件内判断应用是否准备完毕
     */
    public function readyJudge($time_id)
    {
        if ($this->dConfig->ready) {
            swoole_timer_clear($time_id);
            $this->readySucceed();
        }
        $this->eventsManager->fire($this->name . ':readyJudge', $this, $time_id);
    }

    /**
     * 准备完成时间
     */
    public function readySucceed()
    {
        $this->eventsManager->fire($this->name . ':readySucceed', $this, $this->swoole_server);

    }



    /**
     * 重新加载
     * @param $dir
     */
    public function codeUpdata($timer_id,$dir)
    {
        global $last_mtime;
//        output([$last_mtime,$dir],'codeUpdata');
        // recursive traversal directory
        $dir_iterator = new \RecursiveDirectoryIterator($dir);
        $iterator = new \RecursiveIteratorIterator($dir_iterator);
        foreach ($iterator as $file) {
            if (substr($file, -1) != '.') {
                if (substr($file, -3) == 'php') {
                    // 只检查php文件
                    // 检查时间
                    $getMTime = $file->getMTime();
                    if ($last_mtime < $getMTime) {
                        $last_mtime = time();
                        echo $file . " ---|lasttime :$last_mtime and getMTime:$getMTime update and reload \n";
                        echo "关闭系统!自动重启!";
                        $this->swoole_server->shutdown();
                        break;
                    }
                }
            }
        }
    }


    /**
     * 此事件在Server正常结束时发生
     */
    public function onShutdown(\Swoole\Server $server)
    {
        output('onShutdown');
        $this->eventsManager->fire($this->name.':onShutdown', $this, $server);
    }

    /**
     * 当工作进程收到由 sendMessage 发送的管道消息时会触发onPipeMessage事件。
     * @param \Swoole\Server $server
     * @param int $src_worker_id
     * @param mixed $message
     */
    public function onPipeMessage(\Swoole\Server $server, int $src_worker_id, mixed $message)
    {
        $this->eventsManager->fire($this->name.':onPipeMessage', $this, [$src_worker_id, $message]);
        if ($server->taskworker) {
            $this->task->onPipeMessage($server, $src_worker_id, $message);
        } else {
            $this->work->onPipeMessage($server, $src_worker_id, $message);
        }
    }

    /**
     * 当worker/task_worker进程发生异常后会在Manager进程内回调此函数。
     * @param \Swoole\Server $server
     * @param int $worker_id 是异常进程的编号
     * @param int $worker_pid 异常进程的ID
     * @param int $exit_code 退出的状态码，范围是 1 ～255
     * @param int $signal 进程退出的信号
     */
    public function onWorkerError(\Swoole\Server $server, int $worker_id, int $worker_pid, int $exit_code, int $signal)
    {
        return 1;
        if ($server->taskworker) {
            $this->task->onWorkerError($server, $worker_id, $worker_pid, $exit_code, $signal);
        } else {
            $this->work->onWorkerError($server, $worker_id, $worker_pid, $exit_code, $signal);
        }
    }


    /**
     * 当管理进程启动时调用它
     * @param \Swoole\Server $server
     */
    public function onManagerStart(\Swoole\Server $server)
    {
        output('on ManagerStart');
        $this->eventsManager->fire($this->name.':onManagerStart', $this, $server);
    }

    /**
     * 当管理进程结束时调用它
     * @param \Swoole\Server $server
     */
    public function onManagerStop(\Swoole\Server $server)
    {
        output('on onManagerStop');
        $this->eventsManager->fire($this->name.':onManagerStop', $this, $server);
    }
}