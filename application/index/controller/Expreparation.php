<?php
/**
 * Created by sublime 3.0
 * User: 愿得一人行
 * Date: 2017/9/4
 * Time: 10:56
 */

namespace app\index\controller;

use app\common\controller\BaseController;
use think\Loader;
use think\Db;
use think\Session;

class Expreparation extends BaseController
{
 
	/**
	 * 实验准备-试验库管理
	 */
	//试验库列表显示
	public function experimentLibraryList()
	{
        $user_id = Session::get('user_info.user_id');

	    $map =[
	        'a.user_id' => $user_id
        ];

	    $fields = 'a.id,a.experiment_name,a.experiment_type,a.check_quote,b.curriculum_name,b.curriculum_num,c.name,c.user_uid,d.name as resource_name,e.test_name';

	    $res = Db::name('experiment_library')
            ->alias('a')
            ->join('nk_curriculum b','b.id=a.curriculum_id')
            ->join('nk_user c','c.id=a.user_id')
            ->join('nk_resources d','d.id=a.resources_id')
            ->join('nk_test_paper e','e.id=a.test_id')
            ->field($fields)
            ->where($map)
            ->select();

	    foreach ($res as $k => $v){
	        $res[$k]['curr_name'] = $v['curriculum_name'] . '(' . $v['curriculum_num'] . ')';
	        $res[$k]['user_name'] = $v['name'] . '(' . $v['user_uid'] . ')';
	        unset($res[$k]['curriculum_name']);
	        unset($res[$k]['curriculum_num']);
	        unset($res[$k]['name']);
	        unset($res[$k]['user_uid']);
        }

		if($res){
			jsonReturn('001',$res);
		}else{
			jsonReturn('002',null,"查询失败！");
		}
	}

	//查询已审核通过的资源
	public function AuditedResources()
	{
		$ret = Db::name('resources')->where('status',1)->select();
		if($ret){
			jsonReturn('001',$ret);
		}else{
			jsonReturn('002',null,"没有匹配的资源！");
		}
	}




	//查看习题
	public function exercisesQuery()
	{
		$id = input('post.id');

		//查询试卷名称
		$experiment = Db::name('experiment_library')->where('id',$id)->find();

		$ret = Db::name('test_paper')->where('id',$experiment['test_id'])->find();
		//查询替换课程名称
		$curr = Db::name('curriculum')->where('id',$ret['curriculum_id'])->find();
		$ret['curriculum_name'] = $curr['curriculum_name'] . '(' . $curr['curriculum_num'] . ')';
		//查询替换所属策略
		$strategy = Db::name('base_teststrategy')->where('id',$ret['strategy_id'])->find();
		$ret['strategy_name'] = $strategy['strategy_name'];
		if($ret){
			jsonReturn('001',$ret);
		}else{
			jsonReturn('002',null,"没有查询到相关结果！");
		}
	}


	//新增实验-选择教师
	public function experimentLibraryTeacher()
	{
	    if(Session::has('user_info.user_id')){
	        $map = [
	            'id'    =>  Session::get('user_info.user_id')
            ];

            $user = Db::name('user')->field('id,name,user_uid')->where($map)->find();

            if($user){
                $user['user_name'] = $user['name'] . '(' . $user['user_uid'] . ')';
                unset($user['name']);
                unset($user['user_uid']);
                jsonReturn('001',$user);
            }else{
                jsonReturn('002',null,"没有符合要求的教师！");
            }
        }else{
            jsonReturn('002',null,"没有符合要求的教师！");
        }
	}


	//新增实验-选择试卷
	public function experimentLibraryTest()
	{
		//$ret = Db::name('test_paper')->where('release','是')->order('id desc')->select();
		$fields = 'a.id,a.test_name';
		$map = [
            'setters_id' => Session::get('user_info.user_id'), 
            'release' 	 => '是'
        ];
		$ret = Db::name('test_paper')
                ->alias('a')
                ->join('nk_base_teststrategy b','a.strategy_id=b.id')
                ->field($fields)
                ->where($map)
                ->select();

		if($ret){
			jsonReturn('001',$ret);
		}else{
			jsonReturn('002',null,"查询失败！");
		}

	}



