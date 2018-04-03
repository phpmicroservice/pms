<?php

namespace pms\Validation;

/**
 * 验证器
 * Class pmsValidator
 * @package pms
 * @property \pms\pmsValidation $_Validation
 */
class Validator extends \Phalcon\Validation\Validator
{
    protected $code = '';
    protected $type = '';
    protected $_Va = false;
    protected $_Validation;
    protected $message;


    public function __construct(array $options = null)
    {

        parent::__construct($options);
        if (method_exists($this, 'init')) {
            $this->init();
        }
    }

    protected function init()
    {
        if ($this->_Va) {
            $this->_Validation = \Phalcon\Di::getDefault()->get('Validation');
        }
    }

    /**
     * 执行验证
     * @param \Phalcon\Validation $validation 这个验证器
     * @param string $attribute 要验证的字段名字
     * @return boolean
     */
    public function validate(\Phalcon\Validation $validation, $attribute)
    {
    }


    /**
     * 发送消息
     * @param \Phalcon\Validation $validation
     * @param null $message
     * @param null $field
     * @param null $type
     * @param null $code
     * @return bool
     */
    protected function appendMessage(\Phalcon\Validation $validation, $field, $message = null, $type = null, $code = null)
    {

        if ($type === null) {
            $type = $this->type;
        }

        if ($message === null) {
            $message = $this->getOption("message");
            if ($message == get_class($this)) {
                $message = $this->message;
            }
        }
        $validation->appendMessage(
            new \Phalcon\Validation\Message($message, $field, $type, $code)
        );
        return false;
    }
}