<?php


namespace pms;


class Validation extends \Phalcon\Validation implements \Phalcon\Di\InjectionAwareInterface
{

    use Validation\Validation;

    protected $rules = []; #验证规则
    protected $rules_ = []; #重复的验证规则


    /**
     * 判断验证的时候是否出错了!
     */
    public function isError()
    {
        $mess = $this->getMessages();
        return count($mess);
    }

    /**
     * 验证的重构
     * @param null $data
     * @param null $entity
     * @return bool 通过返回true 发现问题返回false
     */
    public function validate($data = null, $entity = null)
    {

        $message = parent::validate($data, $entity);
        if ($message->count()) {
            return false;
        }
        return true;
    }


    public function getErrorMessages(): string
    {
        $messages = '';
        foreach (parent::getMessages() as $message) {
            $messages .= $message->getMessage() . '<br />';
        }
        return $messages;
    }

    /**
     * 初始化的时候进行 验证规则解析
     */
    protected function initialize()
    {

        if (!empty($this->rules)) {
            $re = $this->analysisRule($this->rules);
            if (is_string($re)) {
                return $this->appendMessage(new \Phalcon\Validation\Message($re));
            }
        }
        if (!empty($this->rules_)) {
            $re = $this->analysisRule2($this->rules_);
            if (is_string($re)) {
                return $this->appendMessage(new \Phalcon\Validation\Message($re));
            }
        }
    }

    /**
     * 验证规则解析
     */
    protected final function analysisRule($rulesArr)
    {
        foreach ($rulesArr as $name => $rules) {

            foreach ($rules as $rule => $pa) {
                $fun_name = 'add_' . $rule;
                if (method_exists($this, $fun_name)) {
                    $re = $this->$fun_name($name, $pa);
                    if (is_string($re)) {
                        return $re;
                    }
                } else {
                    return '初始化失败!公式错误!' . $fun_name;
                }
            }
        }
    }
    //获取所有的 错误信息

    /**
     *
     * 重复的验证规则解析
     */
    protected final function analysisRule2($rulesArr)
    {
        foreach ($rulesArr as $rule => $names) {
            foreach ($names as $name) {
                $fun_name = 'add_' . $rule;
                if (method_exists($this, $fun_name)) {
                    $re = $this->$fun_name($name, []);
                    if (is_string($re)) {
                        return $re;
                    }
                } else {
                    return '初始化失败!公式错误!' . $fun_name;
                }
            }
        }
    }

    /**
     * Appends a message to the messages list
     */
    public function appendMessage(\Phalcon\Validation\MessageInterface $message): Validation
    {
        if ($this->_messages) {
            $messages = new Validation\Message\Group();
        }
        $messages->appendMessage($message);
        $this->_messages = $messages;
        return $this;
    }
}