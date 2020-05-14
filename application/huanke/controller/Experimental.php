<?php  
/**
 * Created by Sublime Text
 * User: 愿得一人行
 * Date: 2018/9/8
 * Time: 10:22
 */
namespace app\huanke\controller;

use think\Controller;
use think\Db;
use think\Config;
use think\Session;
class Experimental  extends Controller
{

	//接收实验数据并写入数据库
	public function experimentalData()
	{
		//更新使用次数
		$where = [
			'softwarename' => '垃圾焚烧发电资源化利用技术虚拟仿真实验',
		];
		$select = Db::name('huanke_software')->where($where)->find();
		if($select){
			$updates = Db::name('huanke_software')->where($where)->setInc('num',1);
		}else{
			$adds = [
				'softwarename' => '垃圾焚烧发电资源化利用技术虚拟仿真实验',
				'num' 		   => '1',
			];
			$updates = Db::name('huanke_software')->insert($adds);
		}
		//更新使用次数结束
			
		
		$data = file_get_contents("php://input");  //获取c#端数据
		$datas = substr($data,5); 
		$ret = json_decode(urldecode($datas),true);

		$userinfo = session('userinfo');
		//修改当前用户在用户表中的时间
		$map = array('session_id' => $userinfo['session_id']);
		$update = array('time' => time(), 'random' => mt_rand(10000000,99999999));
		$check = Db::name('huanke_user')->where($map)->update($update);
		//if(empty($check)){
		//	header('Location: http://vr.nankai.edu.cn/env/login.html');exit;
		//}

		// $userinfo = array('session_id' => '123456', 'user_name' => 'user1');
		$score = Db::name('huanke_score');
		$processdata = Db::name('huanke_processdata');

		$score->startTrans();     //开启事务

		//向成绩表中写入数据
		$add = [
			'session_id' 		=> $userinfo['session_id'],
			'user_name'  		=> $userinfo['user_name'],
			'experiment_name' 	=> '垃圾焚烧发电资源化利用技术虚拟仿真实验',
			'score' 			=> $ret['score'],
		];
		$result = $score->insert($add);
		$scoreId = $score->getLastInsID();
		if(empty($result)){
			$score->rollback();  //事务回滚
			jsonReturn('002');
		}else{
			$adds = [];
			for($i=0;$i<count($ret['shiyan']);$i++){
				$adds[$i] = [
					'sid' 		=> $scoreId,                       	//成绩表id
					'laji1' 	=> $ret['shiyan'][$i]['laji1'],    	//草木投加量
					'laji2' 	=> $ret['shiyan'][$i]['laji2'],	   	//厨余投加量
					'laji3' 	=> $ret['shiyan'][$i]['laji3'],		//果皮投加量
					'laji4' 	=> $ret['shiyan'][$i]['laji4'],		//毛骨投加量
					'laji5' 	=> $ret['shiyan'][$i]['laji5'],		//皮革投加量
					'laji6' 	=> $ret['shiyan'][$i]['laji6'],		//塑料投加量
					'laji7' 	=> $ret['shiyan'][$i]['laji7'],		//纤维投加量
					'laji8' 	=> $ret['shiyan'][$i]['laji8'],		//纸张投加量
					'laji9' 	=> $ret['shiyan'][$i]['laji9'],		//玻璃投加量
					'laji10' 	=> $ret['shiyan'][$i]['laji10'],	//金属投加量
					'laji11' 	=> $ret['shiyan'][$i]['laji11'],	//灰渣投加量
					'hsl' 		=> $ret['shiyan'][$i]['hsl'],		//垃圾含水率
					'gskqxs' 	=> $ret['shiyan'][$i]['gskqxs'],	//过剩空气系数
					'jfms' 		=> $ret['shiyan'][$i]['jfms'],		//一次风进风模式
					'jffm' 		=> $ret['shiyan'][$i]['jffm'],		//一次风进风风门
					'fdl' 		=> $ret['shiyan'][$i]['fdl'],		//发电量
					'ery' 		=> $ret['shiyan'][$i]['ery'],		//二噁英
					'hcl' 		=> $ret['shiyan'][$i]['hcl'],		//HCl
					'so' 		=> $ret['shiyan'][$i]['so'],		//SO2
					'nox' 		=> $ret['shiyan'][$i]['nox'],		//NOx
					'co' 		=> $ret['shiyan'][$i]['co'],		//CO
					'klr' 		=> $ret['shiyan'][$i]['klr'],		//颗粒物
					'syfx' 		=> $ret['syfx'],					//实验分析
					'aqdf' 		=> $ret['aqdf'],					//工作安全测试得分
					'fldf' 		=> $ret['fldf'],					//垃圾分类测试得分
					'caozuo1' 	=> $ret['caozuo1'],					//打开引风机打开一次风
					'caozuo2' 	=> $ret['caozuo2'],					//一次风模式与参数设置相符
					'caozuo3' 	=> $ret['caozuo3'],					//开启垃圾进料形同
					'caozuo4' 	=> $ret['caozuo4'],					//开启燃烧器，炉膛点火
					'caozuo5' 	=> $ret['caozuo5'],					//开启炉排
					'caozuo6' 	=> $ret['caozuo6'],					//开启喷碱液装置
					'caozuo7' 	=> $ret['caozuo7'],					//开启活性炭喷淋装置
					'caozuo8' 	=> $ret['caozuo8'],					//开启二次风
				];
			}
			$rets = $processdata->insertAll($adds);
			if(empty($rets)){
				$score->rollback();  //事务回滚
				jsonReturn('002');
			}else{
				$score->commit();  //事务提交
				jsonReturn('001');
			}
		
		}			

	}







}