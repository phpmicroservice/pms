<?php
# 启动索引文件

# 设置php常用配置
date_default_timezone_set("PRC");
# 加载函数库 在composer里加载

# 设置 常量

defined('ROOT_DIR') || exit('constant ROOT_DIR Undefined!');
defined('ROUTER_INDEX') || define('ROUTER_INDEX', 'r');

define('PMS_DIR', __DIR__);
# 设置服务器名字
if (!defined('SERVICE_NAME')) {
    if (getenv('APP_SERVICE_NAME')) {
        define('SERVICE_NAME', strtolower(\pms\get_env('APP_SERVICE_NAME')));
    } else {
        if (defined('APP_SERVICE_NAME')) {
            define('SERVICE_NAME', strtolower(APP_SERVICE_NAME));
        }

    }
}
if (defined('SERVICE_NAME')) {
    echo "SERVICE_NAME : " . SERVICE_NAME . " \n";
} else {
    echo "SERVICE_NAME : " . SERVICE_NAME . " \n";
    exit();
}


defined('RUNTIME_DIR') || define('RUNTIME_DIR', ROOT_DIR . '/runtime/');# 运行目录
if (!is_dir(RUNTIME_DIR)) mkdir(RUNTIME_DIR, 775, true);


defined('APP_DEBUG') || define('APP_DEBUG', boolval(\pms\get_env("APP_DEBUG", 1)));# debug 的开启
# 输出级别定义
defined('OUTPUT_ERROR') || define('OUTPUT_ERROR', \pms\get_envbl("OUTPUT_ERROR", 1));# error级别的输出 的开启
defined('OUTPUT_INFO') || define('OUTPUT_INFO', \pms\get_envbl("OUTPUT_INFO", 1));# error级别的输出 的开启
defined('OUTPUT_APP') || define('OUTPUT_APP', \pms\get_envbl("OUTPUT_APP", 1));# APP级别的输出 的开启
defined('OUTPUT_NOTICE') || define('OUTPUT_NOTICE', \pms\get_envbl("OUTPUT_NOTICE", 1));# notice级别的输出 的开启
defined('OUTPUT_PMS') || define('OUTPUT_PMS', \pms\get_envbl("OUTPUT_PMS", 1));# notice级别的输出 的开启
defined('NO_OUTPUT') || define('NO_OUTPUT', \pms\get_envbl("NO_OUTPUT", 1));# notice级别的输出 的开启

defined('SD_OPTION') || define('SD_OPTION', ['open_length_check' => true,
    'package_max_length' => 83886080,
    'package_length_type' => 'N',
    'package_length_offset' => 0,
    'package_body_offset' => 4,]);


define('START_TIME', time());
defined('RUN_UNIQID') || define('RUN_UNIQID', uniqid());
echo '项目目录为:' . ROOT_DIR . ',pms目录为:' . PMS_DIR . " \n";


# 服务的地址和端口
if (empty(\pms\get_env("APP_HOST_IP"))) {
    $ip_list = \swoole_get_local_ip();
    $host_ip = $ip_list['eth0'];
} else {
    $host_ip = \pms\get_env("APP_HOST_IP");
}
define('APP_HOST_IP', $host_ip);
if (empty(\pms\get_env("APP_HOST_PORT"))) {
    $host_port = 9502;
} else {
    $host_port = \pms\get_env("APP_HOST_PORT");
}
define('APP_HOST_PORT', $host_port);

echo '项目的访问地址为:' . APP_HOST_IP . ':' . APP_HOST_PORT . " \n";
echo '当前PHP的版本为:' . PHP_VERSION . ' ,Phalcon版本为:' . \Phalcon\Version::get() . '  ,Swoole的版本为:' . \swoole_version() . " \n";
