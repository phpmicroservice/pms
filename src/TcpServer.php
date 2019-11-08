<?php

namespace pms;

/**
 * tcp服务
 * Class Server
 * @property \pms\Work $work;
 * @property \pms\Task $task;
 * @property \pms\App $app;
 * @property \Swoole\Channel $channel;
 * @property \Swoole\Server $swoole_server;
 * @package pms
 */
class TcpServer extends Server
{

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
        $this->logo = include "logo.php";
        $this->d_option['reactor_num'] = \swoole_cpu_num() * ($option['reactor_num_mulriple'] ?? 1);
        $this->d_option['worker_num'] = \swoole_cpu_num() * ($option['worker_num_mulriple'] ?? 2);
        $this->d_option['task_worker_num'] = \swoole_cpu_num() * ($option['task_worker_num_mulriple'] ?? 4);
        # 加载依赖注入
        if (defined("DI_FILE")) {
             Output::output(DI_FILE, 'DI_FILE');
            include_once DI_FILE;
        } else {
            throw new \Phalcon\Exception("undefined constant DI_FILE");
        }
        $this->swoole_server = new \Swoole\Server($ip, $port, $mode, $tcp);
        $di = \Phalcon\Di\FactoryDefault\Cli::getDefault();
       
        $di->setShared('server', $this->swoole_server);
        parent::__construct($this->swoole_server);
        $this->d_option= array_merge($this->d_option, $option);
        Output::output($this->d_option, 'd_option');
        # 设置运行参数
        $this->swoole_server->set($this->d_option);
        $this->task = new Task($this->swoole_server);
        $this->work = new Work($this->swoole_server);
        $this->app = new App($this->swoole_server);
        # 注册进程回调函数
        $this->workCall();
        # 注册链接回调函数
        $this->tcpCall();
        $this->createTable();
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

}
