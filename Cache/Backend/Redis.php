<?php

namespace pms\Cache\Backend;

class Redis extends \Phalcon\Cache\Backend\Redis
{


    /**
     * 获取 缓存,不存在调用回调 函数
     * @param string $keyName
     * @param callable $callback
     * @param null $lifetime
     */
    public function getc($keyName, callable $callback, $lifetime = null)
    {
        if (!is_string($keyName)) {
            $keyName = md5(serialize([$keyName]));
        }
        if ($this->exists($keyName, $lifetime)) {
            return $this->get($keyName);
        }
        # 不存在
        $data = $callback($lifetime);
        $this->save($keyName, $data, $lifetime);
        return $data;

    }
}