	//新增实验
	public function experimentLibraryAdd()
	{
		$data = input('post.data/a');

		//查询实验名称和所属课程是否已经存在
		$experiment_library = Db::name('experiment_library');
		$ret = $experiment_library->where('experiment_name', $data['experiment_name'])->select();
		if($ret){
			jsonReturn('002',null,"该实验名称已经存在！");exit;
		}else{
			$add = [
				'experiment_name' 		 => $data['experiment_name'],			//实验名称
				'curriculum_id' 		 => $data['curriculum_name'],			//所属课程id
				'experiment_type' 		 => $data['experiment_type'],			//实验类型
				'user_id' 			     => $data['update_user'],			    //负责人id
				'check_quote' 			 => $data['check_quote'],				//是否被引用
				'experiment_results'     => $data['experiment_results'],		//实验成绩比重
				'presentation_results'   => $data['presentation_results'],		//报告成绩比重
				'experiment_requirement' => $data['experiment_requirement'],	//实验要求
				'experiment_report' 	 => $data['experiment_report'],			//实验报告
				'resources_id' 		     => $data['resources_name'],			//资源名称
				'test_id' 		         => $data['test_name'],				    //试卷名称
				'create_time' 		 	 => date("Y-m-d H:i:s"),				//创建时间
				'update_time' 			 => date("Y-m-d H:i:s"),				//更新时间
			];
			$result = $experiment_library->insert($add);
			if($result){
				jsonReturn('001',null,"新增成功！");
			}else{
				jsonReturn('002',null,"新增失败！");
			}

		}

	}


	//修改实验-显示
	public function experimentLibraryEdit()
	{
		$id = input('post.id');

		$map = [
		    'id' => $id
        ];

		$res = Db::name('experiment_library')->where($map)->find();

		if($res){
			jsonReturn('001',$res);
		}else{
			jsonReturn('002',null,"查询失败！");
		}

	}


	//修改实验-更新
	public function experimentLibrarySave()
	{
		$data = input('post.data/a');

		//查询实验名称和所属课程是否已经存在
		$experiment_library = Db::name('experiment_library');
		$ret = $experiment_library->where('id','<>', $data['id'])->where('experiment_name', $data['experiment_name'])->select();
		if($ret){
			jsonReturn('002',null,"该实验名称已经存在！");exit;
		}else{
			$save = [
				'experiment_name' 		 => $data['experiment_name'],			//实验名称
				'curriculum_id' 		 => $data['curriculum_id'],				//所属课程id
				'experiment_type' 		 => $data['experiment_type'],			//实验类型
				'user_id' 			 	 => $data['user_id'],					//负责人id
				'check_quote' 			 => $data['check_quote'],				//是否被引用
				'experiment_results'     => $data['experiment_results'],		//实验成绩比重
				'presentation_results'   => $data['presentation_results'],		//报告成绩比重
				'experiment_requirement' => $data['experiment_requirement'],	//实验要求
				'experiment_report' 	 => $data['experiment_report'],			//实验报告
				'resources_id' 		     => $data['resources_id'],				//资源名称
				'test_id' 		         => $data['test_id'],				    //试卷名称
				'update_time' 			 => date("Y-m-d H:i:s"),				//更新时间
			];

			$result = $experiment_library->where('id',$data['id'])->update($save);
			if($result){
				jsonReturn('001',null,"修改成功！");
			}else{
				jsonReturn('002',null,"修改失败！");
			}

		}
	}


	//删除实验
	public function experimentLibraryDelete()
	{
		$id = input('post.id/a');
		$ret = Db::name('experiment_library')->where('id','in', $id)->delete();
		if($ret){
			jsonReturn('001',null,"删除成功！");
		}else{
			jsonReturn('002',null,"删除失败！");
		}
	}





