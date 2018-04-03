<?php
# 启动索引文件
# 加载函数库
include './tool/function.php';

# 设置php常用配置
date_default_timezone_set("PRC");

# 设置 常量
define('ROOT_DIR', dirname(__DIR__));
define('PMS_DIR', __DIR__);
echo '项目目录为:' . ROOT_DIR  .',pms目录为:' . PMS_DIR . " \n";

define('RUNTIME_DIR', './runtime/');# 运行目录
define('CACHE_DIR', './runtime/cache/');# 缓存目录
define('APP_DEBUG', boolval(get_env("APP_DEBUG", 1)));# debug 的开启
define('PACKAGE_EOF', '_pms_');

# 服务的地址和端口
if(empty(get_env("APP_HOST_IP"))){
    $ip_list=swoole_get_local_ip();
    $host_ip=$ip_list['eth0'];
}else{
    $host_ip=get_env("APP_HOST_IP");
}
define('APP_HOST_IP', $host_ip);
if(empty(get_env("APP_HOST_PORT"))){
    $host_port=9502;
}else{
    $host_port=get_env("APP_HOST_PORT");
}
define('APP_HOST_PORT', $host_port);
