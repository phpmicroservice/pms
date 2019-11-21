<?php

namespace pms\bear;

use Phalcon\Mvc\Router;
use pms\Serialize\SerializeTrait;
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
class WsCounnect implements CounnectInterface
{
    use CounnectTrait;
    use SerializeTrait;
    public $swoole_server;
    protected $name = 'WsCounnect';
    private $request;
    private $frame;
    private $data;
    private $router;
    private $fd;
    private $interference;

    public function __construct(\Swoole\WebSocket\Server $server, int $fd, $data)
    {
//        echo "创建一个链接对象 \n";
        $this->swoole_server = $server;
        $this->fd = $fd;
        if (!empty($data)) {
            $this->data = $data;
            $this->request = $this->data['d'];
        }
        $this->cache = \Phalcon\Di\FactoryDefault\Cli::getDefault()->getShared('cache');

        $this->analysisRouter();
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
        return $this->swoole_server->push($this->fd, $this->encode1($data));
    }


    /**
     * 销毁一个链接对象
     */
    public function __destruct()
    {
        \pms\Output::debug('销毁一个链接对象');
    }
}