<?php  
/**
 * Created by Sublime Text
 * User: 愿得一人行
 * Date: 2018/12/12
 * Time: 16:51
 */
namespace app\ailab\controller;

use think\Controller;
use think\Db;
use think\Loader;
class Presentation extends Controller
{

	//生成实验报告
	public function generationReport()
	{
		$session_id = trim(input('post.session_id'));
		$user_name = trim(input('post.user_name'));
		$analysis = trim(input('post.analysis'));

		//修改当前用户在用户表中的时间
		$map = array('session_id' => $session_id);
		$update = array('time' => time(), 'random' => mt_rand(10000000,99999999));
		$check = Db::name('ailab_user')->where($map)->update($update);
		if(empty($check)){
			jsonReturn('004');exit;
		}

		$score = Db::name('ailab_score');
		$where = [
			'session_id' 		=> $session_id,
			'user_name' 		=> $user_name,
			'experiment_name' 	=> '无人机智能任务规划虚拟仿真实验',
		];
		$sel = $score->where($where)->find();
		if($sel){
			$update = [
				'analysis' => $analysis,
				// 'count'    => str_replace(array("&nbsp;","&amp;","&lt;","&gt;","&quot;","&qpos;","&copy;","&reg;","&trade;","&ensp; ","&emsp;","&thinsp;"), "", $analysis),
				'count'    => html_entity_decode($analysis),
			];
			$ret = $score->where($where)->update($update);
			if($ret){
				//更新使用次数
				//$where = [
				//	'softwarename' => '垃圾焚烧发电资源化利用技术虚拟仿真实验',
				//];
				//$select = Db::name('huanke_software')->where($where)->find();
				//if($select){
				//	$updates = Db::name('huanke_software')->where($where)->setInc('num',1);
				//}else{
				//	$adds = [
				//		'softwarename' => '垃圾焚烧发电资源化利用技术虚拟仿真实验',
				//		'num' 		   => '1',
				//	];
				//	$updates = Db::name('huanke_software')->insert($adds);
				//}
				jsonReturn('001',$ret);
			}else{
				jsonReturn('002',null,'提交失败！');
			}
		}else{
			jsonReturn('002',null,'必须先做实验才能写实验报告！');
		}

		


	}



	//查询成绩
	public function ailabScoreList()
	{
		$session_id = trim(input('post.session_id'));
		$user_name = trim(input('post.user_name'));
		//修改当前用户在用户表中的时间
		$map = array('session_id' => $session_id);
		$update = array('time' => time(), 'random' => mt_rand(10000000,99999999));
		$check = Db::name('ailab_user')->where($map)->update($update);
		if(empty($check)){
			jsonReturn('004');exit;
		}
		$where = array('session_id' => $session_id);
		$sel = Db::name('ailab_score')->where($where)->find();
		if(empty($sel)){
			$sel = array('user_name' => $user_name, 'experiment_name' => '无人机智能任务规划虚拟仿真实验');
		}
		if($sel){
			jsonReturn('001',$sel);
		}else{	
			jsonReturn('002');
		}
	}













}