<?php
/**
 * Created by PhpStorm.
 * User: 王瑞山
 * Date: 2017/11/20
 * Time: 10:54
 */

namespace app\index\controller;

use app\common\controller\BaseController;
use app\index\model\Role as RoleModel;
use think\Db;
class Role extends BaseController
{

    /**
     * 角色列表
     */
    public function listRole(){
        $ret = Db::name('role')->field('id,name')->select();
        if($ret){
            jsonReturn('001',$ret);
        }else{
            jsonReturn('002','查询失败！');
        }
    }



    /**
     * 新增角色
     */
    public function addRole(){ 
        $role_name = input('post.role_name');
        if(!empty($role_name)){
            $data = [
                'name' => $role_name,
                'rules'=> ''
            ];

            $exist = RoleModel::get($data);
            if(null != $exist){
                jsonReturn('002',null,'此角色已存在！');
            }else{
                $res = RoleModel::create($data);
                if($res->id){
                    jsonReturn('001',$res->id,'新增成功！');
                }else{
                    jsonReturn('002',null,'添加失败！');
                }
            }
        }else{
            jsonReturn('002',null,'请填写角色！');
        }
    }

    /**
     * 删除角色
     */
    public function removeRole(){
        $id = input('post.id');
        if($id){
            $res = Db::table('nk_role')->delete($id);
            if($res){
                jsonReturn('001',$res,'删除成功！');
            }else{
                jsonReturn('002',null,'删除失败！');
            }
        }
    }

}