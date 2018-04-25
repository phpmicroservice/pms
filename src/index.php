<?php
# 启动索引文件

# 设置php常用配置
date_default_timezone_set("PRC");
# 加载函数库
require __DIR__.'/function.php';
# 设置 常量
defined('SERVICE_NAME') || define('SERVICE_NAME', ROOT_DIR . '/runtime/');# 设置服务器名字

defined('ROOT_DIR') || exit('constant ROOT_DIR Undefined!');
define('PMS_DIR', __DIR__);
defined('SERVICE_NAME') || define('SERVICE_NAME', ROOT_DIR);# 设置服务器名字
defined('RUNTIME_DIR') || define('RUNTIME_DIR', ROOT_DIR . '/runtime/');# 运行目录
defined('CACHE_DIR') || define('CACHE_DIR', ROOT_DIR . '/runtime/cache/');# 缓存目录
defined('APP_DEBUG') || define('APP_DEBUG', boolval(get_env("APP_DEBUG", 1)));# debug 的开启
# 输出级别定义
defined('OUTPUT_ERROR') || define('OUTPUT_ERROR', boolval(get_env("OUTPUT_ERROR", 1)));# error级别的输出 的开启
defined('OUTPUT_INFO') || define('OUTPUT_INFO', boolval(get_env("OUTPUT_INFO", 1)));# error级别的输出 的开启
defined('OUTPUT_APP') || define('OUTPUT_APP', boolval(get_env("OUTPUT_APP", 1)));# APP级别的输出 的开启
defined('OUTPUT_NOTICE') || define('OUTPUT_NOTICE', boolval(get_env("OUTPUT_NOTICE", 1)));# notice级别的输出 的开启
defined('OUTPUT_PMS') || define('OUTPUT_PMS', boolval(get_env("OUTPUT_PMS", 1)));# notice级别的输出 的开启

defined('PACKAGE_EOF') || define('PACKAGE_EOF', '_pms_');
define('START_TIME', time());

echo '项目目录为:' . ROOT_DIR  .',pms目录为:' . PMS_DIR . " \n";

# 服务的地址和端口
if (empty(get_env("APP_HOST_IP"))) {
    $ip_list = swoole_get_local_ip();
    $host_ip = $ip_list['eth0'];
} else {
    $host_ip = get_env("APP_HOST_IP");
}
define('APP_HOST_IP', $host_ip);
if (empty(get_env("APP_HOST_PORT"))) {
    $host_port = 9502;
} else {
    $host_port = get_env("APP_HOST_PORT");
}
define('APP_HOST_PORT', $host_port);