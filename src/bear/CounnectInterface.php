<?php

namespace pms\bear;

/**
 * 链接的抽象
 * Interface CounnectInterface
 * @package pms\bear
 */
interface CounnectInterface
{
    /**
     * 打开链接
     */
    public function open();


    /**
     * 获取干扰符
     * @return mixed|string|null
     */
    public function getInterference(): string;


    /**
     * 重置干扰符,保存干扰符关系
     * @return string
     */
    public function resetInterference(): string;


    /**
     * 获取路由
     * @return mixed
     */
    public function getRouter($model = 'cli'): array;


    /**
     *
     */
    public function analysisRouter($router = null);


    /**
     * 获取内容
     */
    public function getContent($index = null);


    /**
     * 获取数据
     * @return mixed
     */
    public function getData($index = null);


    /**
     * 获取路由字符串
     */
    public function getRouterString(): string;


    /**
     * 获取fd_id
     */
    public function getFd(): int;
    
}