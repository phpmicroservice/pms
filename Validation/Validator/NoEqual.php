<?php
/**
 * Created by PhpStorm.
 * User: Dongasai
 * Date: 2018/1/11
 * Time: 19:41
 */

namespace \pms\Validation\Validator;


/**
 * 不等于验证
 * Class NoEqual
 * <code>
 * use Phalcon\Validation;
 * use Phalcon\Validation\Validator\Confirmation;
 *
 * $validator = new Validation();
 *
 * $validator->add(
 *     "password",
 *     new NoEqual(
 *         [
 *             "message" => "Password doesn't match on firmation",
 *             "with"    => "confirmPassword",
 *         ]
 *     )
 * );
 *
 * $validator->add(
 *     [
 *         "password",
 *         "email",
 *     ],
 *     new NoEqual(
 *         [
 *             "message" => [
 *                 "password" => "Password doesn't match onfirmation",
 *                 "email"    => "Email doesn't match confirmation",
 *             ],
 *             "with" => [
 *                 "password" => "confirmPassword",
 *                 "email"    => "confirmEmail",
 *             ],
 *         ]
 *     )
 * );
 * </code>
 *
 * @package core\Validator
 */
class NoEqual extends \pms\Validation\Validator
{
    public function validate(\Phalcon\Validation $validation, $attribute)
    {
        $value = $validation->getValue($attribute);
        $withValue = $validation->getValue($this->getOption('with', 'with'));
        if ($value == $withValue) {
            $this->type = 'equal';
            return $this->appendMessage($validation, $attribute);
        }
        # 不等于
        return true;

    }

}