<?php

namespace pms\bear;

use Phalcon\Events\ManagerInterface;
use pms\Serialize\SerializeTrait;

/**
 * 客户端 异步,可绑定同步回调函数的
 * Class Client
 * @property \swoole_client $swoole_client
 * @property-read \swoole_server $swoole_server
 * @event connect/receive_true(receive事件的效验版本已进行数据拆分)/error/close/bufferFull/bufferEmpty/beforeSend发送之前
 * @package pms
 */
class Client2 extends \pms\Base
{
    use SerializeTrait;
    public $swoole_client;
    public $isConnected = false;
    protected $swoole_server;
    protected $name = 'Client';
    private $server_ip;
    private $server_port;
    private $option = SD_OPTION;

    /**
     * 配置初始化
     */
    public function __construct(\swoole_server $swoole_server, $ip, $port, $option = [], $name = 'Client')
    {

        static $c_n = 1;
        $c_n++;
        $this->name = $name . $c_n;
        $this->server_ip = $ip;
        $this->server_port = $port;
        $this->swoole_server = $swoole_server;
        $this->option = array_merge($this->option, $option);
        $this->get_swoole_client();
    }

    /**
     * 获取一个swoole 客户端
     */
    private function get_swoole_client()
    {
        \pms\Output::debug('get_swoole_client');
        if ($this->swoole_client instanceof \Swoole\Client) {
        } else {
            $this->swoole_client = new \Swoole\Client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
        }
        $this->swoole_client->set($this->option);

        $this->swoole_client->on("connect", [$this, 'connect']);
        $this->swoole_client->on("receive", [$this, 'receive_true']);
        $this->swoole_client->on("error", [$this, 'error']);
        $this->swoole_client->on("close", [$this, 'close']);
        $this->swoole_client->on("bufferFull", [$this, 'bufferFull']);
        $this->swoole_client->on("bufferEmpty", [$this, 'bufferEmpty']);
    }

    /**
     * 判断链接
     * @return bool
     */
    public function isConnected()
    {
        return $this->isConnected;
    }

    /**
     * 开始,链接服务器
     */
    public function start($timeout = 10)
    {
        if (!$this->isConnected) {
            \pms\Output::debug([$this->isConnected, $this->server_ip, $this->server_port], 'client_start');
            return $this->swoole_client->connect($this->server_ip, $this->server_port, $timeout);
        }
        return true;

    }

    public function on($obj)
    {
        $this->eventsManager->attach($this->name, $obj);
    }

    /**
     * 当缓存区低于最低水位线时触发此事件。
     */
    public function bufferEmpty(\Swoole\Client $client)
    {
        $this->eventsManager->fire($this->name . ":bufferEmpty", $this, $client);
    }

    /**
     * 当缓存区达到最高水位时触发此事件。
     */
    public function bufferFull(\Swoole\Client $client)
    {
        $this->eventsManager->fire($this->name . ":bufferFull", $this, $client);
    }

    /**
     * 设置回调函数,事件监听者
     * @param $callback
     */
    public function attach($callback)
    {
        $this->eventsManager->attach($this->name, $callback);
    }

    /**
     * 发送一个请求
     * @param $router
     * @param $data
     * @return bool
     */
    public function send_ask($server, $router, $data)
    {
        return $this->send([
            's' => $server,
            'r' => $router,
            'd' => $data
        ]);
    }

    /**
     * 发送数据
     * @param $data
     */
    public function send(array $data)
    {
        if (!$this->isConnected) {
            return false;
        } else {
            $data['f'] = $data['f'] ??strtolower(SERVICE_NAME);
            $this->eventsManager->fire($this->name . ":beforeSend", $this, $data);
            return $this->swoole_client->send($this->encode($data));
        }

    }



    /**
     * 链接成功
     * @param \swoole_client $client
     */
    public function connect(\swoole_client $client)
    {
        $this->isConnected = true;
        echo "Client connect \n";
        $this->eventsManager->fire($this->name . ":connect", $this, $client);
    }

    /**
     * 收到值,真实
     * @param \swoole_client $cli
     * @param $data
     */
    public function receive_true(\swoole_client $client, $data)
    {
        $this->eventsManager->fire($this->name . ":receive_true", $this, $data);
        \pms\Output::debug('内容不展示', '客户端收到消息' . $this->name);
        $data_arr =$this->decode($data);
        foreach ($data_arr as $value) {
            $this->receive($value);
        }

    }

    /**
     * 收到值,解码可用的
     * @param $value
     */
    private function receive($value)
    {
        $data = $this->decode($value);
        \pms\Output::debug($data, 'client_receive' . $this->name);
        $this->eventsManager->fire($this->name . ":receive", $this, $data);
    }



    /**
     * 链接出错的
     * @param \swoole_client $client
     */
    public function error(\swoole_client $client)
    {
        \pms\Output::error(['client error', $this->name], 'error');
        $this->eventsManager->fire($this->name . ":error", $this, $client);
    }

    /**
     * 当链接关闭
     * @param \swoole_client $client
     */
    public function close(\swoole_client $client)
    {
        $this->isConnected = false;
        \pms\Output::info('client server close');
        $this->eventsManager->fire($this->name . ":close", $this, $client);
    }

}