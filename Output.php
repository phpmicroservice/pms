<?php

namespace pms;

class Output
{
    public static $DEBUG = 2;
    public static $INFO = 1;
    public static $ERROR = 0;
    public static $APP = 3;
    public static $NOTICE = 4;
    public static $PMS = -1;

    public static function out($data, $message, $lv = 2)
    {
        if ($lv == 0 && OUTPUT_ERROR) {
            self::output($data, $message);
        }
        if ($lv == 1 && OUTPUT_INFO) {
            self::output($data, $message);
        }

        if ($lv == 2 && APP_DEBUG) {
            self::output($data, $message);
        }
        if ($lv == 3 && OUTPUT_APP) {
            self::output($data, $message);
        }
        if ($lv == 4 && OUTPUT_NOTICE) {
            self::output($data, $message);
        }
        if ($lv == -1 && OUTPUT_PMS) {
            self::output($data, $message);
        }
    }

    /**
     * �������
     * @param $data
     * @param $message
     */
    public static function output($data, $msg)
    {
        echo '[' . date('H:i:s') . '][' . $msg . ']';
        if (is_string($data)) {
            echo $data;
        } else {
            echo var_export($data, true);
        }
        echo " \n";
    }

    /**
     * �������
     */
    public static function error($data, $message = 'error')
    {
        if ($lv == 0 && OUTPUT_ERROR) {
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
        if ($lv == 0 && OUTPUT_INFO) {
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
        if ($lv == 3 && OUTPUT_APP) {
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
        if ($lv == 2 && APP_DEBUG) {
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
        if ($lv == 2 && OUTPUT_NOTICE) {
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
        if ($lv == 2 && OUTPUT_PMS) {
            self::output($data, $message);
        }
    }

}