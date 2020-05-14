<?php
/**
 * Created by sublime 3.0
 * User: 愿得一人行
 * Date: 2017/9/14
 * Time: 10:56
 */

namespace app\index\controller;

use app\common\controller\BaseController;
use think\Db;
use think\Request;
use think\Config;
use think\Exception;
use think\Loader;
use think\Session;

class Student extends BaseController
{

	/**
	 * 试验考核
	 */
	//实验列表查询
	public function AssessmentList()
	{
        
		$user_uid = Session::get('user_info.user_uid');
		$lab_id = Session::get('user_infocas.labid');
		//查询该学生下的所有课程
		$where = [
			'user_uid' => $user_uid,
			'lab_id'   => $lab_id
		];
		$user_student = Db::name('user_student')->where($where)->select();
		//查询每门课程下的资源
		$ret = [];
		$result = [];
		$arr = [];
		$start_curriculum = Db::name('start_curriculum');
		$teach_resource = Db::name('teach_resource');
		foreach($user_student as $k=>$v){
			//查询课程id
			$ret[$k] = $start_curriculum->where('curriculum_num',$v['curriculum_num'])->find();
			//查询教学资源表
			$result[$k] = $teach_resource->where('curriculum_id',$ret[$k]['id'])->find();
			//组合课程和资源
			$arr[$k]['id']              = $v['id'];
			$arr[$k]['curriculum_num']  = $v['curriculum_num'];
			$arr[$k]['curriculum_name'] = $v['curriculum_name'];
			$arr[$k]['resource_name']   = $result[$k]['name'];
			$arr[$k]['resource_type']   = $result[$k]['type'];

		}
		if($arr){
			jsonReturn('001',$arr);
		}else{
			jsonReturn('002',null,'目前没有课程!');
		}
	
	}


	//查看详情
	public function AssessmentSelect(){
		$id = input('id');
		$user_student = Db::name('user_student')->where('id',$id)->find();
		//查询教学资源表
		$fields = 'a.id as res_id,a.name as res_name,a.type as res_type,a.url as res_url,a.describe as res_describe';
		$resources = Db::name('teach_resource')
						->alias('a')
						->join('nk_start_curriculum b', 'a.curriculum_id=b.id')
						->where('b.curriculum_num',$user_student['curriculum_num'])
						->field($fields)
						->find();
						
		//合并数据
		$resources['id'] = $user_student['id'];
		$resources['user_uid'] = $user_student['user_uid'];
		$resources['curriculum_num'] = $user_student['curriculum_num'];
		$resources['curriculum_name'] = $user_student['curriculum_name'];
		if($resources){
			jsonReturn('001',$resources);
		}else{
			jsonReturn('002','加载失败！');
		}


	}


    /**
     * 上传实验报告
     */
	public function upExperimentReport(){
		$curr_num = input('curr_num');

        $file = request()->file('file');
        $fileInfo = request()->file('file')->getInfo();

        $ext = substr($fileInfo['name'],strpos($fileInfo['name'],'.'));

        if(in_array($ext,['.xlsx','.xls'])){
            $store_path = "uploads/phpexcel/";
            $checkExt = ['xlsx','xls'];

        }elseif (in_array($ext,['.doc','.docx','.pdf','.txt'])){
            $store_path = "uploads/document/";
            $checkExt = ['doc','docx','pdf','txt'];

        }elseif (in_array($ext,['.mp4'])){
            $store_path = "uploads/video/";
            $checkExt = ['mp4'];

        }elseif (in_array($ext,['.exe'])){
            $store_path = "uploads/software/";
            $checkExt = ['exe'];

        }elseif (in_array($ext,['.zip'])){
            $store_path = "uploads/static/";
            $checkExt = ['zip'];

        }else{
            $checkExt = ['jpg','png','jpeg'];

            if (in_array($ext,['.jpg','.png','.jpeg']) && $sign == 'resource'){
                $store_path = "uploads/images/";
            }else{
                $store_path = "uploads/portrait/";
            }
        }

        $config = array(
            'size'    =>    16384000000,
            'ext'     =>    $checkExt,
        );

        $info = $file->validate($config)->move($store_path);
        if($info){
            $url = $store_path . str_replace('\\','/',$info->getSaveName());
            $res = [
                'name'  =>  $info->getFilename(),
                'url'   =>  str_replace('public/','',$url)
            ];
            $name = Session::get('user_info.user_name');
            $user_uid = Session::get('user_info.user_uid');
            //查询学生成绩表student_id
            $map = [
            	'user_uid'       => $user_uid,
            	'curriculum_num' => $curr_num
            ];
            $student_id = Db::name('user_student')->where($map)->find();
            $student_score = Db::name('student_score')->where('student_id',$student_id['id'])->find();
            $fileName = $name . '(' . $user_uid . ')';
            if($student_score){
            	//写入成绩表
	            $datas = [
	            	'report_name' => $fileName,
	            	'report_url'  => $res['url'],
	            ]; 
	            $result = Db::name('student_score')->where('id',$student_score['id'])->update($datas);
            }else{
            	//写入成绩表
	            $datas = [
	            	'student_id'  => $student_id['id'],
	            	'report_name' => $fileName,
	            	'report_url'  => $res['url'],
	            ]; 
	            $result = Db::name('student_score')->insert($datas);
            }

            if($result){
            	jsonReturn('001',null,'上传成功！');
            }else{
            	jsonReturn('002',null,'上传失败！');
            }
            
        }else{
            jsonReturn('002',null,$file->getError());
        } 
        
    }


