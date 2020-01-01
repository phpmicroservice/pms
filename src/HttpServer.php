<?php

namespace pms;

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
class HttpServer extends Server
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
//        $this->logo = require 'logo.php';
        $this->d_option['reactor_num'] = $this->callNumMulriple(get_env('reactor_num_mulriple', 2));
        $this->d_option['worker_num'] = $this->callNumMulriple(get_env('worker_num_mulriple', 2));
        $this->d_option['task_worker_num'] = $this->callNumMulriple(get_env('task_worker_num_mulriple', 4));

        # 加载依赖注入
        if (defined("DI_FILE")) {
            Output::output(DI_FILE, 'DI_FILE');
            include_once DI_FILE;
        } else {
            throw new \Phalcon\Exception("undefined constant DI_FILE");
        }
        $this->swoole_server = new \Swoole\Http\Server($ip, $port, $mode, $tcp);
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
        $this->app->setType('http');
        # 注册进程回调函数
        $this->workCall();
        # 注册链接回调函数
        $this->httpCall();
        $this->createTable();
    }

    /**
     * 处理连接回调
     */
    private function httpCall()
    {
        # 设置连接回调
        $this->swoole_server->on('request', [$this->app, 'onRequest']);
    }

}