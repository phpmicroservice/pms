<?php

namespace pms;

/**
 * session 实现 储存在gCache
 * Class Session
 * @property \Phalcon\Cache\BackendInterface $gCache
 * @package pms
 */
class Session
{
    private $option = [
        'lifetime' => 600,
        'prefix' => 'session_'
    ];
    private $session_id;
    private $session_key;
    private $data;
    private $gCache;

    public function __construct($sid, $option = [])
    {

        $this->gCache = \Phalcon\Di::getDefault()->get('gCache');
        $this->option = array_merge($this->option, $option);
        $this->session_id = $sid;
        $this->session_key = $this->option['prefix'] . $sid;
        $this->init($sid);

    }

    /**
     * @param $sid
     */
    public function init($sid)
    {
        output(get_class($this->gCache), 'session35');
        $this->data = $this->gCache->get($this->session_key);
        if (empty($this->data)) {
            $this->data = [];
        }
    }

    /**
     * 获取当前session的id
     */
    public function getId()
    {
        return $this->session_id;
    }

    /**
     * 设置session的id
     *
     * @param $sid
     */
    public function setId($sid)
    {
        $this->session_id = $sid;
        $this->init($sid);
    }

    /**
     * 销毁session,全部删除,不留下,会立即同步
     */
    public function destroy()
    {
        $this->data = [];
        $this->gCache->delete($this->session_key);
    }

    /**
     * 更新数据
     */
    public function update()
    {
        $this->data = $this->gCache->get($this->session_key);
    }

    /**
     * Alias: Gets a session variable from an application context
     */
    public function __get($index)
    {
        return $this->get($index);
    }

    /**
     * Alias: Sets a session variable in an application context
     */
    public function __set(string $index, $value)
    {
        return $this->set($index, $value);
    }

    /**
     * 获取内容
     * @param $index
     * @param null $default
     * @return null
     */
    public function get($index, $default = null)
    {
        return $this->data[$index] ?? $default;
    }

    /**
     * 设置内容
     * @param $index
     * @param $value
     */
    public function set($index, $value)
    {
        return $this->data[$index] = $value;
    }

    /**
     * Alias: Check whether a session variable is set in an application context
     */
    public function __isset(string $index)
    {
        return $this->has($index);
    }

    /**
     * 判断索引是否存在
     * @param $index
     * @return bool
     */
    public function has($index)
    {
        return isset($this->data[$index]);
    }

    /**
     * Alias: Removes a session variable from an application context
     *
     * <code>
     * unset($session->auth);
     * </code>
     */
    public function __unset(string $index)
    {
        $this->remove($index);
    }

    /**
     * 移除一个索引
     * @param $index
     */
    public function remove($index)
    {
        unset($this->data[$index]);
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        $this->reserve();
    }

    /**
     * 储存
     */
    public function reserve()
    {
        Output::debug($this->data, 'session_reserve');
        $this->gCache->save($this->session_key, $this->data, $this->option['lifetime']);
    }


}