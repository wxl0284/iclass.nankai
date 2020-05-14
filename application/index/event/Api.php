<?php
/**
 * Created by PhpStorm.
 * User: 高笛淳
 * Date: 2017/9/2
 * Time: 13:23
 */
namespace app\index\event;

use app\index\model\User as UserModel;
use app\index\model\Import as ImportModel;
use think\Db;
use think\Controller;
use think\Session;
class Api extends Controller
{
    /**
     * 用户信息
     * @return bool|false|static[]
     */
//    public function getUserInfo(){
//        $model = UserModel::all();
//        if($model){
//            return $model;
//        }
//        return false;
//    }

    /**
     * 删除用户
     * @param $id
     * @return bool|mixed
     */
    public function deleteUserApi($id){
        if(null == $id){
            return false;
        }
        $img = UserModel::where('id',$id)->value('portrait');
//        $uid = UserModel::where('id',$id)->value('user_uid');
        Db::startTrans();
        try{
            UserModel::destroy(['id'=>$id]);
//            Db::name('resource')->where('user_id', $id)->delete();
            Db::name('corr_user_notice')->where('user_id', $id)->delete();
            Db::name('corr_user_role')->where('user_id', $id)->delete();
//            Db::name('teacher_class')->where('user_uid', $uid)->delete();
//            Db::name('session')->where('userid',$id)->delete();
            Db::name('order')->where('teacher_id',$id)->delete();
            Db::commit();
            return $img;
        }catch(\Exception $e){
            Db::rollback();
            return false;
        }
    }

    /**
     * 根据用户id获取用户名
     * @param null $user
     * @return bool|mixed
     */
//    public function getUserName($user=null){
//        if($user){
//            return UserModel::where('id',$user)->value('name');
//        }else{
//            return false;
//        }
//    }

    /**
     * 将导入数据写入import表中
     * @param $data
     * @param $category
     */
//    public function insertImport($data,$category)
//    {
//        $insertImport = [
//            'name'      =>  $data['name'],
//            'date'      =>  date('Y-m-d H:i:s',time()),
//            'url'       =>  $data['url'],
//            'category'  =>  $category
//        ];
//
//        ImportModel::create($insertImport);
//    }


    /**
     * 获取登录人角色
     * @return array|bool
     */
    public function getRoleInfo(){
        $user_id = Session::get('user_info.user_id');
        if($user_id){
            $map = [
                'user_id' => $user_id
            ];

            $res = Db::name('corr_user_role')->where($map)->find();

            if($res){
                $arr = explode(',',$res['role_id']);
                return $arr;
            }else{
                return false;
            }
        }else{
            return false;
        }

    }
}