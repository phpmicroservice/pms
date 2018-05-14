<?php

namespace pms\FilterTool;

use Phalcon\Filter;

/**
 * Class FilterTool filterTool
 * @property \Phalcon\Filter $_Filter
 * @package pms\FilterTool
 */
class FilterTool
{
    protected $_Filter;
    protected $_Rules = [];

    public function __construct()
    {
        $this->_Filter = new Filter();
        $this->initialize();
    }

    protected function initialize()
    {

    }


    /**
     * ����
     * @param array $data ����,���ô���
     * @param bool $old �Ƿ���Ҫ������
     * @return array
     */
    public function filter(array &$data, $old = false)
    {
        $newarr = [];
        foreach ($this->_Rules as $filter1) {
            $field = $filter1[0];
            $filter = $filter1[1];
            if (isset($data[$field])) $newarr[$field] = $this->_Filter->sanitize($data[$field], $filter);
        }
        if ($old) {
            $newarr = array_merge($data, $newarr);
        }
        $data = $newarr;
        return $data;
    }

}