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
    public function __construct($ip, $port, $option = [])
    {
//        $this->logo = require 'logo.php';
        # 加载依赖注入
        require ROOT_DIR . '/app/di.php';
        $this->gCache->save('WKINIT', 0);
        $this->swoole_server = new \Swoole\Http\Server($ip, $port);
        $this->swoole_server->set([
            'enable_static_handler' => true,
            'document_root' => PUBLIC_PATH
        ]);
        $this->swoole_server->set(array_merge($this->d_option, $option));
        parent::__construct($this->swoole_server);

        # 设置运行参数
        $this->swoole_server->set(array_merge($this->d_option, $option));
        $this->task = new Task($this->swoole_server);
        $this->work = new Work($this->swoole_server);
        $this->app = new App($this->swoole_server);
        $this->app->setType('http');
        # 注册进程回调函数
        $this->workCall();
        # 注册链接回调函数
        $this->httpCall();
    }

    /**
     * 处理连接回调
     */
    private function httpCall()
    {
        # 设置连接回调
        $this->swoole_server->on('open', [$this->app, 'onOpen']);
        $this->swoole_server->on('message', [$this->app, 'onMessage']);
        $this->swoole_server->on('close', [$this->app, 'onClose']);
    }

}