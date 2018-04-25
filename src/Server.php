<?php

namespace pms;

use Phalcon\Events\ManagerInterface;

/**
 * 服务启动
 * Class Server
 * @property \pms\Work $work;
 * @property \pms\Task $task;
 * @property \pms\App $app;
 * @property \Swoole\Channel $channel;
 * @property \Swoole\Server $swoole_server;
 * @package pms
 */
class Server extends Base
{
    public $swoole_server;
    public $channel;
    private $task;
    private $work;
    private $app;
    private $logo;
    protected $name = 'Server';
    private $d_option = [
        'task_worker_num' => 4,
        'open_eof_split' => true, //打开EOF检测
        'package_eof' => PACKAGE_EOF, //设置EOF
    ];


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
        require ROOT_DIR . '/app/di.php';
        $this->swoole_server = new \Swoole\Server($ip, $port, $mode, $tcp);
        parent::__construct($this->swoole_server);
        # 设置运行参数
        $this->swoole_server->set(array_merge($this->d_option, $option));
        $this->task = new  Task($this->swoole_server);
        $this->work = new Work($this->swoole_server);
        $this->app = new App($this->swoole_server);
        # 注册进程回调函数
        $this->workCall();
        # 注册链接回调函数
        $this->tcpCall();
        $this->swoole_server->wkinit = false;
        $this->swoole_server->channel = new \Swoole\Channel(1024 * 1024 * 128);# 128M
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
        $this->eventsManager->fire($this->name . ':onStart', $this, $server);
    }

    /**
     *
     * 此事件在Worker进程/Task进程启动时发生。
     * 这里创建的对象可以在进程生命周期内使用
     */
    public function onWorkerStart(\Swoole\Server $server, int $worker_id)
    {
        output('WorkerStart', 'onWorkerStart');
        # 加载依赖注入器
        include_once ROOT_DIR . '/app/di.php';

        $this->eventsManager->fire($this->name . ':onWorkerStart', $this, $server);
        if ($server->taskworker) {
            #task
            $this->task->onWorkerStart($server, $worker_id);
        } else {
            $this->work->onWorkerStart($server, $worker_id);
            # 准备判断事件
            \swoole_timer_tick(2000, [$this, 'readyJudge']);
        }

        if (!$server->wkinit && !$server->taskworker) {
            $server->wkinit = true;
            # 热更新
            if (get_envbl('APP_CODEUPDATE', true)) {
                \swoole_timer_tick(10000, [$this, 'codeUpdata']);
            }
            # 应用初始化
            $this->app->init($server, $worker_id);

        }

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
        \pms\Output::debug('readySucceed', 'readySucceed');
        $this->eventsManager->fire($this->name . ':readySucceed', $this, $this->swoole_server);

    }


    /**
     * 重新加载
     * @param $dir
     */
    public function codeUpdata()
    {
        $this->swoole_server->task('codeUpdata');
    }


    /**
     * 此事件在Server正常结束时发生
     */
    public function onShutdown(\Swoole\Server $server)
    {
        output('onShutdown');
        $this->eventsManager->fire($this->name . ':onShutdown', $this, $server);
    }

    /**
     * 当工作进程收到由 sendMessage 发送的管道消息时会触发onPipeMessage事件。
     * @param \Swoole\Server $server
     * @param int $src_worker_id
     * @param mixed $message
     */
    public function onPipeMessage(\Swoole\Server $server, int $src_worker_id, mixed $message)
    {
        $this->eventsManager->fire($this->name . ':onPipeMessage', $this, [$src_worker_id, $message]);
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
        $this->eventsManager->fire($this->name . ':onManagerStart', $this, $server);
    }

    /**
     * 当管理进程结束时调用它
     * @param \Swoole\Server $server
     */
    public function onManagerStop(\Swoole\Server $server)
    {
        output('on onManagerStop');
        $this->eventsManager->fire($this->name . ':onManagerStop', $this, $server);
    }
}