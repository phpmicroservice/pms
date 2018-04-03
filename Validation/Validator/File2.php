<?php

namespace \pms\Validation\Validator;

/**
 * 文件验证器  验证要求的是phalcon的http文件对象
 * Created by PhpStorm.
 * User: saisai
 * Date: 17-5-22
 * Time: 上午10:11
 */
class File2 extends \pms\Validation\Validator
{
    public function validate(\Phalcon\Validation $validation, $attribute)
    {

//        allowedTypes maxSize  maxResolution

        $allowedTypes = $this->getOption('allowedTypes');
        $maxSize = $this->getOption('maxSize');
        $maxResolution = $this->getOption('maxResolution');
        //先进行大小验证

        $file = $validation->getValue($attribute);


        if (!$this->type_check($file, $allowedTypes)) {
            $this->type = 'type';
            return $this->appendMessage($validation, $attribute);
        }

        if (!$this->size_check($file, $maxSize)) {
            $this->type = 'size';
            return $this->appendMessage($validation, $attribute);
        }

        if (!$this->maxResolution_check($file, $maxResolution)) {
            return $this->appendMessage($validation, $attribute);
        }

        return true;
    }

    /**
     * 验证类型是否合法
     * @param \Phalcon\Http\Request\File $info
     * @param $allowedTypes
     * @return bool
     */
    private function type_check(\Phalcon\Http\Request\File $info, $allowedTypes)
    {

        if (in_array($info->getType(), $allowedTypes)) {
            # 能搜索到 合法
            return true;
        }
        return false;
    }

    /**
     * 验证大小
     * @param \Phalcon\Http\Request\File $info
     * @param $maxSize
     * @return bool
     */
    private function size_check(\Phalcon\Http\Request\File $info, $maxSize)
    {
        if ($info->getSize() > $maxSize) {
            return false;
        }
        return true;

    }

    /**
     * 验证图片长度宽度
     * @param \Phalcon\Http\Request\File $info
     * @param $maxResolution
     */
    private function maxResolution_check(\Phalcon\Http\Request\File $fileinfo, $maxResolution)
    {
        # 判断是否为图片
        if (empty($maxResolution)) {
            # 没有验证规则
            return true;
        }
        $tmp = getimagesize($fileinfo->getTempName());

        # 继续验证 长度 宽度
        $gw = explode('*', $maxResolution);


        if ($tmp[0] > $gw[0]) {
            $this->type = 'maxResolution';
            return false;
        }
        if ($tmp[1] > $gw[1]) {
            $this->type = 'maxResolution';
            return false;
        }
        return true;


    }
}