<?php


namespace pms\Controller;

use pms\bear\CounnectInterface;

/**
 *
 * Trait ClintTrait
 * @package pms\Controller
 */
trait ControllerTrait
{
    /**
     * 获取链接
     * @return CounnectInterface
     */
    public function getCounnect(): CounnectInterface
    {
        return $this->connect;
    }

    public function getServer(): \Swoole\Server
    {
        return $this->server;
    }

}