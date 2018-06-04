<?php

namespace pms;

use \pms\bear\Counnect;

/**
 * 控制器
 * Class Controller
 * @property \pms\bear\Counnect $connect
 * @property \pms\Session $session
 * @property \Phalcon\Config $dConfig
 * @package pms
 */
class Controller extends \Phalcon\Di\Injectable
{

    public $connect;
    public $session;

    public function __construct()
    {

    }


// 初始化事件
    public function initialize()
    {
    }

    /**
     * 在执行之前调度
     * @param Dispatcher $dispatcher
     */
    public function beforeExecuteRoute(Dispatcher $dispatcher)
    {

    }


    /**
     * 西沟函数
     */
    public function __destruct()
    {
        Output::debug('销毁控制器!');
        $this->onDestruct();
    }

    /**
     *
     */
    public function onDestruct()
    {

    }
}