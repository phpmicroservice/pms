<?php

namespace \pms\Validation\Validator;

/**
 * 必填验证的增强版 可以进行多个字段的选一必填,即多选一
 * Class RequiredPro
 * @package core\Validator
 */
class RequiredPro extends \pms\Validation\Validator
{
    public function validate(\Phalcon\Validation $validation, $attribute)
    {
        $attrs = $this->getOption('attrs');
        if (is_array($attrs)) {
            foreach ($attrs as $attr) {
                $value = $validation->getValue($attr);
                if (!empty($value)) {
                    # 有一个不为空
                    return true;
                }
            }
            # 全都是空
            $this->type = 'allEmpty';
            return $this->appendMessage($validation, $attribute);

        } else {
            # 设置不对
            $this->type = 'Option';
            return $this->appendMessage($validation, $attribute);
        }

    }

}