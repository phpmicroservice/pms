<?php
/**
 * 获取环境变量的方法
 * @param $name
 * @param string $default
 * @return array|false|string
 */
function get_env($name, $default = '')
{
    return getenv(strtoupper($name)) === false ? $default : getenv(strtoupper($name));
}

/**
 * 输出内容
 * @param $data
 * @param string $msg
 */
function output($data,$msg='info')
{
    \pms\Output::info($data, $msg);
}

/**
 * 获取通讯key
 * @param $secret
 * @param $data
 * @param $name
 */
function get_key($secret, $data, $name = '')
{
    md5(md5($secret) . md5(CONFIG_DATA_KEY) . md5(strtolower(SERVICE_NAME)));
}