<?php

namespace pms;

use Phalcon\Events\ManagerInterface;

/**
 * Class Base
 * @property \Phalcon\Cache\BackendInterface $cache
 * @property \Phalcon\Cache\BackendInterface $gCache
 * @property \Phalcon\Config $config
 * @property \Phalcon\Config $dConfig
 * @property \Swoole\Server $swoole_server
 * @package pms
 */
abstract class Base extends \pms\Di\Injectable implements \Phalcon\Events\EventsAwareInterface
{
    protected $swoole_server;
    protected $name;
    protected $type = 'tcp';

    public function __construct($server)
    {
//        $this->logo = require 'logo.php';
        $this->swoole_server = $server;
    }


    /**
     * 设置类型 ['tcp','udp','ws','http']
     * @param $type
     */
    public function setType($type)
    {
        $in = ['tcp', 'udp', 'ws', 'http'];
        if (in_array($type, $in)) {
            $this->type = $type;
        } else {
            throw new \Exception("不合法的类型");
        }
    }

    /**
     * 设置事件管理器
     * @param ManagerInterface $eventsManager
     */
    public function setEventsManager(ManagerInterface $eventsManager)
    {
        $this->eventsManager = $eventsManager;
    }

    /**
     * 事件绑定
     * @param $handler
     */
    public function onBind($event, $handler)
    {
        $this->eventsManager->attach($this->name . ':' . $event, $handler);
    }

    /**
     * 设置事件管理器
     * @return  ManagerInterface $eventsManager
     */
    public function getEventsManager():ManagerInterface
    {
        return $this->eventsManager;
    }

}