<?php

namespace pms\Validation\Validator;

use pms\Validation\Validator;

/**
 * 服务方法验证器
 * Class ServerAction
 *
 * @option=[
 *    "server_action"=>"服务名字@路由地址"
 * ]
 * @property \pms\bear\ClientSync $proxyCS
 * @package app\validator
 */
class ServerAction extends Validator
{
    protected $proxyCS;

    /**
     * 执行验证
     * @param \Phalcon\Validation $validator
     * @param string $attribute
     * @return boolean
     */
    public function validate(\Phalcon\Validation $validation, $attribute)
    {
        $server_action = $this->getOption('server_action');
        $server_action_arr = explode('@', $server_action);
        $sername = $server_action_arr[0];
        $actionname = $server_action_arr[1];
        if ($this->getOption('data')) {
            $data = $this->getOption('data');
            $data['disturb'] = \funch\Str::rand(12);
        } else {
            $data = [
                $this->getOption('dataIndex', $attribute) => $validation->getValue($attribute),
                'disturb' => \funch\Str::rand(12)
            ];
        }
        if (empty($sername) || empty($actionname)) {
            $this->type = 'parameter';
            return $this->appendMessage($validation, $attribute);
        }

        $re = $this->proxyCS->request_return($sername, $actionname, $data);
        \pms\output([$re, $sername, $actionname, $data], 'ServerAction');
        if ($re === false || $re['e']) {
            # 请求遇到错误!
            $this->type = 'request_error';
            return $this->appendMessage($validation, $attribute);
        }
        if (!$re['d']) {
            # 返回结果是false
            $this->type = $this->message ? $this->message : 'data';
            return $this->appendMessage($validation, $attribute);
        }
        return true;
    }

    protected function init()
    {
        $this->proxyCS = \Phalcon\Di::getDefault()->getShared('proxyCS');
        parent::init();
    }

}