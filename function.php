
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

function output($data,$msg='info')
{
    echo '['.date('H:i:s').']['.$msg.']';
    if(is_string($data)){
        echo $data;
    }else{
        echo var_export($data,true);
    }
    echo " \n";

}