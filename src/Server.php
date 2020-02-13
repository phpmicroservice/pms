<?php

namespace pms;

require_once 'index.php';

use Swoole\Table;

/**
 * Class Base
 * @property \Phalcon\Cache\BackendInterface $cache
 * @property \Phalcon\Cache\BackendInterface $gCache
 * @property \Phalcon\Config $config
 * @property \Phalcon\Config $dConfig
 * @property \Swoole\Server $swoole_server
 * @property App $app
 * @package pms
 */
class Server extends Base
{

    protected $swoole_server;
    protected $name = 'Server';
    protected $type = 'tcp';
    public $channel;
    public $task;
    public $work;
    public $app;
    protected $inotify_fd; # 热更新用
    protected $logo;
    protected $d_option = SD_OPTION;
    private $workerStartTime=0;

    public function __construct($server)
    {
//       $this->logo = require 'logo.php';
         $this->swoole_server = $server;
         $di = \Phalcon\Di\FactoryDefault\Cli::getDefault();
         $di->set('service', $this);
    }

    public function getWorkerStartTime()
    {
        return $this->workerStartTime;
    }


    protected function callNumMulriple($mulriple=2)
    {
        $cpu = \swoole_cpu_num();
        if ($cpu <= 2) {
            $cpu = 4;
        }
        return $cpu * $mulriple;
    }

    /**
     * 处理进程回调
     */
    protected function workCall()
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
     * 创建表格
     */
    protected function createTable()
    {
        # 这是通过swoole表格功能做一个简单的表,他们的具有原子性的
        $this->swoole_server->default_table = new \Swoole\Table(1024, 0.2); #1024行
        $this->swoole_server->default_table->column('data', Table::TYPE_INT, 4);
        $this->swoole_server->default_table->create();
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
     *
     * 此事件在Worker进程/Task进程启动时发生。
     * 这里创建的对象可以在进程生命周期内使用
     */
    public function onWorkerStart(\Swoole\Server $server, int $worker_id)
    {
        $this->workerStartTime = time();
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


        if (!$server->taskworker && !$server->default_table->get('server-wkinit', 'data')) {
            $server->default_table->set('server-wkinit', ['data' => 1]);
            $server->default_table->set('server-initworkerid', ['data' => $worker_id]);
            $this->initapp($server, $worker_id);
        }
    }

    /**
     * 初始化APP
     * @param $server
     * @param $worker_id
     */
    protected function initapp(\Swoole\Server $server, $worker_id)
    {
        \pms\output('init');
        # 热更新
        if (\pms\get_envbl('APP_CODEUPDATE', true)) {
             $server->after(3000, [$this, 'codeUpdata']);
        }

        # 应用初始化
        $this->app->init($server, $worker_id);
    }


   
    

    /**
     * 准备判断事件,可以再这个事件内判断应用是否准备完毕
     */
    public function readyJudge($time_id)
    {
        if ($this->swoole_server->default_table->get('server-ready', 'data')) {
            swoole_timer_clear($time_id);
            $this->readySucceed();
        }
        $this->eventsManager->fire($this->name . ':readyJudge', $this, $time_id);
    }

    /**
     * 设置准备好了
     */
    public function setReady()
    {
        $this->swoole_server->default_table->set('server-ready', ['data' => 1]);
    }

    /**
     * 准备完成 事件
     */
    public function readySucceed()
    {
        \pms\Output::debug('readySucceed', 'readySucceed');
        $this->eventsManager->fire($this->name . ':readySucceed', $this, $this->swoole_server);
    }

    /**
     * 代码热更新
     * @param $dir
     */
    public function codeUpdata()
    {
        \pms\output('codeUpdatacodeUpdata');
        $this->toTask(Task\HotReload::class);
    }

    /**
     * 任务投递
     * @param type $name
     * @param type $data
     */
    public function toTask($name,$data=[])
    {
         $this->swoole_server->task(Task\Data::get4NameData($name, $data));
    }

    /**
     * 此事件在Server正常结束时发生
     */
    public function onShutdown(\Swoole\Server $server)
    {
        \pms\output('onShutdown');
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
        \pms\output('onWorkerError');
        if ($server->default_table->get('initapp_worker_id', 'data') === $worker_id) {
            # 初始化进程出错了
            $server->default_table->set('WKINIT', ['data' => 0]);
            $server->stop($worker_id, true);
        }
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
        \pms\output('on ManagerStart');
        $this->eventsManager->fire($this->name . ':onManagerStart', $this, $server);
    }

    /**
     * 当管理进程结束时调用它
     * @param \Swoole\Server $server
     */
    public function onManagerStop(\Swoole\Server $server)
    {
        \pms\output('on onManagerStop');
        $this->eventsManager->fire($this->name . ':onManagerStop', $this, $server);
    }

    /**
     * 设置类型 ['tcp','udp','ws','http']
     * @param $type
     */
    public function setType($type)
    {
        $in = ['tcp', 'udp', 'ws', 'http'];
        if (in_array($type, $in)) {
            $this->type = $type;
        } else {
            throw new \Exception("不合法的类型");
        }
    }
}
