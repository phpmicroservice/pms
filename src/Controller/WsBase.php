<?php

namespace pms\Controller;
/**
 * Class WsBase
 * @property \pms\bear\WsCounnect $counnect
 * @property \Swoole\WebSocket\Server $server
 * @package pms\Contoller
 */
class WsBase extends \Phalcon\Di\Injectable
{

    public $counnect;
    public $server;

    public function __construct()
    {
        $this->counnect = $this->dispatcher->getParam('counnect');
        $this->server = $this->dispatcher->getParam('server');

    }

}