	/**
	 * 实验准备-布置新实验
	 */
	//查询所选课程下的实验
	public function choosingCourses()
	{
		$id = input('post.id');   //课程ID
		$user_id = Session::get('user_info.user_id');

		$curriculum = Db::name('curriculum')->where('id',$id)->find();
		$ret = Db::name('experiment_library')->where('curriculum_id', $curriculum['id'])->where('user_id', $user_id)->where('check_quote', '是')->order('id desc')->select();
		
		//查询替换教师
		$user = Db::name('user');
		foreach($ret as $k=>$v){
			$where[$k] = $user->where('id',$v['user_id'])->find();
			$ret[$k]['update_user'] = $where[$k]['name'];
            $ret[$k]['curriculum_name'] = $curriculum['curriculum_name'] . '(' . $curriculum['curriculum_num'] . ')';
		}

		$rut['data'] = $ret;

		if($rut){
			jsonReturn('001',$rut);
		}else{
			jsonReturn('002',null,"查询失败！");
		}
	}


	//显示设置安排属性
	public function setAttributesAdd()
	{
		$id = input('post.id');
		$ret = Db::name('experiment_library')->where('id', $id)->find();
		//查询替换教师
		$user = Db::name('user')->where('id',$ret['user_id'])->find();
		$ret['update_user'] = $user['name'];
		
		//查询上课地点
		$user_id = Session::get('user_info.user_id');
		$class_place = Db::name('start_curriculum')->where('curriculum_id',$ret['curriculum_id'])->where('user_id',$user_id)->find();
		if($class_place){
			$ret['class_place'] = $class_place['class_place'];
		}else{
			$ret['class_place'] = "该实验暂无上课地点！";
		}
		//查询所开课程名称
		$curr = Db::name('curriculum')->where('id',$ret['curriculum_id'])->find();
		$ret['curriculum_name'] = $curr['curriculum_name'] . '(' .  $curr['curriculum_num'] . ')';

		//实验前练习题查询
		if(empty($ret['test_id'])){
			$ret['test_name'] = "该实验暂无相关习题！";
		}else{
			$test_paper = Db::name('test_paper')->where('id',$ret['test_id'])->find();
			$ret['test_name'] = $test_paper['test_name'];
		}

		//查询选择该门课程符合要求的学生人数
		$class_id = explode(',',$class_place['class_id']);
		$num = [];
		$user_class = Db::name('user_class');
		for($i=0;$i<count($class_id);$i++){
			$user_uid[$i] = $user_class->where('id',$class_id[$i])->find();
			$num[$i] = explode(',',$user_uid[$i]['user_uid']);
		}
		foreach($num as $k=>$v){
			foreach($v as $key=>$val){
				$numbel[] = $val;
			}
		}
		$ret['num'] = count($numbel);
		//获取学生的一卡通号
		Session::set('USERUUID', $numbel);
		Session::set('USERRET', $ret);
		if($ret){
			jsonReturn('001',$ret);
		}else{
			jsonReturn('002',null,"查询失败！");
		}

	}


	
	//安排成功插入数据库
	public function  setAttributesInsert()
	{
		$data = input('post.data/a');     		//接收数据
		$datas = Session::get('USERRET');       //获取基础数据

        if($data[1] < time() && $data[2] < time()){
            jsonReturn('002',null,"安排的时间不合理！");exit;
        }

        if($data[1] < time() && $data[2] > time()) {
            jsonReturn('002',null,"安排的时间不合理！");exit;
        }


		$user_id 	        = $datas['user_id'];    		//上传者id
		$experiment_name 	= $datas['experiment_name'];    //实验名称
		$curriculum_id 	    = $datas['curriculum_id'];		//所属课程代码
		$experiment_type 	= $datas['experiment_type'];	//实验类型
		$experiment_address = $datas['class_place']; 		//实验地点
		$test_name 	        = $datas['test_name']; 		    //试卷名称
		$experiment_check 	= $data[0];						//必做：1，选做：0
		$start_time 		= date("Y-m-d H:i:s",$data[1]);						//开始时间
		$end_time 			= date("Y-m-d H:i:s",$data[2]);						//结束时间

		//获取学生一卡通号
		$user_uuid = Session::get('USERUUID');
		if(empty($user_uuid)){
			jsonReturn('002',null,"目前没有学生选择这门课！");exit;
		}else{
			//验证该条记录是否已经存在
			$where = [
				'experiment_name' => $experiment_name,		//实验名称
				'curriculum_id'   => $curriculum_id,		//所属课程代码
				'start_time' 	  => $start_time,			//开始时间
				'end_time' 	      => $end_time,				//结束时间
			];
			$experiment = Db::name('experiment');
			$achievement = Db::name('achievement');
			$experiment->startTrans();  //开启事务

			$ret = $experiment->where($where)->select();
			if($ret){
				jsonReturn('002',null,"在该时间段已经发布过该实验！");exit;
			}else{
				$add = [
					'user_id'    		 => $user_id,				//上传者id
					'experiment_name'    => $experiment_name,		//实验名称
				    'curriculum_id'      => $curriculum_id,			//所属课程代码
					'experiment_type' 	 => $experiment_type,		//实验类型
					'experiment_address' => $experiment_address,	//实验地点
					'experiment_check'   => $experiment_check,		//必做：1，选做：0
					'test_name'   		 => $test_name,				//试卷名称
					'start_time' 	     => $start_time,			//开始时间
					'end_time' 	         => $end_time,				//结束时间
					'create_time'        => date("Y-m-d H:i:s"),	//创建时间
					'update_time'        => date("Y-m-d H:i:s"),	//更新时间

				];

				$result = Db::name('experiment')->insert($add);
				$experimentId = Db::name('experiment')->getLastInsID();
				if($result){
					//插入成绩表
					$user = Db::name('user');
					$user_class = Db::name('user_class');
					for($i=0;$i<count($user_uuid);$i++){
						$array[$i] = $user->where('user_uid',$user_uuid[$i])->find();
						$class_name[$i] = $user_class->where('id',$array[$i]['class_id'])->find();

						$arr[$i]['experiment_id'] = $experimentId;     		//布置试验id
						$arr[$i]['user_name']     = $array[$i]['name'];     //学生姓名
						$arr[$i]['user_uid']      = $user_uuid[$i];		    //学生一卡通号
						$arr[$i]['class']     	  = $class_name[$i]['class_name'];	//班级
						$arr[$i]['phone']     	  = $array[$i]['phone'];	//手机号
						$arr[$i]['submit_state']  = "未提交";				//提交状态
						$arr[$i]['create_time']   = date("Y-m-d H:i:s");	//创建时间
						$arr[$i]['update_time']   = date("Y-m-d H:i:s");	//更新时间
					}
					$rut = $achievement->insertAll($arr);
					if(empty($rut)){
						$experiment->rollback();  //事务回滚
					}else{
						Session::set('experimentId',$experimentId);
						$experiment->commit();    //提交事务
						jsonReturn('001',null,"安排成功！");
					}
				}else{
					jsonReturn('002',null,"发布失败！");
				}
			}
		}	

	}


