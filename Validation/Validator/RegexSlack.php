<?php
/**
 * Created by PhpStorm.
 * User: Dongasai
 * Date: 2018/1/18
 * Time: 16:06
 */

namespace \pms\Validation\Validator;


/**
 * 减弱版本的正则验证,
 * Class RegexSlack
 * @package core\Validator
 */
class RegexSlack extends \pms\Validation\Validator
{

    public function validate(\Phalcon\Validation $validation, $attribute)
    {

        $value = $validation->getValue($attribute);
        $pattern = $this->getOption("pattern");
        if (preg_match($pattern, $value)) {
            # 通过
            return true;
        } else {
            $this->type = 'RegexSlack';
            return $this->appendMessage($validation, $attribute);
            # 不通过
        }
    }
}