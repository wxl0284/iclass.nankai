<?php
/**
 * Created by sublime 3.0
 * User: 愿得一人行
 * Date: 2019/7/10
 * Time: 10:56
 */

namespace app\index\controller;

use app\common\controller\BaseController;
use think\Db;
use think\Exception;
use think\Session;
use think\Loader;

class Lab extends BaseController
{

	/**
	 * 实验室管理
	 */

	//实验室列表
	public function labList()
	{
		$labid = Session::get('user_infocas.labid');
		if($labid == 'kong'){
			$ret = Db::name('lab')->select();	
		}else{
			$map = [
				'id' => $labid
			];
			$ret = Db::name('lab')->where($map)->select();
		}

		if($ret){
			jsonReturn('001',$ret);
		}else{
			jsonReturn('002',null,'查询失败！');
		}

	}



	// 所有实验室列表
	public function labQuery()
	{
		$ret = Db::name('lab')->select();
		if($ret){
			jsonReturn('001',$ret);
		}else{
			jsonReturn('002',null,'查询失败！');
		}
	}

	//添加实验室
	public function labAdd()
	{
		$name = trim(input('post.name'));
		$address = trim(input('post.address'));
		$number = trim(input('post.number'));
		$teacher = trim(input('post.teacher'));
		$imgurl = trim(input('post.imgurl'));
		$content = trim(input('post.content'));

		$lab = Db::name('lab');
		//查询当前实验室是否存在
		$where = [
			'name' 	=> $name
		];
		$sel = $lab->where($where)->find();
		if($sel){
			jsonReturn('002',null,'当前实验室已经存在');exit;
		}else{
			$fileurl = saveBase64Img($imgurl);

			$data = [
				'name' 		=> $name,
				'address' 	=> $address,
				'number' 	=> $number,
				'teacher' 	=> $teacher,
				'imgurl' 	=> $fileurl,
				'content'	=> $content,
				'time'		=> date("Y-m-d H:i:s")
			];
			
			$ret = $lab->insert($data);
			if($ret){
				jsonReturn('001',null,'提交成功！');
			}else{
				jsonReturn('002',null,'提交失败！');
			}
		}

	}



	//实验室详情查询
	public function labEdit()
	{
		$id = input('post.id');
		
		$where = [
			'id' => $id
		];

		$ret = Db::name('lab')->where($where)->find();
		$ret['imgurl'] = 'http://' . $_SERVER['SERVER_NAME'] . '/' . $ret['imgurl'];
		if($ret){
			jsonReturn('001',$ret);
		}else{
			jsonReturn('002',null,'查询失败！');
		}
	}


	//实验室信息修改
	public function labUpdate()
	{
		$id = input('post.id');
		$name = trim(input('post.name'));
		$address = trim(input('post.address'));
		$number = trim(input('post.number'));
		$teacher = trim(input('post.teacher'));
		$imgurl = trim(input('post.imgurl'));
		$content = trim(input('post.content'));

		$lab = Db::name('lab');
		//查询当前实验室是否存在
		$where = [
			'id' 	=> array('NEQ', $id),
			'name' 	=> $name
		];
		$sel = $lab->where($where)->find();
		if($sel){
			jsonReturn('002',null,'当前实验室已经存在');exit;
		}else{
			$fileurl = saveBase64Img($imgurl);
			if(empty($fileurl)){
				$data = [
					'name' 		=> $name,
					'address' 	=> $address,
					'number' 	=> $number,
					'teacher' 	=> $teacher,
					'content'	=> $content,
					'time'		=> date("Y-m-d H:i:s")
				];
			}else{
				$data = [
					'name' 		=> $name,
					'address' 	=> $address,
					'number' 	=> $number,
					'teacher' 	=> $teacher,
					'imgurl' 	=> $fileurl,
					'content'	=> $content,
					'time'		=> date("Y-m-d H:i:s")
				];
			}
			
			$ret = $lab->where('id',$id)->update($data);
			if($ret){
				jsonReturn('001',null,'提交成功！');
			}else{
				jsonReturn('002',null,'提交失败！');
			}
		}

	}






	// 删除实验室
	public function labDel()
	{
		$id = input('post.id');
		$where = [
			'id' => $id
		];
		$ret = Db::name('lab')->where($where)->delete();
		if($ret){
			jsonReturn('001','','删除成功！');
		}else{
			jsonReturn('002',null,'删除失败！');
		}
	}



}
