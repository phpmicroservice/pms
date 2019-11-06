<?php

namespace pms;

use Phalcon\Exception;
use pms\bear\WsCounnect;


/**
 * App类,主管应用的产生调度
 */
class App extends Base
{

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
            $wscounnect->send($exception->getMessage());
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
        $wscounnect = new WsCounnect($server, $frame->fd, $frame->data);
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
            $wscounnect->send($exception->getTrace());
        }

    }


    /**
     * http请求收到
     */
    public function onRequest(\swoole_http_request $request, \swoole_http_response $response)
    {

    }


    /**
     * 产生链接的回调函数
     */
    public function onConnect(\Swoole\Server $server, int $fd, int $reactorId)
    {
        $this->eventsManager->fire($this->name . ":onConnect", $this, [$fd, $reactorId]);
    }

    /**
     * 数据接收 回调函数
     */
    public function onReceive(\Swoole\Server $server, int $fd, int $reactor_id, string $data_string)
    {
        $this->eventsManager->fire($this->name . ":onReceive", $this, [$fd, $reactor_id, $data_string]);
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
            $wscounnect->send($exception->getTrace());
        }

    }

    /**
     * 解码
     * @param $string
     */
    private function decode($data): array
    {
        $length = unpack("N", $data)[1];
        $msg = substr($data, -$length);
        return \pms\Serialize::unpack($msg);
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
        if (isset($info['websocket_status'])) {
            $this->wsClose($server, $fd, $reactor_id);
        }
        $this->eventsManager->fire($this->name . ":onClose", $this, [$fd, $reactor_id]);
    }

    /**
     * ws客户端关闭
     */
    private function wsClose($server, int $fd, int $reactor_id)
    {
        $di = \Phalcon\DI\FactoryDefault\Cli::getDefault();
        $di->set('server', $server);

        $wscounnect = new WsCounnect($server, $fd, []);
        $wscounnect->analysisRouter('/close');
        $router = $wscounnect->getRouter();
        $router['params'] = [$wscounnect, $server];
        try {
            $console = new \Phalcon\Cli\Console();
            $console->setDI($di);
            $console->handle($router);
        } catch (Exception $exception) {
            echo $exception->getMessage();
            $wscounnect->send([$exception->getTrace()]);
        }
    }


    /**
     * 编码
     * @param array $data
     * @return string
     */
    private function encode(array $data): string
    {
        $msg_normal = \pms\Serialize::pack($data);
        $msg_length = pack("N", strlen($msg_normal)) . $msg_normal;
        return $msg_length;
    }
}