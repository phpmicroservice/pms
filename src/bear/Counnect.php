<?php

namespace pms\bear;

/**
 * TCP 链接对象
 * Class Counnect
 * @property \swoole_server $swoole_server
 * @package pms
 */
class Counnect
{
    public $swoole_server;
    public $request;
    protected $name = 'Counnect';
    private $fd;
    private $reactor_id;

    public function __construct(\Swoole\Server $server, int $fd, int $reactor_id, array $data)
    {
//        echo "创建一个链接对象 \n";
        $this->swoole_server = $server;
        $this->fd = $fd;
        if (!empty($data)) {
            $this->data = $data;
            $this->request= $this->data['d'];
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
    public function getData($index = null)
    {
        if ($index) {
            return $this->data[$index] ?? null;
        }
        return $this->data;
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
     * 想客户端发送数据
     * @param array $data
     */
    public function send(array $data)
    {
        
        return $this->swoole_server->send($this->fd, $this->encode($data));
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