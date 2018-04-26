<?php

namespace pms;

/**
 * session 实现 储存在sessionCache
 * Class Session
 * @property \Phalcon\Cache\BackendInterface $sessionCache
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
    private $sessionCache;

    public function __construct($sid, $option = [])
    {

        $this->sessionCache = \Phalcon\Di::getDefault()->get('sessionCache');
        $this->option = array_merge($this->option, $option);
        $this->init($sid);

    }

    /**
     * @param $sid
     */
    public function init($sid)
    {
        # 保存之前的session的信息
        if (!empty($this->data)) {
            $this->reserve();
        }
        $this->session_id = $sid;
        $this->session_key = $this->option['prefix'] . $sid;
        $this->data = $this->sessionCache->get($this->session_key);
        output($this->data, 'session_init');
        if (empty($this->data)) {
            $this->data = [];
        }
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
        $this->data[$index] = $value;
        return $this->reserve();
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
     * 移除一个索引
     * @param $index
     */
    public function remove($index)
    {
        unset($this->data[$index]);
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
        $this->sessionCache->delete($this->session_key);
    }

    /**
     * 储存
     */
    public function reserve()
    {
        Output::debug($this->data, 'session_reserve');
        $this->data['save_time'] = time();
        $this->sessionCache->save($this->session_key, $this->data, $this->option['lifetime']);
    }

    /**
     * 更新数据
     */
    public function update()
    {
        $this->data = $this->sessionCache->get($this->session_key);
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
     * Alias: Check whether a session variable is set in an application context
     */
    public function __isset(string $index)
    {
        return $this->has($index);
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
     * 析构函数
     */
    public function __destruct()
    {
        $this->reserve();
    }



}