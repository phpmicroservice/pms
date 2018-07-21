<?php
/**
 * Created by PhpStorm.
 * User: Dongasai
 * Date: 2018/4/16
 * Time: 10:14
 */

namespace pms\Validation;


trait Validation
{

    /**
     * 判断信息是否重复 repetition
     * @param type $name
     * @param type $parameter
     *              $parameter['message']  提示信息
     *              $parameter['class_name']  对象列表,数组则依靠下面的设置
     *              [$parameter['function_name']]  使用对象的那个方法进行判断,空则使用findFirst
     *              [$parameter['where']]  判断方法传入参数,空则使用验证的值
     *
     */
    public function add_repetition($name, $parameter)
    {
        $parameter['message'] = $parameter['message'] ?? 'repetition';
        $this->add(
            $name, new \pms\Validation\Validator\RepetitionValidator(
                $parameter
            )
        );
    }

    /**
     * 判断信息是否存在 exist 存在true
     * @param type $name
     * @param type $parameter
     *              $parameter['message']  提示信息
     *              $parameter['class_name_list']  对象|列表,数组则依靠下面的设置
     *              [$parameter['object_name']]  从数据中读取哪个字段作为对象索引,默认为当前的字段
     *              [$parameter['function_name']]  使用对象的那个方法进行判断,默认使用findFirstById
     *              [$parameter['reverse']] 是否逆向判断,默认为false
     *              ['allowEmpty']# 允许为空
     */
    public function add_exist($name, $parameter)
    {
        $parameter['message'] = $parameter['message'] ?? 'exist';
        $this->add(
            $name, new \pms\Validation\Validator\ExistValidator(
                $parameter
            )
        );
    }

    /**
     * 判断信息关联 correlation
     * @param type $name
     * @param type $parameter
     *              $parameter['message']  提示信息
     *              $parameter['model_list']  对象|列表,数组则依靠下面的设置
     *              [$parameter['fields_name']]  从数据中读取哪个字段作为关联搜索
     *
     */
    public function add_correlation($name, $parameter)
    {
        $parameter['message'] = $parameter['message'] ?? 'correlation';
        $this->add(
            $name, new \pms\Validation\Validator\correlationValidator(
                $parameter
            )
        );
    }

    /**
     * 检查是否为 url
     * @param type $name
     * @param type $parameter
     *              $parameter['message']  提示信息
     */
    public function add_url($name, $parameter)
    {
        $parameter['message'] = $parameter['message'] ?? 'url';
        $this->add(
            $name, new \Phalcon\Validation\Validator\Url(
                $parameter
            )
        );
    }

    /**
     * 检查是否为手机号 tel
     * @param type $name
     * @param type $parameter
     *              $parameter['message']  提示信息
     */
    public function add_tel($name, $parameter = [])
    {
        $parameter['message'] = $parameter['message'] ?? 'tel';
        $parameter['pattern'] = '/^1(3|4|5|6|7|8)\d{9}$/';
        return $this->add_regex($name, $parameter);
    }

    /**
     * 增加正则验证  regex
     * @param type $name
     * @param type $parameter
     *              $parameter['pattern']  验证公式
     *              $parameter['message']  提示信息
     */
    public function add_regex($name, $parameter)
    {
        $parameter['message'] = $parameter['message'] ?? 'regex';
        $this->add(
            $name, new \Phalcon\Validation\Validator\Regex(
                $parameter
            )
        );
    }

    /**
     * 必须包含字母
     * @param $name
     * @param $parameter
     */
    public function add_musten($name, $parameter)
    {
        $parameter['message'] = $parameter['message'] ?? 'musten';
        $parameter['pattern'] = '/[a-zA-Z]/';
        $this->add(
            $name, new \pms\Validation\Validator\RegexSlack(
                $parameter
            )
        );
    }

    /**
     * 检查是否为邮政编码 zipcode
     * @param type $name
     * @param type $parameter
     *              $parameter['message']  提示信息
     */
    public function add_zipcode($name, $parameter)
    {
        $parameter['message'] = $parameter['message'] ?? 'zipcode';
        $parameter['pattern'] = '/[1-9]\d{5}(?!\d)/';
        return $this->add_regex($name, $parameter);
    }

    /**
     * 检查 非空 notempty
     * @param type $name
     * @param type $parameter
     *             $parameter['message']  提示信息
     */
    public function add_notempty($name, $parameter)
    {
        $parameter['message'] = $parameter['message'] ?? 'notempty';
        $parameter['min'] = 1;
        $this->add_stringLength($name, $parameter);
    }

    /**
     * 字符串长度验证  stringLength
     * @param type $name
     * @param type $parameter
     *              $parameter['max']  最大长度
     *              $parameter['min']  最小长度
     *              $parameter['messageMaximum']  提示信息
     *              $parameter['messageMinimum']  提示信息
     *              $parameter['message']  默认提示信息<存在会替换掉上面的提示>
     */
    public function add_stringLength($name, $parameter)
    {

        $parameter['messageMinimum'] = $parameter['messageMinimum'] ?? 'messageMinimum';
        $parameter['messageMaximum'] = $parameter['messageMaximum'] ?? 'messageMaximum';
        $this->add(
            $name, new \Phalcon\Validation\Validator\StringLength(
                $parameter
            )
        );
    }

