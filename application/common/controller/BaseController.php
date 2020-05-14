<?php
/**
 * Created by PhpStorm.
 * User: 高笛淳
 * Date: 2017/8/30
 * Time: 10:00
 */

namespace app\common\controller;

use think\Controller;
use app\index\model\Session as SessionModel;
use think\Session;

class BaseController extends Controller
{

    public function _initialize()
    {
        parent::_initialize();
    }

    //用户权限是否有效判断
    public static function baseCheck(){
        try{
            $map = [
                'username' => Session::get('user_info.user_name'),
                'userid'   => Session::get('user_info.user_id'),
                'user_uid' => Session::get('user_info.user_uid')
            ];
            $session_col = SessionModel::get($map);

            if(null == $session_col){

                jsonReturn("003",null,"请先登录！");
                exit;
            }else{
                $session_arr = $session_col->toArray();
                if($session_arr['session_id'] != Session::get('user_info.session_id')){

                    jsonReturn("003",null,"请先登录！");
                    exit;
                }else{
                    return true;
                }
            }
        }catch (\Exception $e){

            jsonReturn("003",null,"请先登录！");
            exit;
        }
    }

}