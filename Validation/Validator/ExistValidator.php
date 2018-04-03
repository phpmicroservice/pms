<?php

namespace \pms\Validation\Validator;

/**
 * exist
 * $parameter['message']  提示信息
 *              $parameter['class_name_list']  对象|列表,数组则依靠下面的设置
 *              [$parameter['object_name']]  从数据中读取哪个字段作为对象索引,默认为当前的字段
 *              [$parameter['function_name']]  使用对象的那个方法进行判断,默认使用findFirstById
 *              [$parameter['reverse']] 是否逆向判断,默认为false
 */


/**
 * 判断是否存在
 * Description of Exist
 * @author Dongasai
 */
class ExistValidator extends \pms\Validation\Validator
{
    private $type = 'exist';

    public function validate(\Phalcon\Validation $validation, $attribute)
    {

        $allowEmpty = $this->getOption('allowEmpty');

        $ob_list = $message = $this->getOption("class_name_list");
        if (is_array($ob_list)) {
            $index = $this->getOption("object_name");
            $obj = $ob_list[$validation->getValue($index)];
            if (empty($obj)) {
                $validation->appendMessage(
                    new \Phalcon\Validation\Message('ExistValidator  validation parameter error ', $attribute, $attribute)
                );
                return fasle;
            }
        } else {
            $obj = $ob_list;
        }
        if (is_string($obj)) {
            $obj_true = new $obj();
        } else {
            $obj_true = $obj;
        }
        $function_name = $this->getOption("function_name");
        if ($function_name) {

        } else {
            $function_name = 'findFirstById';
        }

        $re = $obj_true->$function_name($validation->getValue($attribute));

        if (!$this->isExist($re)) {
            Trace::add('info', [
                $this->getOption("function_name"),
                $this->getOption("object_name"),
                $this->getOption("class_name_list"),
                $function_name,
                $validation->getValue($attribute),
            ]);
            $validation->appendMessage(
                new \Phalcon\Validation\Message($this->getOption("message"), $attribute, $this->type)
            );
            return false;
        }
        return true;
    }

    /**
     * 判断是否存在
     * @param $dataModel
     */
    private function isExist($dataModel)
    {

//        reverse
        $reverse = $this->getOption("reverse", false);
        if ($reverse) {
            $this->type = 'reverse';
            if ($dataModel === false) {
                return true;
            } else {
                return false;
            }
        } else {
            if ($dataModel === false) {

                return false;
            } else {
                return true;
            }
        }
        throw new \Phalcon\Exception('some unexpected results', 403);
    }

}
