<?php
/**
 * Created by PhpStorm.
 * User: Dongasai
 * Date: 2018/4/25
 * Time: 14:26
 */

namespace pms\bear;


class ClientSync extends \pms\Base
{

    public $swoole_client;
    public $isConnected = false;
    protected $swoole_server;
    protected $name = 'Client';
    private $server_ip;
    private $server_port;
    private $option = [
        'open_eof_check' => true, //打开EOF检测
        'package_eof' => PACKAGE_EOF, //设置EOF
    ];

    /**
     * 配置初始化
     */
    public function __construct($ip, $port, $timeout = 10)
    {
        $this->server_ip = $ip;
        $this->server_port = $port;

        $this->swoole_client = new \Swoole\Client(SWOOLE_SOCK_TCP);
        $this->swoole_client->set($this->option);
        if (!$this->swoole_client->connect($this->server_ip, $this->server_port, $timeout)) {
            $this->isConnected = true;
            exit("connect failed. Error: {$this->swoole_client->errCode}\n");
        }

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
     * 发送一个请求
     * @param $router
     * @param $data
     * @return bool
     */
    public function send_ask($router, $data)
    {
        return $this->send([
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
        $data['f'] = $data['f'] ?? SERVICE_NAME;
        return $this->swoole_client->send($this->encode($data));
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
     * 发送并接受返回
     * @param $data
     */
    public function send_recv($data)
    {
        $this->send($data);
        return $this->recv();
    }

    /**
     * 请求和返回
     * @param $sername
     * @param $router
     * @param $data
     * @return mixed
     */
    public function request_return($sername, $router, $data)
    {
        return $this->send_recv([
            's' => $sername,
            'r' => '/server/create_array',
            'd' => $data,
            'accessKey' => \get_access(get_env($sername . '_APP_SECRET_KEY'), $data, SERVICE_NAME)
        ]);

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
     * 接收数据
     * @return array
     */
    public function recv()
    {
        $string = $this->swoole_client->recv();
        \pms\Output::debug($string, 'send_recv');
        \pms\Output::debug($this->swoole_client->errCode, 'send_recv_e');
        return $this->decode($string);
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
        \pms\Output::debug($data, 'client_receive' . $this->name);
        $this->eventsManager->fire($this->name . ":receive", $this, $data);
    }


}