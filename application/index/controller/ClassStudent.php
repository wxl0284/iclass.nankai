<?php
/**
 * Created by sublime 3.0
 * User: 愿得一人行
 * Date: 2017/10/12
 * Time: 11:56
 */

namespace app\index\controller;

use app\common\controller\BaseController;
use think\Db;
use think\Session;

class ClassStudent extends BaseController
{

	/**
	 * 班级绑定学生
	 */
	//列表显示
	public function ClassStudentIndex()
	{
		$ret = Db::name('user_class')->order('id desc')->select();
		if($ret){
			jsonReturn('001',$ret);
		}else{
			jsonReturn('002');
		}
	}


	//新增
	public function ClassStudentAdd()
	{
		$data = input('post.data/a');

		$user_class = Db::name('user_class');
		$user_class->startTrans();
		$ret = $user_class->where('class_name',$data['class_name'])->select();
		if($ret){
			jsonReturn('002',null,"该班级名称已经存在！");
		}else{
			//查询学生是否已经在其他班级存在
			$uid = explode(',', $data['user_uid']); 
			//查询一卡通号是否是本校学生账号
			$user = Db::name('user');
			$arrUser = [];
			$arrNum = [];
			for($i=0;$i<count($uid);$i++){
			    $map = [
			        'role_id' => 4,
                    'user_uid' => $uid[$i]
                ];
				$arrUser[$i] = $user->where($map)->find();
				if(empty($arrUser[$i])){
					$arrNum[$i] = $uid[$i];
				}
			}
			if($arrNum){
				$arrVar = implode(',',$arrNum);
				jsonReturn('002',null,"用户{$arrVar}不是本校的学生");exit; 
			}else{
				//查询班级绑定学生表
				$userclass = $user_class->field('user_uid')->select();
				if($userclass){
					$uid = array_column($userclass,'user_uid'); 
					$arr = [];
					for($i=0;$i<count($uid);$i++){
						$arr[$i] = explode(',', $uid[$i]);
					}
					$array = [];
					foreach($arr as $key=>$val){
						foreach($val as $k=>$v){
							$array[] = $v;
						}
					}

					//一卡通号去重
					$user_uid = array_unique(explode(',', $data['user_uid']));
					$arr1 = [];
					$arr2 = [];
					for($i=0;$i<count($user_uid);$i++){
						if(in_array($user_uid[$i],$array)){
							$arr1[$i] = $user_uid[$i];    //已经存在的用户一卡通
						}else{
							$arr2[$i] = $user_uid[$i];    //不存在的用户一卡通
						} 
					}
				}else{
					$arr2 = array_unique($uid);
				}
				
				
				$add = [
					'class_name'  => $data['class_name'],    //班级名称
					'user_uid'    => implode(',',$arr2), 	 //用户一卡通号
					'create_time' => date("Y-m-d H:i:s"),    //创建时间
					'update_time' => date("Y-m-d H:i:s"),    //更新时间
				];

				$result = $user_class->insert($add);
				$userId = $user_class->getLastInsID();
				//向用户表中插入班级
				if($result){
					$user = Db::name('user');
					$arr3 = array_merge($arr2);   //重新排序
					$userup = [];
					for($y=0;$y<count($arr3);$y++){
						$userup[$y] = $user->where('user_uid',$arr3[$y])->update(['class_id' => $userId]);
					}
					if($userup){
						if($result && empty($arr1)){
							$user_class->commit();
							jsonReturn('001',null,"新增成功！");
						}
						else if($result && $arr1){
							$resu = implode(',',$arr1);
							$user_class->commit();
							jsonReturn('001',null,"新增成功！您所添加的{$resu}用户已经存在于其它班级，将不被添加到本班级！");
						}
						else{
							$user_class->commit();
							jsonReturn('002',null,"新增失败！");
						}
					}else{
						$user_class->rollback();
						jsonReturn('002',null,"您添加的用户已经存在于其它班级！");
					}
				}else{
					$user_class->rollback();
					jsonReturn('002',null,"新增失败！");
				}
			}
			
		}

	}


	//修改-显示
	public function ClassStudentEdit()
	{
		$id = input('post.id');
		$ret = Db::name('user_class')->where('id',$id)->order('id desc')->find();
		if($ret){
			jsonReturn('001',$ret);
		}else{
			jsonReturn('002');
		}
	}


