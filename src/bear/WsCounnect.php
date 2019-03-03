<?php

namespace pms\bear;

use Phalcon\Mvc\Router;

/**
 * Ws链接对象
 * Class WsCounnect
 * @property \Swoole\WebSocket\Server $swoole_server
 * @property \Phalcon\Mvc\Router $router
 * @package pms\bear
 */
class WsCounnect
{
    public $swoole_server;
    protected $name = 'WsCounnect';
    private $request;
    private $frame;
    private $data;
    private $router;
    private $fd;

    public function __construct(\Swoole\WebSocket\Server $server, int $fd, $data)
    {
//        echo "创建一个链接对象 \n";
        $this->swoole_server = $server;
        $this->fd = $fd;


        if (!empty($data)) {
            $this->data = $this->decode($data);

        }
        $this->analysisRouter();
    }

    /**
     * 解码
     * @param $string
     */
    private function decode($msg)
    {
        return \pms\Serialize::unpack($msg);
    }

    private function analysisRouter()
    {
        $this->router = \Phalcon\Di::getDefault()->get('router2');
        $this->router->handle($this->data[ROUTER_INDEX] ?? '');
    }

    /**
     * 获取 $frame
     * @param $frame
     */
    public function getFrame()
    {
        return $this->frame;
    }

    /**
     * 设置 $frame
     * @param $frame
     */
    public function setFrame($frame)
    {
        $this->frame = $frame;
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function __get($name)
    {
        return $this->request[$name] ?? null;
    }

    /**
     * 获取fd_id
     */
    public function getFd()
    {
        return $this->fd;
    }

    /**
     * 获取数据
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * 获取请求信息
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * 设置请求信息
     * @param $request
     * @return mixed
     */
    public function setRequest(\Swoole\Http\Request $request)
    {
        $this->request = $request;
    }

    /**
     * 想客户端发送数据
     * @param array $data
     */
    public function send($data)
    {
        return $this->swoole_server->push($this->fd, $this->encode($data));
    }

    /**
     * 编码
     * @param array $data
     * @return string
     */
    private function encode(array $data): string
    {
        $msg_normal = \pms\Serialize::pack($data);
        return $msg_normal;
    }

    /**
     * 获取路由
     * @return mixed
     */
    public function getRouter($model = 'cli'): array
    {
        if ($model == 'cli') {
            return [
                'module' => $this->router->getModuleName(),
                'task' => $this->router->getControllerName(),
                'action' => $this->router->getActionName()
            ];
        } else {
            return [
                'module' => $this->router->getModuleName(),
                'controller' => $this->router->getControllerName(),
                'action' => $this->router->getActionName()
            ];
        }

    }


    /**
     * 销毁一个链接对象
     */
    public function __destruct()
    {
        \pms\Output::debug('销毁一个链接对象');
    }
}