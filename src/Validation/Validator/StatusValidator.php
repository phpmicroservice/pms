<?php

namespace pms\Validation\Validator;


/**
 * 状态判断 判断数据是否是期望的数据
 * Class StatusValidator
 * model 模型名字
 * by 根据那个字段(数据库)
 * by_index 根绝那个值(数据)
 * status 键值对 [ 要验证的状态的数据库字段 => 期望的状态值 ]
 *
 * 存在与期望值不一样的即验证不通过
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
            if ($m_value == $status_val) {
            } else {
                \pms\output([$m_value, $status_val], 'status');
                $this->type = "key-" . $status_key;
                return $this->appendMessage($validation, $attribute);
            }
        }
        return true;


    }
}