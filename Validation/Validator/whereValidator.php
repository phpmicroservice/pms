<?php

namespace \pms\Validation\Validator;

use core\CoreModel;
use core\Sundry\Trace;

/**
 * wheres 条件查询验证 存在返回true通过验证   negation取反  model
 * Class whereValidator
 * @package core\Validator
 */
class whereValidator extends \pms\Validation\Validator
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
            Trace::add('where', 'model');
            $this->type = 'model';
            return $this->appendMessage($validation, $attribute);
        }
        $modelsManager = \Phalcon\Di::getDefault()->get('modelsManager');
        $builder = $modelsManager->createBuilder();
        if (!($builder instanceof \Phalcon\Mvc\Model\Query\Builder)) {

            Trace::add('where', 'builder');
            $this->type = 'builder';
            return $this->appendMessage($validation, $attribute);
        }
        $builder->from($model_name);
        $wheres = $this->getOption('wheres');
        if (empty($wheres)) {
            $wheres = $validation->getData();
        }
        Trace::add('whereValidator', [
            'wheres' => $wheres,
            'data' => $validation->getData(),
            $attribute
        ]);

        foreach ($wheres as $key => $value) {
            $builder->andWhere($key . ' = :' . $key . ':', [
                $key => $validation->getValue($value)
            ]);
        }

        $data = $builder->getQuery()->execute();
        # 取反 negation
        $negation = $this->getOption('negation', false);
        Trace::add('error', [$data->toArray(), $negation]);
        if (empty($data->toArray())) {
            # 不存在数据
            if ($negation) {
                return true;
            }
            $this->type = 'empty';
            return $this->appendMessage($validation, $attribute);
        }
        if ($negation) {
            $this->type = 'empty_negation';
            return $this->appendMessage($validation, $attribute);
            return false;
        }
        return true;

    }
}