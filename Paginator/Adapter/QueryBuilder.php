<?php

namespace pms\Paginator\Adapter;

/**
 * User: Dgonasai
 * Date: 2018年4月18日14:03:43
 */
class QueryBuilder extends \Phalcon\Paginator\Adapter\QueryBuilder
{

    /**
     * Returns a slice of the resultset to show in the pagination
     * @return \stdClass
     */
    public function getPaginate($call = null): \stdClass
    {
        $page = parent::getPaginate();

        if (is_callable($call)) {
            return $call($page);
        }

        return $page;
    }


}