	/**
	 * 上传实验报告
	 */
	// public function uploadReport()
	// {
	// 	$data = input('post.data/a');
	// 	if(!$data['name'] && !$data['url']){
	// 	    jsonReturn('002',null,'请上传文件！');
 //        }
 //        $id = input('post.id');dump($id);
		// $user_uuid = Session::get('user_info.user_uid');

		// //查询批改表信息
		// $experiment = Db::name('experiment')->where('id',$id)->find();

		// //生成报告名
		// $Presentation = $experiment['experiment_name'] . strrchr($data['name'],'.');
		// //写入成绩表数据
		// $achievement = Db::name('achievement');
		// $where = [
		// 	'user_uid'       => $user_uuid,				//学生id
		// 	'experiment_id'  => $id,				    //布置试验表id
		// ];
		// $rut = $achievement->where($where)->find();
		// //更新写入报告名称和路径
		// $save = [
		// 	'presentation_name' => $Presentation,    				  //报告名称
		// 	'presentation_url'  => $data['url'],    				  //报告路径
		// 	'submit_state'      => '已提交',
		// 	'update_time'       => date("Y-m-d H:i:s"),
		// ];
		// $result = $achievement->where('id',$rut['id'])->update($save);

		// if($result){
		// 	jsonReturn('001',null,"上传成功！");
		// }else{
		// 	jsonReturn('002',null,"上传失败！");
		// }
		
	// }




	/**
	 * 下载资源
	 */
	public function DownloadResources()
	{
		$id = input('id');
		//查询资源名称
		$exper_name = Db::name('experiment')->where('id',$id)->find();
		$experiment = Db::name('experiment_library')->where('experiment_name',$exper_name['experiment_name'])->find();

		//查询资源表
		$resources = Db::name('resources')->where('id',$experiment['resources_id'])->find();
		
		//更新下载量
        $save = array(
            'download' => $resources['download'] + 1,
        );
        $upload = Db::name('resources')->where('id',$experiment['id'])->update($save);
        if($upload){
            //下载文件
            $file = $resources['file'];
            $name = $resources['name'];
            if(is_file($file)){
                $length = filesize($file);
                $type = mime_content_type($file);
                $showname =  ltrim(strrchr($file,'/'),'/');
                $filename = $name . strrchr($showname,'.'); //文件名替换
                header("Content-Description: File Transfer");
                header('Content-type: ' . $type);
                header('Content-Length:' . $length);
                if(preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])){ //for IE
                    header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
                }else{
                    header('Content-Disposition: attachment; filename="' . $filename . '"');
                }
                readfile($file);
            }else{
                jsonReturn('002',null,'您下载的资源不存在！');
            }
        }

	}






	/**
	 * PHP与C#端数据交互-虚拟资源成绩上传
	 */
	//接收考试数据
	public function score(){
		$ret = file_get_contents("php://input");  //获取c#端数据
		$desdecrypt = desdecrypt($ret);         
		$rut = json_decode(urldecode($desdecrypt),true);


		$user_uuid = $rut('user_uid');

		//查询学生基本信息
		$user = Db::name('user')->where('user_uid',$user_uuid)->find();

		//查询批改表信息
		$experiment = Db::name('experiment_library')->where('id',$data['id'])->find();

		//查询批改表id
		$where = [
			'experiment_name' => $experiment['experiment_name'],
			'curriculum_name' => $experiment['curriculum_name'],
		];
		$ret = Db::name('correcting')->where($where)->find();
		//写入成绩表数据
		$achievement = Db::name('achievement');
		$where = [
			'corr_id'    => $ret['id'],					//试验批改id
			'user_name'  => $user['name'],				//学生姓名
			'student_id' => $user['student_id'],		//学号
			'class'		 => $user['class'],				//班级
			'phone' 	 => $user['phone'],    			//手机号
		];
		$rut = $achievement->where($where)->find();
		if($rut){
			$save = [
				'experiment_score'  => $data['experiment_score'],    //实验成绩
				'update_time'       => date("Y-m-d H:i:s"),
			];
			$result = $achievement->where('id',$rut['id'])->update($save);
		}else{
			$add = [
				'corr_id'    		=> $ret['id'],					//试验批改id
				'user_name'   		=> $user['name'],				//学生姓名
				'student_id' 		=> $user['student_id'],		    //学号
				'class' 			=> $user['class'],				//班级
				'phone' 			=> $user['phone'],    			//手机号
				'experiment_score'  => $data['experiment_score'],   //实验成绩
				'create_time'       => date("Y-m-d H:i:s"),
				'update_time'       => date("Y-m-d H:i:s"),
			];
			$result = $achievement->insert($add);
		}
		if($result){
			jsonReturn('001',null,"提交成功！");
		}else{
			jsonReturn('002',null,"提交失败！");
		}







	}





















}
