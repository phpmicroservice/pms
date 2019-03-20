<?php

namespace pms\bear;

use Phalcon\Mvc\Router;
use function pms\output;
use Swoole\WebSocket\Frame;

/**
 * Ws链接对象
 * Class WsCounnect
 * @property \Swoole\WebSocket\Server $swoole_server
 * @property \Phalcon\Mvc\Router $router
 * @property \Phalcon\Cache\BackendInterface $cache
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
        $this->cache = \Phalcon\Di\FactoryDefault\Cli::getDefault()->getShared('cache');

        $this->analysisRouter();
    }

    /**
     * 打开链接
     */
    public function open()
    {
        $this->resetInterference();
    }

    /**
     * 获取干扰符
     * @return mixed|string|null
     */
    public function getInterference()
    {
        $interference = $this->cache->get('interference' . RUN_UNIQID . $this->fd, 15552000);
        if (empty($interference)) {
            return $this->resetInterference();
        }
        return $interference;


    }

    /**
     * 重置干扰符,保存干扰符关系
     * @return string
     */
    public function resetInterference()
    {
        $interference = uniqid() . mt_rand(11111111, 99999999);
        $this->cache->save('interference' . RUN_UNIQID . $this->fd, $interference, 15552000);
        return $interference;
    }




    /**
     * 解码
     * @param $string
     */
    private function decode($msg)
    {
        return \pms\Serialize::unpack($msg);
    }

    /**
     *
     */
    public function analysisRouter($router = null)
    {
        $this->router = \Phalcon\Di::getDefault()->get('router2');
        if ($router) {
            $this->router->handle($router);
        } else {
            $this->router->handle($this->getRouterString());
        }

    }

    /**
     * 获取路由字符串
     */
    public function getRouterString()
    {
        return $this->data[ROUTER_INDEX] ?? '/';
    }



    /**
     * 获取 $frame
     * @param $frame
     */
    public function getFrame(): Frame
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
     * 获取内容
     */
    public function getContent()
    {
        return $this->data['d'];
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
     * 获取链接标识符
     * @return string
     */
    public function getSid()
    {
        return md5(RUN_UNIQID . $this->getInterference() . $this->fd) . $this->fd;
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
    private function encode($data): string
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