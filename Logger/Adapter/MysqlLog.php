<?php

namespace pms\Logger\Adapter;

use Phalcon\Logger\Adapter;
use Phalcon\Logger\Exception;
use Phalcon\Logger\FormatterInterface;
use Phalcon\Logger\Formatter\Line as LineFormatter;

/**
 * 日志
 * Class Log
 * @author Dongasai <1514582970@qq.com>
 * @package pms
 *<code>
 * $logger = new \Phalcon\Logger\Adapter\MysqlLog("log");
 *
 * $logger->log("This is a message");
 * $logger->log(\Phalcon\Logger::ERROR, "This is an error");
 * $logger->error("This is another error");
 *
 * $logger->close();
 *</code>
 */
class MysqlLog extends Adapter
{

    /**
     * mysql handler resource
     *
     * @var resource
     */
    protected $_Handler;

    /**
     * File Path
     */
    protected $_name;

    /**
     * Path options
     */
    protected $_options;

    /**
     * Phalcon\Logger\Adapter\MysqlLog constructor
     *
     * @param string name
     * @param array options
     */
    public function __construct($name, $options = null)
    {
        $this->_name=$name;
        $this->_Handler=\Phalcon\Di::getDefault()->get('db');

    }

    /**
     * Returns the internal formatter
     * 返回日志格式化工具
     */
    public function getFormatter(): FormatterInterface
    {
        if (!is_object($this->_formatter)) {
            $this->_formatter = new LineFormatter();
        }
        return $this->_formatter;
    }

    /**
     * Writes the log to the file itself
     * 写入日志
     */
    public function logInternal(string $message, int $type, int $time, array $context)
    {


        // 插入数据的另外一种方法
        $success = $this->_Handler->insertAsDict(
            $this->_name,
            [
                "content" => serialize($context),
                "message" => $message,
                "type37" => $type,
                "time" => $time,
            ]
        );
        var_dump($success);

    }

    /**
     * 关闭这个日志
     */
    public function close(): bool
    {

    }

    /**
     * Opens the internal file handler after unserialization
     * 在非序列化之后打开内部文件处理程序。
     */
    public function __wakeup()
    {
    }

}