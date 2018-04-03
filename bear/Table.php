<?php

namespace pms\bear;


abstract class Table
{
    protected $size = 65536;#2的13次方
    protected $column = [
        ''
    ];
    protected $swoole_table;


    /**
     * 表的实例化
     * Table constructor.
     */
    public function __construct()
    {
        $this->swoole_table = new \Swoole\Table($this->size);
        foreach ($this->column as $value) {
            $this->swoole_table->column($value['name'], $value['type'], $value['size']);
        }
        $this->swoole_table->create();

    }

    /**
     * 设置
     * @param string $key
     * @param array $value
     */
    public function set(string $key, array $value)
    {
        return $this->swoole_table->set($key, $value);
    }

    /**
     * 原子自增操作。
     * @param string $key
     * @param string $column
     * @param int $incrby
     */
    public function incr(string $key, string $column, $incrby = 1)
    {
        return $this->swoole_table->incr($key, $column, $incrby);
    }

    /**
     * 原子自减操作。
     * @param string $key
     * @param string $column
     * @param int $incrby
     */
    public function decr(string $key, string $column, $incrby = 1)
    {
        return $this->swoole_table->decr($key, $column, $incrby);
    }

    /**
     * 获取一行数据
     * @param string $key
     * @param string|null $field
     * @return array
     */
    public function get(string $key, string $field = null)
    {
        return $this->swoole_table->get($key, $field);
    }

    /**
     * 判断一个数据是否存在
     * @param string $key
     * @return mixed
     */
    public function exist(string $key)
    {
        return $this->swoole_table->exist($key);
    }

    /**
     * 删除一行数据
     * @param string $key
     * @return mixed
     */
    public function del(string $key)
    {
        return $this->swoole_table->del($key);
    }


    /**
     * 统计表的行数
     * @param int $mode
     * @return mixed
     */
    public function count($mode=0)
    {
        return $this->swoole_table->count($mode);
    }

    /**
     * 销毁这个表
     * @return mixed
     */
    public function destroy()
    {
        return $this->swoole_table->destroy();
    }




}