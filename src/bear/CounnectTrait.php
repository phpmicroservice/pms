<?php


namespace pms\bear;


trait CounnectTrait
{


    /**
     * 打开链接
     */
    public function open()
    {
        $this->resetInterference();
    }


    /**
     * 获取干扰符
     * @return mixed|string|null
     */
    public function getInterference():string
    {
        $interference = $this->cache->get('interference' . RUN_UNIQID . $this->fd, 15552000);
        if (empty($interference)) {
            return $this->resetInterference();
        }
        return $interference;
    }


    /**
     * 重置干扰符,保存干扰符关系
     * @return string
     */
    public function resetInterference():string
    {
        $interference = uniqid() . mt_rand(11111111, 99999999);
        $this->cache->save('interference' . RUN_UNIQID . $this->fd, $interference, 15552000);
        return $interference;
    }


    /**
     * 获取路由
     * @return mixed
     */
    public function getRouter($model = 'cli'): array
    {
        if ($model == 'cli') {
            return [
                'module' => $this->router->getModuleName(),
                'task' => $this->router->getControllerName(),
                'action' => $this->router->getActionName()
            ];
        } else {
            return [
                'module' => $this->router->getModuleName(),
                'controller' => $this->router->getControllerName(),
                'action' => $this->router->getActionName()
            ];
        }

    }

    /**
     *
     */
    public function analysisRouter($router = null)
    {
        $this->router = \Phalcon\Di::getDefault()->get('router2');
        if ($router) {
            $this->router->handle($router);
        } else {
            $this->router->handle($this->getRouterString());
        }

    }


    /**
     * 获取内容
     */
    public function getContent($index = null)
    {
        if ($index) {
            return $this->data['d'][$index] ?? null;
        }
        return $this->data['d'];
    }

    /**
     * 获取数据
     * @return mixed
     */
    public function getData($index = null)
    {
        if ($index) {
            return $this->data[$index] ?? null;
        }
        return $this->data;
    }

    /**
     * 获取路由字符串
     */
    public function getRouterString():string
    {
        return $this->data[ROUTER_INDEX] ?? '/';
    }

    /**
     * 获取fd_id
     */
    public function getFd():int 
    {
        return $this->fd;
    }

    /**
     * 发送一个成功
     * @param $m 消息
     * @param array $d 数据
     * @param int $t 类型/控制器
     */
    public function send_succee($d = [], $m = '成功', $t = '')
    {
        $data = [
            'm' => $m,
            'd' => $d,
            'e' => 0,
            't' => empty($t) ? $this->getRouterString() : $t
        ];
        $this->passing = $this->getData('p');
        if ($this->passing) {
            $data['p'] = $this->passing;
        }
        $data['f'] = strtolower(SERVICE_NAME);
        return $this->send($data);
    }

    /**
     * 发送一个错误的消息
     * @param $m 错误消息
     * @param array $d 错误数据
     * @param int $e 错误代码
     * @param int $t 类型,路由
     */
    public function send_error($m, $d = [], $e = 1, $t = '')
    {
        $data = [
            'm' => $m,
            'd' => $d,
            'e' => $e,
            't' => empty($t) ? $this->getRouterString() : $t
        ];
        $this->passing = $this->getData('p');
        if ($this->passing) {
            $data['p'] = $this->passing;
        }
        $data['f'] = strtolower(SERVICE_NAME);
        return $this->send($data);
    }

}