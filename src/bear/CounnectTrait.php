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
    public function getInterference()
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
    public function resetInterference()
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
    public function getRouterString()
    {
        return $this->data[ROUTER_INDEX] ?? '/';
    }

    /**
     * 获取fd_id
     */
    public function getFd()
    {
        return $this->fd;
    }


}