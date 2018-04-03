<?php

namespace pms\bear;

use Phalcon\Events\ManagerInterface;

/**
 * 客户端
 * Class Client
 * @property \swoole_client $swoole_client
 * @property-read \swoole_server $swoole_server
 * @event connect/receive_true(receive事件的效验版本已进行数据拆分)/error/close/bufferFull/bufferEmpty/beforeSend发送之前
 * @package pms
 */
class Client extends \pms\Base
{
    public $swoole_client;
    protected $swoole_server;
    private $server_ip;
    private $server_port;
    private $option = [
        'open_eof_check' => true, //打开EOF检测
        'package_eof' => PACKAGE_EOF, //设置EOF
    ];
    protected $name = 'Client';

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
     * 判断链接
     * @return bool
     */
    public function isConnected()
    {
        return $this->swoole_client->isConnected();
    }


    /**
     * 开始,链接服务器
     */
    public function start($timeout =3)
    {
        if (!$this->swoole_client->isConnected()) {
            return $this->swoole_client->connect($this->server_ip, $this->server_port,$timeout);
        }
        return true;

    }

    public function on($obj)
    {
        $this->eventsManager->attach($this->name, $obj);
    }

    /**
     * 获取一个swoole 客户端
     */
    private function get_swoole_client()
    {
        output('get_swoole_client');
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
     * 发送数据
     * @param $data
     */
    public function send($data)
    {
        $this->eventsManager->fire($this->name . ":beforeSend", $this, $data);
        $this->swoole_client->send($this->encode($data));
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
     * 发送并接受返回
     * @param $data
     */
    public function send_recv($data)
    {
        $this->send($data);
        $string = $this->swoole_client->recv();
        output($string,'send_recv');
        output($this->swoole_client->errCode,'send_recv_e');
        return $this->decode($string);
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
     * 链接成功
     * @param \swoole_client $client
     */
    public function connect(\swoole_client $client)
    {
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
        //output('内容就不展示了', 'client_receive_true');
        $data_arr = explode(PACKAGE_EOF, rtrim($data, PACKAGE_EOF));
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
       // output('这是内容', 'client_receive');
        $this->eventsManager->fire($this->name . ":receive", $this, $data);
    }

    /**
     * 链接出错的
     * @param \swoole_client $client
     */
    public function error(\swoole_client $client)
    {
        output('client error');
        $this->eventsManager->fire($this->name . ":error", $this, $client);
    }

    /**
     * 当链接关闭
     * @param \swoole_client $client
     */
    public function close(\swoole_client $client)
    {
        output('client server close');
        $this->eventsManager->fire($this->name . ":close", $this, $client);
    }

}