<?php  
/**
 * Created by Sublime Text
 * User: 愿得一人行
 * Date: 2018/9/7
 * Time: 16:22
 */
namespace app\huanke\controller;

use think\Controller;
use think\Db;
class User extends Controller
{

	//查询使用次数和当前在线人数
	public function huankeUserNumber()
	{
		$user = Db::name('huanke_user');
		//修改当前用户在用户表中的时间
		$session_id = trim(input('post.session_id'));
		$map = array('session_id' => $session_id);
		$update = array('time' => time(), 'random' => mt_rand(10000000,99999999));
		$check = $user->where($map)->update($update);
		if(empty($check)){
			jsonReturn('004');exit;
		}else{
			//查询使用次数
			$software = Db::name('huanke_software')->where('id=1')->find();
			//查询在线人数
			$online = $user->where('status=1')->count();
			$lineup = $user->where('status=0')->count();

			$ret = array('num' => $software['num'], 'online' => $online, 'lineup' => $lineup);

			if($ret){
				jsonReturn('001',$ret);
			}else{
				jsonReturn('002');
			}
		}
		
	}












}