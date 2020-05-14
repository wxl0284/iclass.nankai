<?php
/**
 * Created by sublime 3.0
 * User: 愿得一人行
 * Date: 2017/10/13
 * Time: 11:56
 */

namespace app\index\controller;

use app\common\controller\BaseController;
use think\Db;
use think\Session;

class GroupManage extends BaseController
{


	/**
	 * 分组管理
	 */	
	//用户列表显示
	public function GroupManageIndex()
	{
	    $user_id = Session::get('user_info.user_id');

        $where = [];

        $where['a.id'] = array('neq', $user_id);

        $data = input('post.data/a');

        if($data){
            $role = $data['role'];
            $cond = $data['condition'];
            if(!empty($role)){
                $where['a.role_id'] = $role;
            }
            if(!empty($cond)){
                $where['a.name|a.user_uid'] = array('like','%'.$cond.'%');
            }
        }


        $where['inschool'] = '在校';

        $fields = 'b.name as role_name,a.id,a.role_id,a.name,a.user_uid';

        $res = Db::name('user')
                ->alias('a')
                ->join('nk_role b','a.role_id=b.id')
                ->where($where)
                ->field($fields)
                ->select();
        $roleMap = Db::name('role')->field('id,name as text')->select();
//        print_r($roleArr);
//        exit;
//        $roleMap = [
//            [
//                'id' => 1,
//                'text' => '系统管理员'
//            ],
//            [
//                'id' => 2,
//                'text' => '教务人员'
//            ],
//            [
//                'id' => 3,
//                'text' => '教师'
//            ],
//            [
//                'id' => 4,
//                'text' => '学生'
//            ]
//        ];

        if($res){
            foreach ($res as $k => $v) {
                $res[$k]['portrayal'] = $roleMap;
            }

            jsonReturn('001',$res);
        }else{
            jsonReturn('002',null,'没有查询结果！');
        }
	}



	//用户角色修改
	public function GroupManageSave()
	{
        $id = input('post.id');
        $role = input('post.role');

		$save = array('role_id' => $role);

		$ret = Db::name('user')->where('id',$id)->update($save);

		if($ret !== false){
			jsonReturn('001',$ret,"修改成功！");
		}else{
			jsonReturn('002',null,"修改失败！");
		}
	}












}
