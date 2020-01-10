<?php

namespace pms\bear;

use Phalcon\Events\ManagerInterface;
use pms\Base;
use pms\Serialize\SerializeTrait;
use function pms\output;

/**
 * 客户端 异步
 * Class Client
 * @property \Swoole\Async\Client $swoole_client
 * @package pms\bear
 */
class Client extends Base
{
    use ClintTrait;
    use SerializeTrait;
    public $swoole_client;
    public $isConnected = false;
    protected $swoole_server;
    protected $name = 'client';
    private $server_ip;
    private $server_port;
    private $option = SD_OPTION;

    /**
     * 配置初始化
     */
    public function __construct($server,string $ip, string $port, $option = [], $name = 'client')
    {
        static $c_n = 1;
        $c_n++;
        $this->swoole_server = $server;
        $this->name = $name . $c_n;
        $this->server_ip = $ip;
        $this->server_port = $port;
        $this->option = $this->option;
        $this->get_swoole_client();
    }

    /**
     * 获取一个swoole 客户端
     */
    private function get_swoole_client()
    {
        \pms\Output::info([$this->server_ip,$this->server_port],'get_swoole_client');
        \pms\Output::debug('get_swoole_client');
       
        if ($this->swoole_client instanceof \Swoole\Async\Client) {
        } else {

            $this->swoole_client = new \Swoole\Async\Client(SWOOLE_SOCK_TCP);
        }
       
        
        $this->swoole_client->set($this->option);

        $this->swoole_client->on("connect", [$this, 'connect']);
        $this->swoole_client->on("receive", [$this, 'receive']);
        $this->swoole_client->on("error", [$this, 'error']);
        $this->swoole_client->on("close", [$this, 'close']);
        $this->swoole_client->on("bufferFull", [$this, 'bufferFull']);
        $this->swoole_client->on("bufferEmpty", [$this, 'bufferEmpty']);
    }

   

    /**
     * 开始,链接服务器
     */
    public function start($timeout = 10)
    {
        if (!$this->isConnected()) {
            \pms\Output::debug([$this->isConnected, $this->server_ip, $this->server_port], 'client_start');
            return $this->swoole_client->connect($this->server_ip, $this->server_port, $timeout);
        }
        return true;

    }


    /**
     * 当缓存区低于最低水位线时触发此事件。
     */
    public function bufferEmpty(\Swoole\Async\Client $client)
    {
        $this->call('bufferEmpty', $client);
        
    }

    /**
     * 当缓存区达到最高水位时触发此事件。
     */
    public function bufferFull(\Swoole\Async\Client $client)
    {
        $this->call('bufferFull', $client);
    }



    /**
     * 链接出错的
     * @param \Swoole\Async\Client $client
     */
    public function error(\Swoole\Async\Client $client)
    {
        \pms\Output::error(['client error', $this->name], 'error');
        $this->call('error', $client);


    }

    /**
     * 当链接关闭
     * @param \Swoole\Async\Client $client
     */
    public function close(\Swoole\Async\Client $client)
    {
        $this->isConnected = false;
        \pms\Output::info('client server close');
        $this->call('close', $client);
    }


    /**
     * 链接成功
     * @param \Swoole\Async\Client $client
     */
    public function connect(\Swoole\Async\Client $client)
    {
        $this->isConnected = true;
        output([$this->server_ip,$this->server_port],'connect');
        $this->call('connect', $client,$this->option);

    }


    /**
     * 收到值,真实
     * @param \Swoole\Async\Client $cli
     * @param $data
     */
    public function receive(\Swoole\Async\Client $client, $data_string)
    {
        \pms\Output::output($data_string,'clinet-receive');
        $this->call('receive', $client, $this->decode($data_string));
    }


    /**
     * 时间执行
     * @param $event
     * @param \Swoole\Async\Client $client
     */
    private function call($event, \Swoole\Async\Client $client, $data = null)
    {
        $di = \Phalcon\DI\FactoryDefault\Cli::getDefault();
        \pms\Output::output($request, $this->name . $event);
        $counnect = new ClientCounnect($client, $data);
        $url = '/' . $this->name . '/' . $event;
        $counnect->analysisRouter($url);
        $router = $counnect->getRouter();
        $router['params'] = [$counnect, $this->swoole_server];
        if($router['task'] =='empty' && $router['action'] =='empty'){
            return false;
        }
        try {
            $console = new \Phalcon\Cli\Console();
            $console->setDI($di);
            \pms\Output::output([$router['task'], $router['action'], $url,$data], $this->name . $event . '-params');
            $console->handle($router);
        } catch (Exception $exception) {
            $counnect->send($exception->getMessage());
        }
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
            $this->eventsManager->fire($this->name . ":beforeSend", $this, $data);
            return $this->swoole_client->send($this->encode($data));
        }
    }

    public function isConnected()
    {
        return $this->swoole_client->isConnected();
    }


}