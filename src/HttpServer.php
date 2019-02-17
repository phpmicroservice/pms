<?php

namespace pms;

require_once 'index.php';

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
class HttpServer extends Base
{
    public $swoole_server;
    public $channel;
    protected $name = 'HttpServer';
    private $app;
    private $logo;# 热更新用
    private $d_option = SD_OPTION;


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
        $this->task = new  Task($this->swoole_server);
        $this->work = new Work($this->swoole_server);
        $this->app = new App($this->swoole_server);
        # 注册进程回调函数
        $this->workCall();
        # 注册链接回调函数
        $this->tcpCall();

    }

    /**
     * 处理进程回调
     */
    private function workCall()
    {
        #任务相关
        $this->swoole_server->on('Task', [$this->task, 'onTask']);
        $this->swoole_server->on('Finish', [$this->work, 'onFinish']);


        # 主进程启动
        $this->swoole_server->on('Start', [$this, 'onStart']);
        # Work/Task进程 启动
        $this->swoole_server->on('WorkerStart', [$this, 'onWorkerStart']);
    }

    /**
     * 处理连接回调
     */
    private function tcpCall()
    {
        # 设置连接回调
        $this->swoole_server->on('Request', [$this->app, 'onRequest']);

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
     * 主进程开始事件
     * @param swoole_server $server
     */
    public function onStart(\Swoole\Server $server)
    {
        echo $this->logo;
        \pms\output('onStart');
        $this->eventsManager->fire($this->name . ':onStart', $this, $server);
    }

    /**
     * 工作进程启动
     * @param \Swoole\Server $server
     * @param int $worker_id
     */
    public function onWorkerStart(\Swoole\Server $server, int $worker_id)
    {

        \pms\output('WorkerStart', 'onWorkerStart');
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
        if (!$this->gCache->get('WKINIT') && !$server->taskworker) {
            \pms\output(133);
            $this->gCache->save('WKINIT', 1);
            # 热更新
            if (\pms\get_envbl('APP_CODEUPDATE', true)) {
                if (\pms\get_envbl('codeUpdata_inotify', false)) {
                    $this->codeUpdata_inotify();
                } else {
                    \swoole_timer_tick(10000, [$this, 'codeUpdata']);
                }
            }

            # 应用初始化
            $this->app->init($server, $worker_id);

        }

    }

    /**
     * 代码热更新inotify 版本
     * @param $dir
     */
    public function codeUpdata_inotify()
    {


        $array = $this->dConfig->codeUpdata;
        \pms\output(ROOT_DIR, 'codeUpdata');

        // 初始化inotify句柄
        $this->inotify_fd = inotify_init();
        // 设置为非阻塞
        stream_set_blocking($this->inotify_fd, 0);


        foreach ($array as $dir) {
            $this->codeUpdateCall(ROOT_DIR . $dir);
        }
        //加入到swoole的事件循环中
        $re = swoole_event_add($this->inotify_fd, [$this, 'inotify_reload']);
        \pms\output($re, 230);
    }

    /**
     * 更新代码的执行部分
     * @param $timer_id
     * @param $dir
     */
    private function codeUpdateCall($dir)
    {
        // 监控的目录，默认是Applications
        $monitor_dir = realpath($dir);

        // 递归遍历目录里面的文件
        $dir_iterator = new \RecursiveDirectoryIterator($monitor_dir);
        $iterator = new \RecursiveIteratorIterator($dir_iterator);
        foreach ($iterator as $file) {
            // 只监控php文件
            if (pathinfo($file, PATHINFO_EXTENSION) != 'php') {
                continue;
            }
            // 把文件加入inotify监控，这里只监控了IN_MODIFY文件更新事件
            $wd = inotify_add_watch($this->inotify_fd, $file, IN_MODIFY);
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

    public function inotify_reload()
    {
        $events = inotify_read($this->inotify_fd);
        if ($events) {
            foreach ($events as $event) {
                echo "inotify Event :" . var_export($event, 1) . "\n";
                echo "关闭系统!自动重启!";
                $this->swoole_server->shutdown();
            }
        }
    }

    /**
     * 代码热更新
     * @param $dir
     */
    public function codeUpdata()
    {
        $this->swoole_server->task('codeUpdata');
    }


}