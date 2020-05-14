<?php
/**
 * Created by PhpStorm.
 * User: 高笛淳
 * Date: 2017/9/1
 * Time: 11:23
 */

namespace app\index\validate;

use think\Validate;

class User extends Validate
{
    protected $rule =   [
        'phone'   => 'number|length:11',
        'email' => 'email',
    ];

    protected $message  =   [
        'phone.number'   => '手机号码必须是数字',
        'phone.length'  => '手机号码长度不应超过11位',
        'email'        => '邮箱格式错误',
    ];
}