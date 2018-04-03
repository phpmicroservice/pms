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
        $this->client_ip = get_env('REGISTER_ADDRESS', 'pms_config');
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
        return md5(md5(REGISTER_SECRET_KEY) . md5('config'));
    }


    /**
     * 发送数据
     * @param $data
     */
    public function send($router, $data)
    {
        if ($this->register_client->isConnected()) {
            return $this->register_client->send_ask($router, $data);
        } else {
            $this->register_client->start();
        }

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
//        output($data, 'receive_configinit');
        $error = $data['e'] ?? 0;
        if (!$error) {
            #没有错误 config_init config_md5 config_data
            $this->save($data);
        } else {
            # 出现了错误!
            output([$data], 'error');
        }
    }

    /**
     * 保存
     * @param $data
     */
    private function save($data)
    {
        $type=$data['t'];
        output($data, 'reg_save');
        if($type=='service_reg'){
            $this->reg_status=1;
        }

    }


    /**
     * 配置更新
     */
    public function ping()
    {
        if ($this->register_client->isConnected()) {
            $data = [
                'name' => 'config',
                'host' => APP_HOST_IP,
                'port' => APP_HOST_PORT,
                'k' => $this->get_key()
            ];
            output('ping', 'ping');
            if ($this->reg_status) {
                # 注册完毕进行ping
                $this->register_client->send_ask('service_ping', $data);
            } else {
                # 没有注册完毕,先注册
                $this->register_client->send_ask('service_reg', $data);
            }
        }else{
            $this->register_client->start();
        }
    }


}