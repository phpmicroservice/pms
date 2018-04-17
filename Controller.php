<?php

namespace pms;

use \pms\bear\Counnect;

/**
 * 控制器
 * Class Controller
 * @property \pms\bear\Counnect $connect
 * @property \pms\Session $session
 * @package pms
 */
class Controller extends \Phalcon\Di\Injectable
{

    protected $connect;

    // 初始化事件
    protected function onInitialize($connect)
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
     * 初始化session
     */
    protected function init_sid()
    {
        # 进行模拟session

        # 读取session_id
        $sid = $this->connect->sid;
        if (empty($sid)) {
            # 没有发送sid
            $sid = \strtolower(md5(uniqid() . time()));
            $this->connect->send_succee($sid, '初始化sid', 'init_sid');
        }
        $this->session_id = $sid;
        output($sid, 'sid');

        $this->session = new Session($sid);

    }

    /**
     * 西沟函数
     */
    public function __destruct()
    {
        Output::debug('销毁控制器!');
        $this->session->reserve();
        $this->onDestruct();
    }

    /**
     *
     */
    public function onDestruct()
    {

    }
}