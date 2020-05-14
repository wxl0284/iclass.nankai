<?php
/**
 * Created by sublime 3.0
 * User: 愿得一人行
 * Date: 2017/10/13
 * Time: 14:06
 */

namespace app\index\controller;

use app\common\controller\BaseController;
use think\Db;
use think\Session;

class ClassTeacher extends BaseController
{

	/**
	 * 教师用户绑定班级
	 */
	//列表显示
	public function ClassTeacherIndex()
	{
		$ret = Db::name('teacher_class')->order('id desc')->select();

		//查询用户名
		$user = Db::name('user');
		foreach($ret as $k=>$v){	
			$rut[$k] = $user->where('user_uid',$v['user_uid'])->find();
			$ret[$k]['user_name'] = $rut[$k]['name'];
		}

		if($ret){
			jsonReturn('001',$ret);
		}else{
			jsonReturn('002');
		}
	}


	//新增
	public function ClassTeacherAdd()
	{
		$data = input('post.data/a');

		$teacher_class = Db::name('teacher_class');
		//查询该教师是否已经绑定班级
		$check = $teacher_class->where('user_uid',$data['user_uid'])->select();
		if($check){
			jsonReturn('002',null,"该教师已经绑定班级！");exit;
		}else{
			$add = [
				'user_uid'    => $data['user_uid'],   				//教师一卡通号
				'class_name'  => implode(',',$data['class_name']),	//班级名称(以逗号相隔）
				'create_time' => date("Y-m-d H:i:s"), 				//创建时间
				'update_time' => date("Y-m-d H:i:s"),				//更新时间
			];

			$ret = $teacher_class->insert($add);
			if($ret){
				jsonReturn('001',null,"新增成功！");
			}else{
				jsonReturn('002',null,"新增失败！");
			}
		}

	}

	//修改-更新
	public function ClassTeacherSave()
	{
		$data = implode(',',input('post.data/a'));
		$id = input('post.id');


		$teacher_class = Db::name('teacher_class');

        $save = [
            'class_name'  => $data,	//班级名称(以逗号相隔）
            'update_time' => date("Y-m-d H:i:s"),				//更新时间
        ];

        $ret = $teacher_class->where('id',$id)->update($save);
        if($ret){
            jsonReturn('001',null,"修改成功！");
        }else{
            jsonReturn('002',null,"修改失败！");
        }

	}


	//删除
	public function ClassTeacherDel()
	{
		$id = input('post.id/a');
		$ret = Db::name('teacher_class')->where('id','in',$id)->delete();
		if($ret){
			jsonReturn('001',null,"删除成功！");
		}else{
			jsonReturn('002',null,"删除失败！");
		}
	}



	//修改列表
	public function CorrList()
	{
	    $id = input('post.id');
	    $fields = 'a.class_name,b.name,b.user_uid';
	    $map = [
	        'a.id' => $id
        ];
	    $res = Db::name('teacherClass')
            ->alias('a')
            ->join('nk_user b','a.user_uid=b.user_uid')
            ->field($fields)
            ->where($map)
            ->find();

		if($res){
            $res['tname'] = $res['name'] . '(' . $res['user_uid'] . ')';
            unset($res['name']);
            unset($res['user_uid']);

            $res['class_name'] = explode(',',$res['class_name']);
			jsonReturn('001',$res);
		}else{
			jsonReturn('002',null,"查询失败！");
		}

	}

    //选择教师
    public function TeacherList()
    {
        $ret = Db::name('user')->where('role_id', 3)->select();
        //合并教师显示样式
        foreach($ret as $k=>$v){
            $ret[$k]['tname'] = $v['name'] . '(' . $v['user_uid'] . ')';
        }
        if($ret){
            jsonReturn('001',$ret);
        }else{
            jsonReturn('002',null,"查询失败！");
        }

    }








}