	//显示刚安排成功的试验详情
	public function setAttributesIndex()
	{
		$id = Session::get('experimentId');
		$datas = Session::get('USERRET');      	 //获取基础数据
		$ret = Db::name('experiment')->where('id',$id)->find();
		$ret['test_name'] = $datas['test_name'];  //实验前练习
		//查询选课人数
		$user_uuid = Db::name('achievement')->where('experiment_id',$id)->select();
		$ret['num'] = count($user_uuid);  		 //选课人数
		//查询课程名称
		$curr = Db::name('curriculum')->where('id',$ret['curriculum_id'])->find();
		$ret['curriculum_name'] = $curr['curriculum_name'] . '(' .  $curr['curriculum_num'] . ')';
		if($ret){
			jsonReturn('001',$ret);
		}else{
			jsonReturn('002',null,"查询失败！");
		}
	}

	//显示刚安排成功的试验详情-点击查看学生信息
	public function setAttributesQuery()
	{
		$id = Session::get('experimentId');
		$ret = Db::name('achievement')->where('experiment_id',$id)->select();
		if($ret){
			jsonReturn('001',$ret);
		}else{
			jsonReturn('002',null,"查询失败！");
		} 
	}

	//显示刚安排成功的试验详情-点击删除学生信息
	public function setAttributesDel()
	{
		$id = input('post.id');
		$ret = Db::name('achievement')->where('id',$id)->delete();
		if($ret){
			jsonReturn('001',null,"删除成功！");
		}else{
			jsonReturn('002',null,"删除失败！");
		} 
	}