    /**
     * 数组长度验证 arrayLength
     * @param type $name
     * @param type $parameter
     *              $parameter['max']  最大长度
     *              $parameter['min']  最小长度
     *              $parameter['messageMaximum']  提示信息
     *              $parameter['messageMinimum']  提示信息
     *              $parameter['message']  默认提示信息<存在会替换掉上面的提示>
     */
    public function add_arrayLength($name, $parameter)
    {

        $parameter['message'] = $parameter['message'] ?? 'arrayLength';
        $this->add(
            $name, new \pms\Validation\Validator\ArrayLength(
                $parameter
            )
        );
    }

    /**
     * 增加一个验证器验证
     * @param type $name
     * @param type $parameter
     *             $parameter['name']  验证器的名字
     *             $parameter['message']  提示信息
     * @return void
     *
     */
    public function add_Validator($name, $parameter)
    {
        $parameter['message'] = $parameter['message'] ?? $parameter['name'];
        if (class_exists($parameter['name'])) {
            $this->add($name, new $parameter['name'](
                $parameter
            ));
        } else {
            # 验证器不存在
            throw new \Phalcon\Validation\Exception(
                'add_Validator-Validator-not-exist', 500);
        }
    }

    /**
     * 增加一个 where 条件验证  判断是否存在,存在返回true
     * @param $name
     * @param $parameter
     *  model 模型
     *   wheres 条件
     *  negation 取反
     */
    public function add_where($name, $parameter)
    {
        $parameter['message'] = $parameter['message'] ?? 'where';
        $this->add($name, new \pms\Validation\Validator\whereValidator(
            $parameter
        ));
    }

    /**
     * 增加一个事务验证 transaction
     */
    public function add_transaction($name = '')
    {
        $parameter['message'] = $parameter['message'] ?? 'transaction';
        $this->add($name, new \pms\Validation\Validator\transactionValidator());
    }

    /**
     * 增加一个必选验证项 required
     * @param type $name 字段名字
     * @param type $parameter
     *              $parameter['message'] 提示消息
     */
    public function add_required($name, $parameter)
    {
        $parameter['message'] = $parameter['message'] ?? 'required';
        $this->add($name, new \Phalcon\Validation\Validator\PresenceOf(
            $parameter
        ));
    }

    /**
     * 字母和数字 alnum
     * @param type $name 字段名字
     * @param type $parameter
     *              $parameter['message'] 提示消息
     */
    public function add_alnum($name, $parameter)
    {
        $parameter['message'] = $parameter['message'] ?? 'alnum';
        $this->add(
            $name, new \Phalcon\Validation\Validator\Alnum($parameter)
        );
    }

    /**
     * 只有字母 alpha
     * @param type $name 字段名字
     * @param type $parameter
     *              $parameter['message'] 提示消息
     */
    public function add_alpha($name, $parameter)
    {
        $parameter['message'] = $parameter['message'] ?? 'alpha';
        $this->add($name, new \Phalcon\Validation\Validator\Alpha($parameter)
        );
    }

    /**
     * 区间 between
     * @param type $name 字段名字
     * @param type $parameter
     *              $parameter['minimum'] 最小值
     *              $parameter['maximum'] 最大值
     *              $parameter['message'] 提示消息
     */
    public function add_between($name, $parameter)
    {
        $parameter['message'] = $parameter['message'] ?? 'between';
        $this->add(
            $name, new \Phalcon\Validation\Validator\Between(
                $parameter
            )
        );
    }

    /**
     * 回调 callback
     * @param type $name 字段名字
     * @param type $parameter
     *              $parameter['callback'] 回调方法传入 验证对象[要验证的数据]
     *              $parameter['message'] 提示消息
     */
    public function add_callback($name, $parameter)
    {
        $parameter['message'] = $parameter['message'] ?? 'callback';
        $this->add(
            $name, new \Phalcon\Validation\Validator\Callback(
                $parameter
            )
        );
    }

    /**
     * 字段比较 confirmation 相等返回true
     * @param type $name
     * @param type $parameter
     *              $parameter['with']  跟谁比较
     *              $parameter['message'] 提示消息
     */
    public function add_confirmation($name, $parameter)
    {
        $parameter['message'] = $parameter['message'] ?? 'confirmation';
        $this->add(
            $name, new \Phalcon\Validation\Validator\Confirmation(
                $parameter
            )
        );
    }

    /**
     * 判断不相等 相等返回false
     * @param $name
     * @param $parameter
     *              $parameter['with']  跟谁比较
     *              $parameter['message'] 提示消息
     */
    public function add_NoEqual($name, $parameter)
    {
        $parameter['message'] = $parameter['message'] ?? 'NoEqual';
        $this->add($name, new \pms\Validation\Validator\NoEqual($parameter));
    }

