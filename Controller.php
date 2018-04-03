<?php

namespace pms;

use \pms\bear\Counnect;
/**
 * 控制器
 * Class Controller
 * @property \pms\bear\Counnect $connect
 * @package pms
 */
class Controller extends \Phalcon\Di\Injectable
{

    protected $connect;

    /**
     * 构造函数
     * Controller constructor.
     * @param Counnect $connect
     */
    public function __construct(Counnect $connect)
    {
        $this->connect = $connect;
        $this->onInitialize( $this->connect );
    }

    // 初始化事件
    protected function onInitialize($connect)
    {
    }
}