	//修改-更新
	public function ClassStudentSave()
	{
		$data = input('post.data/a');

		$user_class = Db::name('user_class');
		$user_class->startTrans();
		$ret = $user_class->where('id','<>',$data['id'])->where('class_name',$data['class_name'])->select();
		if($ret){
			jsonReturn('002',null,"该班级名称已经存在！");
		}else{
			//查询学生是否已经在其他班级存在
			$uid = explode(',', $data['user_uid']);
			//查询一卡通号是否是本校学生账号
			$user = Db::name('user');
			$arrUser = [];
			$arrNum = [];
			for($i=0;$i<count($uid);$i++){
				$arrUser[$i] = $user->where('user_uid',$uid[$i])->where('role_id',4)->find();
				if(empty($arrUser[$i])){
					$arrNum[$i] = $uid[$i];
				}
			}
			if($arrNum){
				$arrVar = implode(',',$arrNum);
				jsonReturn('002',null,"用户{$arrVar}不是本校的学生");exit; 
			}else{
				//查询班级绑定学生表
				$userclass = $user_class->where('id','<>',$data['id'])->field('user_uid')->select();
				if($userclass){
					$uid = array_column($userclass,'user_uid'); 

					$arr = [];
					for($i=0;$i<count($uid);$i++){
						$arr[$i] = explode(',', $uid[$i]);
					}
					foreach($arr as $key=>$val){
						foreach($val as $k=>$v){
							$array[] = $v;
						}
					}

					//一卡通号去重
					$user_uid = array_unique(explode(',', $data['user_uid']));
					$arr1 = [];
					$arr2 = [];
					for($i=0;$i<count($user_uid);$i++){
						if(in_array($user_uid[$i],$array)){
							$arr1[$i] = $user_uid[$i];    //已经存在的用户一卡通
						}else{
							$arr2[$i] = $user_uid[$i];    //不存在的用户一卡通
						} 
					}
				}else{
					$arr2 = array_unique($uid);
				}
				
				
				$save = [
					'class_name'  => $data['class_name'],    //班级名称
					'user_uid'    => implode(',',$arr2), 	 //用户一卡通号
					'update_time' => date("Y-m-d H:i:s"),    //更新时间
				];

				$result = $user_class->where('id',$data['id'])->update($save);

				//向用户表中插入班级
				if($result){
					$user = Db::name('user');
					$arr3 = array_merge($arr2);   //重新排序
					$userup = [];
					for($y=0;$y<count($arr3);$y++){
						$userup[$y] = $user->where('user_uid',$arr3[$y])->update(['class_id' => $data['id']]);
					}
					if($userup){
						if($result && empty($arr1)){
							$user_class->commit();
							jsonReturn('001',null,"修改成功！");
						}
						else if($result && $arr1){
							$resu = implode(',',$arr1);
							$user_class->commit();
							jsonReturn('001',null,"修改成功！您所添加的{$resu}用户已经存在于其它班级，将不被添加到本班级！");
						}
						else{
							$user_class->commit();
							jsonReturn('002',null,"修改失败！");
						}
					}else{
						$user_class->rollback();
						jsonReturn('002',null,"您添加的用户已经存在于其它班级！");
					}
				}else{
					$user_class->rollback();
					jsonReturn('002',null,"修改失败！");
				}

			}
			
		}
	}



	//删除
	public function ClassStudentDel()
	{
		$id = input('post.id');
		$user_class = Db::name('user_class');
		$user = Db::name('user');
		$user_class->startTrans();

		//判断该班级是否已经被教师绑定
		$userclass = $user_class->where('id',$id)->find();

		//查询所有教师绑定的班级
		$teacher_class = Db::name('teacher_class')->select();
		$classname = array_column($teacher_class, 'class_name');
		$arr = [];
		$array = [];
		foreach($classname as $k => $v){
			$arr[$k] = explode(',',$v);
		}
		foreach($arr as $k=>$v){
			foreach($v as $key => $value){
				$array[] = $value;
			}
		}
		if(in_array($userclass['class_name'], $array)){
			jsonReturn('002',null,"您所删除的班级已经被教师绑定！");exit;
		}else{
			//删除班级
			$ret = $user_class->where('id',$id)->delete();
			if(empty($ret)){
				$user_class->commit();
				jsonReturn('002',null,"删除失败！");
			}else{
				//将该班级下的绑定的学生解绑
				$rute = [];
				$rut = $user->where('class_id',$id)->select();
				foreach($rut as $k=>$v){
					$rute[$k] = $user->where('id', $v['id'])->update(['class_id' => null]);

				}
				if(empty($rute)){
					$user_class->rollback();
					jsonReturn('002',null,"删除失败！");
				}else{
					$user_class->commit();
					jsonReturn('001',null,"删除成功！");
				}
			}
			
		}
	}


    /**
     * 班级列表数组
     */
    public function ClassInfo()
    {
        $ret = Db::name('user_class')->column('id,class_name');
        if($ret){
            jsonReturn('001',$ret);
        }else{
            jsonReturn('002');
        }
    }

}
