<?php

namespace pms;

use Phalcon\Events\Event;

/**
 * 注册服务
 * Class Register
 * @package pms
 */
class Register extends Base
{

    protected $swoole_server;
    private $register_client;
    private $client_ip;
    private $client_port;
    private $reg_status = false;

    /**
     * 配置初始化
     */
    public function __construct(\Swoole\Server $server)
    {
        if (is_string(env_exist(['REGISTER_SECRET_KEY', 'REGISTER_ADDRESS', 'REGISTER_PORT']))) {
            Output::error('缺少必要的环境变量!');
            $server->shutdown();
        }

        $this->client_ip = get_env('REGISTER_ADDRESS', 'pms_register');
        $this->client_port = get_env('REGISTER_PORT', '9502');
        $this->swoole_server = $server;
        $this->register_client = new bear\Client($server, $this->client_ip, $this->client_port);
        $this->register_client->onBind('receive', $this);
        $obj = $this;
        swoole_timer_tick(3000, function ($timeid) use ($obj) {
            # 进行ping
            $obj->ping();
        });
        $this->register_client->start();
    }


    /**
     * 获取通讯key
     * @return string
     */
    private function get_key()
    {
        return md5(md5(get_env('REGISTER_SECRET_KEY')) . md5(strtolower(SERVICE_NAME)));
    }


    /**
     * 发送数据
     * @param $data
     */
    public function send($router, $data)
    {

        return $this->register_client->send_ask($router, $data);
    }


    /**
     * 链接成功
     * @param \swoole_client $cli
     */
    public function connect(Event $event, Client $Client)
    {
        echo "register server connect \n";
        $this->ping();
    }


    /**
     * 收到返回值
     * @param Event $event
     * @param Client $Client
     * @param $value
     * @return int
     */
    public function receive(Event $event, bear\Client $Client, $data)
    {
        Output::debug($data, 'receive_reg');
        $error = $data['e'] ?? 0;
        if (!$error) {
            #没有错误 config_init config_md5 config_data
            $this->save($data);
        } else {
            # 出现了错误!
            Output::error([$data], 'error');
        }
    }

    /**
     * 保存
     * @param $data
     */
    private function save($data)
    {
        $type = $data['t'];
        Output::debug($data, 'reg_save');
        if ($type == '/service/reg') {
            $this->reg_status = 1;
        }

    }


    /**
     * 配置更新
     */
    public function ping()
    {

        $data = [
            'name' => strtolower(SERVICE_NAME),
            'host' => APP_HOST_IP,
            'port' => APP_HOST_PORT,
            'k' => $this->get_key()
        ];
        Output::info('ping', 'ping');
        if ($this->reg_status) {
            # 注册完毕进行ping
            $this->register_client->send_ask('/service/ping', $data);
        } else {
            # 没有注册完毕,先注册
            $this->register_client->send_ask('/service/reg', $data);
        }

    }


}