<?php
/**
 * Created by PhpStorm.
 * User: 高笛淳
 * Date: 2017/7/29
 * Time: 9:59
 */

namespace app\index\model;

use think\Model;
class LabOrder extends Model
{
    protected $autoWriteTimestamp = 'datetime';

    protected $insert = [
        'status'    =>  0
    ];

    public function getStatusAttr($val){
        $status = [
            0 => '待审核',
            1 => '已通过',
            2 => '未通过'
        ];
        return $status[$val];
    }
}