	/**
	 * 已安排实验管理
	 */
	//已安排实验列表显示
	public function experimentList()
	{
		$user_id = Session::get('user_info.user_id');
		$ret = Db::name('experiment')->where('user_id',$user_id)->order('id desc')->select();
		$curriculum = Db::name('curriculum'); 
		$curr = [];
		foreach($ret as $k=>$v){
			//替换课程名
			$curr[$k] = $curriculum->where('id',$v['curriculum_id'])->find();
			$ret[$k]['curriculum_name'] = $curr[$k]['curriculum_name'] . '(' .$curr[$k]['curriculum_num'] . ')';
			//替换时间
			$ret[$k]['time'] = $v['start_time'] . '至' . $v['end_time'];
		}
		if($ret){
			jsonReturn('001',$ret);
		}else{
			jsonReturn('002',null,"查询失败！");
		} 
	}


	//修改安排实验-显示
	public function experimentEdit()
	{
		$id = input('post.id');
		$ret = Db::name('experiment')->where('id', $id)->find();
		$curricum = Db::name('curriculum')->where('id', $ret['curriculum_id'])->find();
        $ret['curriculum_name'] = $curricum['curriculum_name'] . '(' . $curricum['curriculum_num'] . ')';
		//查询选择该门课程符合要求的学生人数
		$result = Db::name('achievement')->where('experiment_id', $id)->select();
		$ret['num'] = count($result);

		if($ret){
			jsonReturn('001',$ret);
		}else{
			jsonReturn('002',null,"查询失败！");
		} 
	}


	//修改安排实验-更新
	public function experimentSave()
	{
		$data = input('post.data/a');

        if($data[2] < time() && $data[3] < time()){
            jsonReturn('002',null,"安排的时间不合理！");exit;
        }

        if($data[2] < time() && $data[3] > time()) {
            jsonReturn('002',null,"安排的时间不合理！");exit;
        }

		$experiment = Db::name('experiment');

		//查询实验名称和所属课程
		$rets = $experiment->where('id',$data[0])->find();
		//验证该条记录是否已经存在
		$where = [
			'experiment_name' => $rets['experiment_name'],		//实验名称
			'curriculum_id'   => $rets['curriculum_id'],		//所属课程id
			'start_time' 	  => $data[2],						//开始时间
			'end_time' 	      => $data[3],						//结束时间
		];
		$ret = $experiment->where('id','<>', $data[0])->where($where)->select();
		if($ret){
			jsonReturn('002',null,"在该时间段已经发布过该实验！");exit;
		}else{
			$save = [
				'experiment_check'=> $data[1],				//必做：1，选做：0
				'start_time' 	  => $data[2],				//开始时间
				'end_time' 	      => $data[3],				//结束时间
				'update_time'     => date("Y-m-d H:i:s"),   //更新时间
			];
			$rut = $experiment->where('id',$data[0])->update($save);
			if($rut){
				jsonReturn('001',null,"修改成功！");
			}else{
				jsonReturn('002',null,"修改失败！");
			}		
			
		}

	}


	//删除已安排实验
	public function experimentDelete()
	{
        
		$id = input('post.id');

		$experiment = Db::name('experiment');
		$achievement = Db::name('achievement');
		$experiment->startTrans();  //开启事务

		//删除布置试验学生表记录
		$query = $achievement->where('experiment_id', $id)->select();
		if($query){
			$ret = $achievement->where('experiment_id', $id)->delete();
		}else{
			$ret = 1;
		}
		if($ret){
			//删除布置试验表记录
			$rut = $experiment->where('id', $id)->delete();
			if(empty($rut)){
				$experiment->rollback();  //事务回滚
			}else{
				$experiment->commit();    //提交事务
				jsonReturn('001',null,"删除成功！");
			}
		}else{
			jsonReturn('002',null,"删除失败！");
		}

	}


}
