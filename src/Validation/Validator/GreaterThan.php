<?php

namespace pms\Validation\Validator;


/**
 * 大于
 * Class GreaterThan
 * @package pms\Validation\Validator
 */
class GreaterThan extends \pms\Validation\Validator
{
    public function validate(\Phalcon\Validation $validation, string $attribute): bool
    {

        $value = $validation->getValue($attribute);
        $min = $this->getOption('min', 0);
        $equal = $this->getOption('equal', false);

        if ($equal) {
            # 允许等于
            if (intval($value) < $min) {
                $this->type = 'min';
                return $this->appendMessage($validation, $attribute);
            }
        } else {
            if (intval($value) <= $min) {
                $this->type = 'min';
                return $this->appendMessage($validation, $attribute);
            }
        }


        return true;

    }

}