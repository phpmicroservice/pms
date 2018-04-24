<?php

namespace pms\Validation\Validator;

use core\CoreModel;


/**
 * 状态判断
 * Class whereValidator
 * @package pms\Validation\Validator
 */
class StatusValidator extends \pms\Validation\Validator
{
    /**
     * 进行验证
     * @param \Phalcon\Validation $validation
     * @param type $attribute
     * @return boolean
     */
    public function validate(\Phalcon\Validation $validation, $attribute)
    {
        $model_name = $this->getOption('model', null);
        if (is_string($model_name)) {

        } else {

            $this->type = 'model';
            return $this->appendMessage($validation, $attribute);
        }
        $by = $this->getOption('by', 'id');
        $function_name = 'findFirstBy' . $by;
        $by_value = $validation->getValue($this->getOption('by_index', 'id'));

        $model_info = $model_name::$function_name($by_value);
        if (empty($model_info)) {
            $this->type = "miss";
            return $this->appendMessage($validation, $attribute);
        }
        $status = $this->getOption('status', []);
        foreach ($status as $status_key => $status_val) {
            $m_value = $model_info->$status_key;
            $d_vcal = $validation->getValue($status_val);
            if ($m_value == $d_vcal) {
                return true;
            } else {

                $this->type = "key-" . $status_key;
                return $this->appendMessage($validation, $attribute);
            }
        }
        return true;


    }
}