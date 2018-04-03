<?php

namespace pms\bear;

/**
 * 链接对象
 * Class Counnect
 * @property \swoole_server $swoole_server
 * @package pms
 */
class Counnect
{
    private $swoole_server;
    private $request;
    private $fd;
    private $reactor_id;
    private $passing = false;
    protected $name = 'Counnect';

    public function __construct(\swoole_server $server, int $fd, int $reactor_id, array $data)
    {
//        echo "创建一个链接对象 \n";
        $this->swoole_server = $server;
        $this->fd = $fd;
        $this->reactor_id = $reactor_id;
        $this->request = $data;
        if (isset($data['p'])) {
            $this->passing = $this->request['p'];
        }
    }

    /**
     * 获取数据
     * @return mixed
     */
    public function getData()
    {

        return $this->request['d'];
    }

    /**
     * 想客户端发送数据
     * @param array $data
     */
    private function send(array $data)
    {
        if ($this->passing) {
            $data['p'] = $this->passing;
        }
        return $this->swoole_server->send($this->fd, \swoole_serialize::pack($data) . PACKAGE_EOF);
    }

    /**
     * 发送一个错误的消息
     * @param $m 错误消息
     * @param array $d 错误数据
     * @param int $e 错误代码
     * @param int $t 类型,路由
     */
    public function send_error($m, $d = [], $e = 1,$t='')
    {
        $data = [
            'm' => $m,
            'd' => $d,
            'e' => $e            ,
            't'=>empty($t)?$this->getRouter():$t
        ];
        return $this->send($data);
    }

    /**
     * 发送一个请求
     * @param $router
     * @param $data
     * @return bool
     */
    public function send_ask($router,$data)
    {
        return $this->send( [
            'r' => $router,
            'd' => $data
        ]);
    }

    /**
     * 发送一个成功
     * @param $m 消息
     * @param array $d 数据
     * @param int $t 类型
     */
    public function send_succee($d = [], $m = '成功',$t='')
    {
        $data = [
            'm' => $m,
            'd' => $d,
            't'=>empty($t)?$this->getRouter():$t
        ];
        return $this->send($data);
    }

    /**
     * 获取路由
     * @return mixed
     */
    public function getRouter()
    {
        return $this->request['r'];
    }

    /**
     * 销毁一个链接对象
     */
    public function __destruct()
    {
//        echo "销毁一个链接对象 \n";
        // TODO: Implement __destruct() method.
    }
}