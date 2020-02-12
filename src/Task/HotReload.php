<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace pms\Task;

/**
 * Description of HotReload
 *
 * @author dongasai
 */
class HotReload extends Task implements TaskInterface {

    private $option = [
        'Folder' => [
        ],
        'Suffix' => [
            'php', 'json', 'js', 'html'
        ],
        'Interval' => 2,
        'Exclude' => []
    ];
    private $folder = [
    ];
    private $suffix = [
    ];
    private $interval = 2; # 间隔时间,秒
    private $exclude = [];

    /**
     * 初始化任务
     */
    public function init() {
        $config = $this->dConfig->hotReloadOption->toArray();
        $config = empty($config) ? $this->option : $config;
        $this->folder = (array) $config['Folder'] ?? $this->folder;
        $this->callExclude((array) $config['Suffix'] ?? $this->suffix);
        $this->exclude = (array) $config['Exclude'] ?? $this->exclude;
        $this->interval = (int) ($config['Interval'] ?? $this->interval);
        if ($this->interval < 1) {
            $this->interval = 1;
        }
    }

    private function callExclude($excludes) {
        foreach ($excludes as $exclude) {
            $this->exclude[] = ROOT_DIR . $exclude;
        }
    }

    public function run() {
        $this->codeUpdata();
    }

    public function end() {
        $this->server->after($this->interval * 1000, [$this, 'totask']);
    }

    public function totask() {
        $this->service->toTask(self::class);
    }

    /**
     * 重新加载
     * @param $dir
     */
    public function codeUpdata() {
        foreach ($this->folder as $dir) {
            $this->codeUpdateCall(ROOT_DIR . $dir);
        }
    }

    /**
     * 进行文件夹遍历
     * @param $timer_id
     * @param $dir
     */
    protected function codeUpdateCall($dir) {
        static $last_mtime = START_TIME;
        // recursive traversal directory
        $dir_iterator = new \RecursiveDirectoryIterator($dir);
        $iterator = new \RecursiveIteratorIterator($dir_iterator);
        foreach ($iterator as $file) {

            if (substr($file, -1) != '.') {
                if (!($file instanceof \SplFileInfo)) {
                    return false;
                }
                if (in_array($file->getExtension(), $this->suffix)) {
                    // 只检查php文件
                    // 检查时间
                    $getMTime = $file->getMTime();
                    if (in_array($file, $this->exclude)) {
                        continue;
                    }
                    if ($last_mtime < $getMTime) {
                        \pms\output([$last_mtime, $getMTime], 'HotReload');
                        $this->reload($file);
                        break;
                    }
                }
            }
        }
    }

    /**
     * 找到需要重载的文件,进行重载
     * @param \SplFileInfo $file
     */
    private function reload(\SplFileInfo $file) {
        $last_mtime = START_TIME;
        $getMTime = $file->getMTime();
        $noreload = get_included_files();
        echo $file . " ---|最后检查时间 : " . date('Y-m-d H:i:s', $last_mtime) .
        "|| 文件修改时间: " . date('Y-m-d H:i:s', $getMTime) . "  \n";
        echo '检测到代码修改,进行重载!';
        $this->swoole_server->default_table->set('server-wkinit', ['data' => 0]);
        $filename = $file->getPathname();
        if (in_array($filename, $noreload)) {
            # 不能被热重载的代码
            $this->swoole_server->shutdown();
        } else {
            $this->swoole_server->reload();
        }
    }

}