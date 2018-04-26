<?php

namespace pms\Validation\Message;


/**
 * Class Group
 * @package pms\Validation\Message
 */
class Group extends \Phalcon\Validation\Message\Group
{
    /**
     * 转换数组
     * @return array
     */
    public function toArray()
    {
        $message_string = '';
        $arr = [
            'message' => '',
            'data' => []
        ];
        foreach ($this->_messages as $key => $message) {
            if ($message instanceof \Phalcon\Validation\Message) {
                $index = $message->getMessage() . '-' . $message->getType() . '-' . $message->getField();
                if ($key) {
                    $message_string .= ' & ';
                }
                $message_string .= $index;
                $arr['data'][] = [
                    'text' => $message->getMessage(),
                    'code' => $message->getCode(),
                    'field' => $message->getField(),
                    'type' => $message->getType()
                ];
            }
        }
        $arr['message'] = $message_string;
        return $arr;
    }

    /**
     * 清空
     *
     */
    public function pruge()
    {
        $this->_messages = [];
    }

}