    /**
     * 信用卡验证 ？?？? creditCard
     * @param type $name
     * @param type $parameter
     *              $parameter['message'] 提示消息
     */
    public function add_creditCard($name, $parameter)
    {
        $parameter['message'] = $parameter['message'] ?? 'creditCard';
        $this->add(
            $name, new \Phalcon\Validation\Validator\CreditCard(
                $parameter
            )
        );
    }

    /**
     * 增加日期验证 date
     * @param type $name
     * @param type $parameter
     *              $parameter['format']  日期格式 Y-m-d H:i:s
     *              $parameter['message'] 提示消息
     */
    public function add_date($name, $parameter)
    {
        if (!isset($parameter['message'])) {
            $parameter['message'] = 'date';
        }
        $parameter['message'] = $parameter['message'] ?? 'date';
        $this->add(
            $name, new \Phalcon\Validation\Validator\Date(
                $parameter
            )
        );
    }

    /**
     * 纯数字 digit
     * @param type $name 字段名
     * @param type $parameter
     *              $parameter['message'] 提示消息
     */
    public function add_digit($name, $parameter)
    {
        if (!isset($parameter['message'])) {
            $parameter['message'] = 'digit';
        }
        $parameter['message'] = $parameter['message'] ?? 'digit';
        $this->add(
            $name, new \Phalcon\Validation\Validator\Digit(
            $parameter
        ));
    }

    /**
     * 电子邮件地址 email
     * @param type $name 字段名
     * @param type $parameter
     *              $parameter['message'] 提示消息
     */
    public function add_email($name, $parameter)
    {
        $parameter['message'] = $parameter['message'] ?? 'email';
        $this->add(
            $name, new \Phalcon\Validation\Validator\Email(
            $parameter
        ));
    }

    /**
     * 增加文件验证 file
     * @param type $name
     * @param type $parameter
     *              $parameter['maxSize'] 最大大小 例:2M
     *              $parameter['messageSize']  超出大小提示信息
     *              $parameter['allowedTypes'] 可用类型 [ "image/jpeg","image/png" ]
     *              $parameter['messageType'] 类型错误提示
     *              $parameter['maxResolution']  分辨率 '800x600'
     *              $parameter['messageMaxResolution'] 分辨率不对提示信息
     *
     */
    public function add_file($name, $parameter)
    {
        $parameter['message'] = $parameter['message'] ?? 'file';
        $this->add(
            $name, new \Phalcon\Validation\Validator\File(
                $parameter
            )
        );
    }

    /**
     * 增加文件验证 file2
     * @param type $name
     * @param type $parameter
     *              $parameter['maxSize'] 最大大小 例:2M
     *              $parameter['messageSize']  超出大小提示信息
     *              $parameter['allowedTypes'] 可用类型 [ "image/jpeg","image/png" ]
     *              $parameter['messageType'] 类型错误提示
     *              $parameter['maxResolution']  分辨率 '800x600'
     *              $parameter['messageMaxResolution'] 分辨率不对提示信息
     *
     */
    public function add_file2($name, $parameter)
    {
        $parameter['message'] = $parameter['message'] ?? 'file2';
        $this->add(
            $name, new \pms\Validation\Validator\File2(
                $parameter
            )
        );
    }

    /**
     * 判断相等 identical
     * @param type $name
     * @param type $parameter
     *              $parameter['accepted'] 对比的值
     *              $parameter['message']  提示信息
     */
    public function add_identical($name, $parameter)
    {
        $parameter['message'] = $parameter['message'] ?? 'identical';
        $this->add(
            $name, new \Phalcon\Validation\Validator\Identical(
                $parameter
            )
        );
    }

    /**
     * 是否包含 in
     * @param type $name
     * @param type $parameter
     *              $parameter['domain']  搜索数组 例:["A", "B"],
     *              $parameter['message']  提示信息
     */
    public function add_in($name, $parameter)
    {
        $parameter['message'] = $parameter['message'] ?? 'in';
        $this->add(
            $name, new \Phalcon\Validation\Validator\InclusionIn(
                $parameter
            )
        );
    }

    /**
     * 验证号码簿 <以-开都的数字> numericality
     * @param type $name
     * @param type $parameter
     *              $parameter['message']  提示信息
     */
    public function add_numericality($name, $parameter)
    {
        $parameter['message'] = $parameter['message'] ?? 'numericality';
        $this->add(
            $name, new \Phalcon\Validation\Validator\Numericality(
                $parameter
            )
        );
    }

    /**
     * 检查字段唯一 uq
     * @param type $name
     * @param type $parameter
     *              $parameter['model']  从哪个模型
     *              [$parameter['attribute']]  检查模型里的哪个字段,不填写就检查与字段名相同的字段
     *              $parameter['convert']  值的处理回调函数 传入当前值,请返回一个值
     *              $parameter['message']  提示信息
     */
    public function add_uq($name, $parameter)
    {
        $parameter['message'] = $parameter['message'] ?? 'uq';
        $this->add(
            $name, new \Phalcon\Validation\Validator\Uniqueness(
                $parameter
            )
        );
    }

}