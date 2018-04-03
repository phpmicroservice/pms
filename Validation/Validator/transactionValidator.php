<?php


namespace \pms\Validation\Validator;

/**
 * 事务验证
 * Class transactionValidator
 * @package core\Validator
 */
class transactionValidator extends \pms\Validation\Validator
{
    /**
     * 进行验证
     * @param \Phalcon\Validation $validation
     * @param type $attribute
     * @return boolean
     */
    public function validate(\Phalcon\Validation $validation, $attribute)
    {
        $transaction = $this->get_transactionManager();
        if (!$transaction->has()) {
            # 没有启动事务
            $validation->appendMessage(
                new \Phalcon\Validation\Message($this->getOption("message", 'no-start-transaction'), $attribute, 'transaction')
            );
            return false;
        }
        return true;
    }

    /**
     * 获取事务管理器
     * @return \Phalcon\Mvc\Model\Transaction\Manager
     */
    private function get_transactionManager(): \Phalcon\Mvc\Model\Transaction\Manager
    {
        return \Phalcon\Di::getDefault()->getShared('transactionManager');
    }

}