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
    protected $_Rules;

    public function __construct()
    {
        $this->_Filter = new Filter();
        $this->initialize();
    }

    protected function initialize()
    {

    }

    /**
     *
     * @param $data
     */
    public function filter(&$data)
    {
        foreach ($this->_Rules as $field => $filter) {
            if (isset($data[$field])) $data[$field] = $this->_Filter->sanitize($data[$field], $filter);
        }
        return $data;
    }

}