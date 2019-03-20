<?php

namespace pms\Controller;


use Phalcon\Cli\Dispatcher;
use Phalcon\Translate\InterpolatorInterface;

/**
 * Class WsBase
 * @property \pms\bear\WsCounnect $counnect
 * @property \Swoole\WebSocket\Server $server
 * @package pms\Contoller
 */
abstract class WsBase extends \Phalcon\Di\Injectable
{

    public function initialize()
    {

    }

    public function afterBinding(Dispatcher $dispatcher)
    {
    }

}