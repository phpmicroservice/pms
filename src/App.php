<?php

namespace pms;

use Phalcon\Events\Event;
use Phalcon\Events\ManagerInterface;
use Phalcon\Exception;
use Phalcon\Cli\Dispatcher\Exception as DispatchException;


/**
 * App类,主管应用的产生调度
 */
class App extends Base
{

    protected $name = 'App';
    private $config_init;

    public function init(\Swoole\Server $server)
    {

        if ($this->dConfig->server_reg) {
            # 进行服务注册
            $this->config_init = new Register($server);
            $this->config_init->ping();
        }
        $this->eventsManager->fire($this->name . ":init", $this, $server);

    }


    /**
     * http请求收到
     */
    public function onRequest(\swoole_http_request $request, \swoole_http_response $response)
    {
        output([$request->get, $request->server, $request->post]);

        require ROOT_DIR . '/app/di.php';
        //require ROOT_DIR . '/config/services.php';
        $application = new \Phalcon\Mvc\Application();
        require ROOT_DIR . "/app/modules.php";
        $application->setDI($di);
        try {

            $re = $application->handle($request->server['request_uri']);
            if ($di['response'] instanceof \Phalcon\Http\Response) {
                output([$di['response']->getHeaders(), $di['response']->getStatusCode(),
                    strlen($di['response']->getContent()), $di['response']->getCookies()]);
            }

            $response->status($di['response']->getStatusCode());
            $response->end($di['response']->getContent());
            //$response->header($re->getHeaders());
        } catch (\Exception $e) {
            $response->end($di['response']->getContent());

        }

    }


    /**
     * 产生链接的回调函数
     */
    public function onConnect(\Swoole\Server $server, int $fd, int $reactorId)
    {
        output([$fd, $reactorId], 'connect');
        $this->eventsManager->fire($this->name . ":onConnect", $this, [$fd, $reactorId]);
    }

    /**
     * 数据接收 回调函数
     */
    public function onReceive(\Swoole\Server $server, int $fd, int $reactor_id, string $data)
    {
        $this->eventsManager->fire($this->name . ":onReceive", $this, [$fd, $reactor_id, $data]);
        //output($data, 'onReceive');
        $data_arr = explode(PACKAGE_EOF, rtrim($data, PACKAGE_EOF));
        foreach ($data_arr as $value) {
            $this->receive($server, $fd, $reactor_id, $value);
        }

    }

    /**
     * 数据接受的回调,信息已经处理
     * @param $server
     * @param $fd
     * @param $reactor_id
     * @param $data
     */
    private function receive($server, $fd, $reactor_id, $string)
    {
        $this->eventsManager->fire($this->name . ":receive", $this, [$fd, $reactor_id, $string]);
        $data = $this->decode($string);
        output($data, 'receive');
        $connect = new bear\Counnect($server, $fd, $reactor_id, $data);
        $router = $this->di->get('router');
        $router->handle($connect->getRouter());
        $dispatcher = new \pms\Dispatcher();
        $dispatcher->setDi($this->di);
        $dispatcher->setActionSuffix('');
        $dispatcher->setTaskSuffix('');
        $dispatcher->setConnect($connect);
        $dispatcher->setServer($server);
        $dispatcher->setEventsManager($this->eventsManager);
        output([
            'n' => $router->getNamespaceName(),
            'c' => $router->getControllerName(),
            'a' => $router->getActionName(),
            'm' => $router->getModuleName(),
        ], 'handel');
        $dispatcher->setDefaultNamespace($router->getNamespaceName());
        $dispatcher->setTaskName($router->getControllerName());
        $dispatcher->setActionName($router->getActionName());
        $dispatcher->setModuleName($router->getModuleName());
        $dispatcher->setParams($router->getParams());
        $handle = $dispatcher->dispatch();

    }

    /**
     * 解码
     * @param $string
     */
    private function decode($string): array
    {
        return \swoole_serialize::unpack(rtrim($string, PACKAGE_EOF));
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
        output([$fd, $reactor_id], 'close');
        $this->eventsManager->fire($this->name . ":onClose", $this, [$fd, $reactor_id]);

    }

    /**
     * 编码
     * @param array $data
     * @return string
     */
    private function encode(array $data): string
    {
        return \swoole_serialize::pack($data) . PACKAGE_EOF;
    }
}