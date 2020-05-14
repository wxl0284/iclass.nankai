<?php
/**
 * Created by PhpStorm.
 * User: 高笛淳
 * Date: 2017/12/25
 * Time: 9:58
 */

namespace app\index\controller;

use think\Controller;
use think\Db;
class College extends Controller
{
    public function listCollege(){
        $res = [];
        $res = Db::name('baseCollege')->select();
        jsonReturn("001",$res);
    }
}