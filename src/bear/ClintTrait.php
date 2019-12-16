<?php


namespace pms\bear;

use pms\Output;

/**
 *
 * Trait ClintTrait
 * @property-read  \Swoole\Client $swoole_client
 * @package pms\bear
 */
trait ClintTrait
{

    /**
     * 判断链接
     * @return bool
     */
    public function isConnected()
    {
        return $this->swoole_client->isConnected();
    }

    /**
     * 发送数据
     * @param $data
     */
    public function send(array $data)
    {
        $data['f'] = $data['f'] ?? strtolower(SERVICE_NAME);
        $sd= $this->encode($data);
        Output::debug([$sd,$data]);
        return $this->swoole_client->send($sd);
    }
    


}