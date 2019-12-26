<?php

namespace pms;

use Phalcon\Exception;
use pms\bear\Counnect;
use pms\bear\WsCounnect;
use pms\Controller\Http;
use pms\Serialize\SerializeTrait;


/**
 * App类,主管应用的产生调度
 */
class App extends Base
{
    use SerializeTrait;
    protected $name = 'App';

    public function init(\Swoole\Server $server, $worker_id)
    {
        $this->eventsManager->fire($this->name . ":init", $this, $server);
    }


    /**
     * 链接回调
     * @param \Swoole\WebSocket\Server $server
     * @param $request
     */
    public function onOpen(\Swoole\WebSocket\Server $server, $request)
    {
        $di = \Phalcon\DI\FactoryDefault\Cli::getDefault();
        $di->set('server', $server);
        \pms\Output::output($request, 'open');
        $wscounnect = new WsCounnect($server, $request->fd, []);
        $wscounnect->open();
        $wscounnect->analysisRouter('/open');
        $wscounnect->setRequest($request);
        $router = $wscounnect->getRouter();
        $router['params'] = [$wscounnect, $server];
        try {
            $console = new \Phalcon\Cli\Console();
            $console->setDI($di);
            \pms\Output::output([$router['task'], $router['action']], 'open-params');
            $console->handle($router);
        } catch (Exception $exception) {
            output($exception->getMessage());
            $this->eventsManager->fire($this->name . ":openError", $this, [$counnect, $exception]);

        }

    }


    /**
     * 消息回调
     * @param \Swoole\WebSocket\Server $server
     * @param $frame
     */
    public function onMessage(\Swoole\WebSocket\Server $server, \Swoole\WebSocket\Frame $frame)
    {
        $di = \Phalcon\Di\FactoryDefault\Cli::getDefault();
        $di->set('server', $server);
        $data = $this->decode1($frame->data);
        $wscounnect = new WsCounnect($server, $frame->fd, $data);
        $wscounnect->setFrame($frame);
        \pms\Output::output($frame, 'message2');
        $router = $wscounnect->getRouter();
        $router['params'] = [$wscounnect, $server];
        try {
            $console = new \Phalcon\Cli\Console();
            $console->setDI($di);
            \pms\Output::output([$router['task'], $router['action']], 'message-params');
            $console->handle($router);
        } catch (Exception $exception) {
            output($exception->getMessage());
            $this->eventsManager->fire($this->name . ":messageError", $this, [$counnect, $exception]);
        }

    }


    public function emptyCall()
    {
        
    }


    /**
     * http请求收到
     */
    public function onRequest(\Swoole\Http\Request $request, \Swoole\Http\Response $response)
    {
        /*  
            'request_method' => 'GET',
            'request_uri' => '/demo/ddd',
            'path_info' => '/demo/ddd',
            'request_time' => 1577380040,
            'request_time_float' => 1577380040.455423,
            'server_protocol' => 'HTTP/1.1',
            'server_port' => 8080,
            'remote_port' => 41510,
            'remote_addr' => '172.19.0.1',
            'master_time' => 1577380039,

          */
        $path = $request->server['path_info'];
        $di = \Phalcon\Di\FactoryDefault\Cli::getDefault();
        $router = \Phalcon\Di::getDefault()->get('router');
        if($router instanceof \Phalcon\Cli\Router){
            $router->handle($path);
        }
        $routerarray = [
            'module' => $router->getModuleName(),
            'task' => $router->getTaskName(),
            'action' => $router->getActionName()
        ];
        $routerarray['params'] = [$request, $response];
        try {
            $console = new \Phalcon\Cli\Console();
            $console->setDI($di);
            \pms\Output::output([$routerarray['task'], $routerarray['action']], 'message-params');
            $task = $console->handle($routerarray);
            if($task->getReturnedValue() !== null){
                $response->write($task->getReturnedValue());
            }
            $response->end();
        } catch (Exception $exception) {
            $response->write($exception->getMessage());
            if(APP_DEBUG){
                $response->write($exception->getTraceAsString());
            }
            $response->end();
        }
        
    }


    /**
     * 产生链接的回调函数
     */
    public function onConnect(\Swoole\Server $server, int $fd, int $reactorId)
    {
        $this->eventsManager->fire($this->name . ":onConnect", $this, [$fd, $reactorId]);
        $di = \Phalcon\DI\FactoryDefault\Cli::getDefault();
        $di->set('server', $server);
        $data = $server->getClientInfo($fd,0,true);
        $counnect = new Counnect($server, $fd, $reactorId,$data);
        $counnect->analysisRouter('/connect');
        $router = $counnect->getRouter();
        $router['params'] = [$counnect, $server];
        try {
            $console = new \Phalcon\Cli\Console();
            $console->setDI($di);
            \pms\Output::debug([$router['task'], $router['action']], 'connect-params');
            $console->handle($router);
        } catch (Exception $exception) {
            output($exception->getMessage());
            $this->eventsManager->fire($this->name . ":connectError", $this, [$counnect, $exception]);
//            $counnect->send($exception->getMessage());
        }
    }

