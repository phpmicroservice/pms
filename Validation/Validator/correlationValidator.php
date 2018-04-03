<?php
/**
 * Created by PhpStorm.
 * User: Dongasai
 * Date: 2017/5/9
 * Time: 10:34
 */

namespace \pms\Validation\Validator;


/**
 * correlation
 * $parameter['message']  提示信息
 *              $parameter['model_list']  对象|列表,数组则依靠下面的设置
 *              $parameter['fields_name'] 从数据中读取哪个字段进行验证
 */
/**
 *
 * $parameter=[
 * 'message'=>'correlation',
 * 'model_list'=>[
 * 'model'
 * ],
 * 'fields_name'=>[
 * 'id'
 * ]
 * ];
 *
 */

/**
 * 关联关系验证,用于删除
 * Class correlationValidator
 * @package core\Validator
 */
class correlationValidator extends \pms\Validation\Validator
{
    public function validate(\Phalcon\Validation $validation, $attribute)
    {


        if (!$this->is_correlation($validation, $attribute)) {
            return $this->appendMessage($validation, $attribute);

        }
        return true;
    }

    /**
     * 判断是否
     * @param \Phalcon\Validation $validation
     * @param $attribute
     * @return bool
     */
    public function is_correlation(\Phalcon\Validation $validation, string $attribute): bool
    {
        $model_list = $this->getOption("model_list");
        $fields_name = $this->getOption("fields_name");
        $vca = $validation->getValue($attribute);
        foreach ($model_list as $k => $value) {
            if (is_string($value)) {
                $model = new  $value();
            } elseif (is_object($value)) {
                $model = $value;
            }
            $fields = $fields_name[$k];

            $where = [
                'conditions' => "$fields = :valuess:",
                'bind' => ['valuess' => $vca]
            ];
            $data = $model->find($where);
            if ($data === false or empty($data->toArray())) {
                #没有找到是通过的

            } else {
                $this->type = 'correlation' . get_class($model);
                return false;
            }
        }
        return true;
    }
}