<?php

namespace \pms\Validation\Validator;


/**
 * 检测数组长度
 * Class ArrayLength
 * @package core\Validator
 */
class ArrayLength extends \pms\Validation\Validator
{
    public function validate(\Phalcon\Validation $validation, $attribute)
    {

        $value = $validation->getValue($attribute);
        Trace::add('ArrayLength', $value);
        if (!is_array($value)) {
            $this->type = 'is_array';
            return $this->appendMessage($validation, $attribute);
        }
        $max = $this->getOption('max', 0);
        $min = $this->getOption('min', 999);
        if (count($value) < $min) {
            $this->type = 'min';
            return $this->appendMessage($validation, $attribute);
        }
        if (count($value) > $max) {
            $this->type = 'max';
            return $this->appendMessage($validation, $attribute);
        }
        return true;

    }

}