    /**
     * 数据接收 回调函数
     */
    public function onReceive(\Swoole\Server $server, int $fd, int $reactor_id, string $data_string)
    {
        $this->eventsManager->fire($this->name . ":onReceive", $this, [$fd, $reactor_id, $data_string]);
        Output::debug($data_string);
        $data = $this->decode($data_string);
        $this->receive($server, $fd, $reactor_id, $data);

    }

    /**
     * 数据接受的回调,信息已经处理
     * @param $server
     * @param $fd
     * @param $reactor_id
     * @param $data
     */
    private function receive($server, $fd, $reactor_id, $data)
    {
        $di = \Phalcon\Di\FactoryDefault\Cli::getDefault();
        $di->set('server', $server);
        \pms\Output::output([$fd,$reactor_id,$data], 'message-data');
        $counnect = new bear\Counnect($server, $fd,$reactor_id ,$data);
        $router = $counnect->getRouter();
        $router['params'] = [$counnect, $server];
        try {
            $console = new \Phalcon\Cli\Console();
            $console->setDI($di);
            \pms\Output::output([
                $router['task'],
                $router['action'],
                get_class($di->getShared('router'))
            ], 'message-params');
            $console->handle($router);
        } catch (Exception $exception) {
            \pms\Output::error([$exception->getMessage(),$exception->getTraceAsString()]);
            $this->eventsManager->fire($this->name . ":receiveError", $this, [$counnect, $exception]);
            #$counnect->send($exception->getTraceAsString());
        }

    }


    /**
     * upd 收到数据
     * @param \Swoole\Server $server
     * @param string $data
     * @param array $client_info
     */
    public function onPacket(\Swoole\Server $server, string $data, array $client_info)
    {
        $this->eventsManager->fire($this->name . ":onPacket", $this, [$data, $client_info]);
    }

    /**
     * 当缓存区达到最高水位时触发此事件。
     * @param \Swoole\Server $serv
     * @param int $fd
     */
    public function onBufferFull(\Swoole\Server $server, int $fd)
    {
        $this->eventsManager->fire($this->name . ":onBufferFull", $this, $fd);
    }

    /**
     * 当缓存区低于最低水位线时触发此事件
     * @param \Swoole\Server $serv
     * @param int $fd
     */
    public function onBufferEmpty(\Swoole\Server $server, int $fd)
    {
        $this->eventsManager->fire($this->name . ":onBufferEmpty", $this, $fd);
    }

    /**
     * 链接关闭 的回调函数
     * @param \Swoole\Server $server
     * @param int $fd
     * @param int $reactorId
     */
    public function onClose(\Swoole\Server $server, int $fd, int $reactor_id)
    {
        $info = $server->getClientInfo($fd);
        if ($server instanceof  \Swoole\WebSocket\Server) {
            $this->wsClose($server, $fd, $reactor_id,$info);
        }else{
            $di = \Phalcon\DI\FactoryDefault\Cli::getDefault();
            $di->set('server', $server);

            $wscounnect = new Counnect($server, $fd, $reactor_id,$info);
            $wscounnect->analysisRouter('/close');
            $router = $wscounnect->getRouter();
            $router['params'] = [$wscounnect, $server];
            try {
                \pms\Output::output([
                    $router['task'],
                    $router['action']
                ], 'close');
                $console = new \Phalcon\Cli\Console();
                $console->setDI($di);
                $console->handle($router);
            } catch (Exception $exception) {
                echo $exception->getMessage();
                $wscounnect->send([$exception->getTrace()]);
            }
        }
        #$this->eventsManager->fire($this->name . ":onClose", $this, [$fd, $reactor_id]);
    }

    /**
     * ws客户端关闭
     */
    private function wsClose($server, int $fd, int $reactor_id,$data)
    {
        $di = \Phalcon\DI\FactoryDefault\Cli::getDefault();
        $di->set('server', $server);

        $wscounnect = new WsCounnect($server, $fd, $data);
        $wscounnect->analysisRouter('/wsclose');
        $router = $wscounnect->getRouter();
        $router['params'] = [$wscounnect, $server];
        try {
            \pms\Output::output([
                $router['task'],
                $router['action']
            ], 'wsclose');
            $console = new \Phalcon\Cli\Console();
            $console->setDI($di);
            $console->handle($router);
        } catch (Exception $exception) {
            output($exception->getMessage());
            $this->eventsManager->fire($this->name . ":closeError", $this, [$counnect, $exception]);

//            $wscounnect->send([$exception->getTrace()]);
        }
    }

    
}