<?php

namespace pms;

use Phalcon\Events\Event;

/**
 * 配置初始化
 * Class Config
 * @property \pms\bear\Client $config_client
 * @package pms
 */
class ConfigInit extends Base
{
    protected $swoole_server;
    private $config_client;
    private $config_ip;
    private $config_port;

    /**
     * 配置初始化
     */
    public function __construct(\Swoole\Server $server)
    {
        $this->config_ip = get_env('CONFIG_ADDRESS', 'pms_config');
        $this->config_port = get_env('CONFIG_PORT', '9502');
        $this->swoole_server = $server;
        $this->config_client = new bear\Client($server, $this->config_ip, $this->config_port);
        $this->config_client->onBind('receive',$this);
        $ConfigInit = $this;

        swoole_timer_tick(5000, function ($timeid) use ($ConfigInit, $server) {
            # 没有初始化完毕
            $ConfigInit->update();
        });

    }


    /**
     * 获取通讯key
     * @return string
     */
    private function get_key()
    {
        return md5(md5(CONFIG_SECRET_KEY) . md5(CONFIG_DATA_KEY) . md5('register'));
    }


    /**
     * 发送数据
     * @param $data
     */
    public function send($router, $data)
    {
        if ($this->config_client->isConnected()) {
            return $this->config_client->send_ask($router,$data);
        } else {
            $this->config_client->start();
        }

    }


    /**
     * 链接成功
     * @param \swoole_client $cli
     */
    public function connect(Event $event, Client $Client)
    {
        echo "config server connect \n";
        $this->update();
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
        $config = $data['d'];
        if ($this->cache->get('config_md5') != \tool\Arr::array_md5($config)) {
            # 存在更新 更新hash
            $this->cache->save('config_md5', \tool\Arr::array_md5($config));
            # 更新配置信息
            $this->cache->save('config_data', $config);
            self::updata_cache();
        }
    }


    /**
     * 配置更新
     */
    public function update()
    {
        output('ConfigInit update ...');
        $this->send(
            'config_acquire',
            [
                'n' => "register",
                'k' => $this->get_key()
            ]
        );
    }

    /**
     * 从缓存更新
     */
    public static function updata_cache()
    {
        //output(' updata_cache ');
        $cache = \Phalcon\Di::getDefault()->get('cache');
        $config = \Phalcon\Di::getDefault()->get('config');
        if ($cache->exists('config_data')) {
            $config_data = $cache->get('config_data', []);
        } else {
            $config_data = [];
        }
        // output($config_data,'config_data');
        $config->merge(new \Phalcon\Config($config_data));
    }


}