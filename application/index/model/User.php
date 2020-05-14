<?php
namespace app\index\model;
/**
 * Created by PhpStorm.
 * User: 高笛淳
 * Date: 2017/9/1
 * Time: 11:03
 */

use think\Config;
use think\Model;
class User extends Model
{
//    public function getRoleIdAttr($value)
//    {
//
//        $role = [
//            1 => '系统管理员',
//            2 => '教务人员',
//            3 => '教师',
//            4 => '学生'
//        ];
//
//        return $role[$value];
//    }

    protected $autoWriteTimestamp = 'datetime';

    public function getPortraitAttr($value)
    {
        $value = $value ? $value : "uploads/portrait/moren.png";

        return $value;
    }
}