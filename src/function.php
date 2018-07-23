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
 * 获取环境变量的方法
 * @param $name
 * @param string $default
 * @return bool
 */
function get_envbl($name, $default = true)
{
    return (bool)(getenv(strtoupper($name)) === false ? $default : getenv(strtoupper($name)));
}

/**
 * 判断环境变量是否存在
 * @param array $list
 * @return bool|string 都存在返回true,有不存在的返回string 环境变量名字
 *
 */
function env_exist(array $list = [])
{
    foreach ($list as $value) {
        if (getenv(strtoupper($value)) === false) {
            return $value;
        }
    }
    return true;
}

/**
 * 输出内容
 * @param $data
 * @param string $msg
 */
function output($data, $msg = 'info')
{
    \pms\Output::debug($data, $msg);
}

/**
 * 获取通讯key
 * @param $secret
 * @param $data
 * @param $name
 */
function get_access($secret, &$data, $name = '')
{
    $data['uniqid58_'] = $data['uniqid58_'] ?? mt_rand(1000000, 99999999);
    return md5($secret . md5(serialize(asort($data))) . md5(strtolower($name)));
}


/**
 * 通讯key验证
 * @param $accessKey
 * @param $secret
 * @param $data
 * @param string $name
 * @return bool
 */
function verify_access($accessKey, $secret, $data, $name = '')
{
    return hash_equals(get_access($secret, $data, $name), $accessKey);
}

/**
 * 通讯key验证,公钥的验证
 * @param $accessKey
 * @param $secret
 * @param $data
 * @param string $name
 * @return bool
 */
function verify_access2($accessKey, $secret, $data, $name = '')
{
    return hash_equals(get_access(sub_access($secret, $name), $data, $name), $accessKey);
}

/**
 * 获取子秘钥
 * @param $accessKey 主密钥
 * @param $name 子秘钥的名字
 * @return string
 */
function sub_access($accessKey, $name)
{
    return hash('sha1',
        md5(substr(md5($accessKey), 10) . $accessKey) .
        substr(md5($accessKey . $name), 10));
}