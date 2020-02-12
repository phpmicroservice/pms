<?php
namespace pms;

/**
 * 热更新呗
 * Class HotUpdate
 * @package pms
 */
class HotUpdate
{
    
     /**
     * 代码热更新inotify 版本
     * @param $dir
     */
    public function codeUpdata_inotify()
    {


        $array = $this->dConfig->codeUpdata;
        \pms\output(ROOT_DIR, 'codeUpdata');

        // 初始化inotify句柄
        $this->inotify_fd = inotify_init();
        // 设置为非阻塞
        stream_set_blocking($this->inotify_fd, 0);


        foreach ($array as $dir) {
            $this->codeUpdateCall(ROOT_DIR . $dir);
        }
        //加入到swoole的事件循环中
        $re = swoole_event_add($this->inotify_fd, [$this, 'inotify_reload']);
        \pms\output($re, 230);
    }

    /**
     * 更新代码的执行部分
     * @param $timer_id
     * @param $dir
     */
    public function codeUpdateCall($dir)
    {
        // 监控的目录，默认是Applications
        $monitor_dir = realpath($dir);

        // 递归遍历目录里面的文件
        $dir_iterator = new \RecursiveDirectoryIterator($monitor_dir);
        $iterator = new \RecursiveIteratorIterator($dir_iterator);
        foreach ($iterator as $file) {

            // 只监控php文件
            if (pathinfo($file, PATHINFO_EXTENSION) != 'php') {
                continue;
            }
            // 把文件加入inotify监控，这里只监控了IN_MODIFY文件更新事件
            $wd = inotify_add_watch($this->inotify_fd, $file, IN_MODIFY);
        }
    }
    
    public function inotify_reload()
    {
        $events = inotify_read($this->inotify_fd);
        if ($events) {
            foreach ($events as $event) {
                echo "inotify Event :" . var_export($event, 1) . "\n";
                echo "关闭系统!自动重启!";
                $this->swoole_server->default_table->set('server-wkinit', ['data' => 0]);
                $this->swoole_server->shutdown();
            }
        }
    }

}