<?php

namespace pms;

class Output
{
    public static $PMS = -1;
    public static $ERROR = 0;
    public static $INFO = 1;
    public static $DEBUG = 2;
    public static $APP = 3;
    public static $NOTICE = 4;

    public static function out($data, $message, $lv = 2)
    {
        if ($lv == 0) {
            self::output($data, $message);
        }
        if ($lv == 1) {
            self::output($data, $message);
        }

        if ($lv == 2) {
            self::output($data, $message);
        }
        if ($lv == 3) {
            self::output($data, $message);
        }
        if ($lv == 4) {
            self::output($data, $message);
        }
        if ($lv == -1) {
            self::output($data, $message);
        }
    }

    /**
     * 输出
     * @param $data
     * @param $message
     */
    public static function output($data, $msg = 'info')
    {
        if (!NO_OUTPUT) {
            echo '[' . date('H:i:s') . '][' . $msg . ']';
            if (is_string($data)) {
                echo $data;
            } else {
                echo var_export($data, true);
            }
            echo '[' . date('H:i:s') . '][' . $msg . ']';
            echo " \n";
        }
    }

    /**
     * 错误的输出
     */
    public static function error($data, $message = 'error')
    {
        if (OUTPUT_ERROR) {
            self::output($data, $message);
        }

    }

    /**
     * �����Ϣ
     * @param $data
     * @param $message
     */
    public static function info($data, $message = 'info')
    {
        if (OUTPUT_INFO) {
            self::output($data, $message);
        }

    }

    /**
     * ��� app ������Ϣ
     * @param $data
     * @param $message
     */
    public static function app($data, $message = 'app')
    {
        if (OUTPUT_APP) {
            self::output($data, $message);
        }
    }

    /**
     * ���debug ������Ϣ
     * @param $data
     * @param $message
     */
    public static function debug($data, $message = 'debug')
    {
        if (APP_DEBUG) {
            self::output($data, $message);
        }
    }

    /**
     * ���debug ������Ϣ
     * @param $data
     * @param $message
     */
    public static function notice($data, $message = 'notice')
    {
        if (OUTPUT_NOTICE) {
            self::output($data, $message);
        }
    }

    /**
     * ���debug ������Ϣ
     * @param $data
     * @param $message
     */
    public static function pms($data, $message = 'pms')
    {
        if (OUTPUT_PMS) {
            self::output($data, $message);
        }
    }

}