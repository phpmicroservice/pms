<?php

namespace pms;

use Phalcon\Events\ManagerInterface;

/**
 * App类,主管应用的产生调度
 */
class App extends Base
{

    private $config_init;
    protected $name = 'App';

    public function init(\Swoole\Server $server)
    {
        if ($this->dConfig->config_init) {
            # 需要配置初始化
            $this->config_init = new ConfigInit($server);
            $this->config_init->update();
        }
        if ($this->dConfig->server_reg) {
            # 进行服务注册
            $this->config_init = new Register($server);
            $this->config_init->ping();
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
     * 解码
     * @param $string
     */
    private function decode($string): array
    {
        return \swoole_serialize::unpack(rtrim($string, PACKAGE_EOF));
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
        $router = new Router($server, $fd, $reactor_id, $data);
        $router->handle($server, $fd, $reactor_id, $data);
    }

    /**
     * upd 收到数据
     * @param \Swoole\Server $server
     * @param string $data
     * @param array $client_info
     */
    public function onPacket(\Swoole\Server $server, string $data, array $client_info)
    {
        $this->eventsManager->fire($this->name . ":onPacket", $this, [$data,$client_info]);
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
}