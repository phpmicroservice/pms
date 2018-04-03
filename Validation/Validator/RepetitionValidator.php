<?php


namespace \pms\Validation\Validator;

/**
 *
 *
 * $parameter['message']  提示信息
 *              $parameter['class_name']  对象列表,数组则依靠下面的设置
 *              [$parameter['function_name']]  使用对象的那个方法进行判断,空则使用findFirst
 *              [$parameter['where']]  判断方法传入参数
 *              [$parameter['ai']]  判断方法传入参数
 *
 *
 */

/**
 * Description of repetition
 * 重复验证
 * @author Dongasai
 */
class RepetitionValidator extends \pms\Validation\Validator
{

    /**
     * 进行验证
     * @param \Phalcon\Validation $validation
     * @param type $attribute
     * @return boolean
     */
    public function validate(\Phalcon\Validation $validation, $attribute)
    {

        $obj = $message = $this->getOption("class_name");
        if (is_string($obj)) {
            $obj_true = new $obj();
        } else {
            $obj_true = $obj;
        }

        if (!($obj_true instanceof \core\CoreModel)) {
            $validation->appendMessage(
                new \Phalcon\Validation\Message($this->getOption("message"), $attribute, $attribute)
            );
            return false;
        }

        $function_name = $this->getOption("function_name");
        if ($function_name) {

        } else {
            $function_name = 'findFirst';
        }
        $where = $this->getOption("where", $validation->getValue($attribute));
        $dataModel = $obj_true->$function_name($where);

        if ($this->is_repetition($dataModel, $validation)) {
            $validation->appendMessage(
                new \Phalcon\Validation\Message($this->getOption("message"), $attribute, 'Repetition')
            );
            return false;
        }
        return true;
    }

    /**
     * 判断是否重复
     * @param $dataModel
     * @param $validation
     */
    private function is_repetition($dataModel, \Phalcon\Validation $validation)
    {

        $ai = $this->getOption("ai", 'id');
        $auto = $validation->getValue($ai);
        if (empty($auto)) {
            if ($dataModel) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }


    }

}
