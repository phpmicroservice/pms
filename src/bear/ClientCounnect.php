<?php

namespace pms\bear;

use Phalcon\Mvc\Router;
use pms\Serialize\SerializeTrait;
use function pms\output;
use Swoole\WebSocket\Frame;

/**
 * 客户端链接对象
 * Class ClientCounnect
 * @property \Swoole\Client $swoole_client
 * @property \Phalcon\Mvc\Router $router
 * @property \Phalcon\Cache\BackendInterface $cache
 * @package pms\bear
 */
class ClientCounnect
{

    use SerializeTrait;

    public $swoole_client;
    protected $name = 'ClientCounnect';
    private $request;
    private $frame;
    private $data;
    private $router;
    private $fd;

    public function __construct(\Swoole\Client $client, $data)
    {
//        echo "创建一个链接对象 \n";
        $this->swoole_client = $client;
        $this->fd = $fd;
        if (!empty($data)) {
            $this->data = $data;
            $this->request=$this->getContent();
        }
        $this->cache = \Phalcon\Di\FactoryDefault\Cli::getDefault()->getShared('cache');
    }

    /**
     * 解析路由
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
     * @param $name
     * @return mixed|null
     */
    public function __get($name)
    {
        return $this->request[$name] ?? null;
    }

    /**
     * 获取内容
     */
    public function getContent($index = null)
    {
        if ($index) {
            return $this->data['d'][$index] ?? null;
        }
        return $this->data['d'];
    }

    /**
     * 获取数据
     * @return mixed
     */
    public function getData($index = null)
    {
        if ($index) {
            return $this->data[$index] ?? null;
        }
        return $this->data;
    }

    /**
     * 想客户端发送数据
     * @param array $data
     */
    public function send($data)
    {
        return $this->swoole_client->send($this->encode($data));
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