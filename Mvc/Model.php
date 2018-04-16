<?php
/**
 * Created by PhpStorm.
 * User: Dongasai
 * Date: 2018/4/16
 * Time: 9:58
 */

namespace pms\Mvc;


class Model extends \Phalcon\Mvc\Model
{

    protected $_append_field = [];
    protected $_relation_data = [];

    public function initialize()
    {

    }

    /**
     * 设置追加信息
     * @param array $arr
     */
    public function set_append_field(array $arr)
    {
        $this->_append_field = $arr;
    }

    /**
     * 设置关联信息
     * @param array $arr
     */
    public function set_relation_data(array $arr)
    {
        $this->_relation_data = $arr;
    }

    /**
     * 设置修改或者增加用的数据到数据模型对象
     * @param type $data
     */
    public function setData($data)
    {
        $this->useDynamicUpdate(true);
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $this->$k = $v;
            }
        } else {
            return false;
        }
    }

    /**
     * 获取所有消息
     */
    public final function getMessage()
    {

        $string = '';
        $mess = $this->getMessages();
        if (count($mess)) {
            foreach ($mess as $v) {
                $string = $string . ' & ' . $v->getMessage();
            }
            return $string;
        } else {
            return false;
        }
    }

    /**
     * 转换数组的魔法函数
     * @param null $columns
     * @return Array
     */
    public function toArray($columns = null): Array
    {
        if ($this->_relation_data) {
            //进行关联读取
            foreach ($this->_relation_data as $field) {
                $this->$field;
            }
        }

        $data = parent::toArray($columns);
        if ($this->_append_field) {
            //存在附加字段
            foreach ($this->_append_field as $field) {
                if (property_exists($this, $field)) {
                    $data[$field] = $this->$field;
                }
            }
        }

        return $data;
    }

    /**
     * 保存之前 -> 更新/创建之前
     */
    protected function beforeSave()
    {

    }

    /**
     * 更新之前
     */
    protected function beforeUpdate()
    {

    }

    /**
     * 创建之前
     */
    protected function beforeCreate()
    {

    }

    /**
     * 读取之后
     */
    protected function afterFetch()
    {

    }


}