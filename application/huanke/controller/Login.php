<?php  
/**
 * Created by Sublime Text
 * User: 愿得一人行
 * Date: 2018/9/7
 * Time: 9:28
 */
namespace app\huanke\controller;

use think\Controller;
use think\Db;
use think\Session;
class Login extends Controller
{

	//登录验证
	public function checkLoginHuanke()
	{
		$user_uid = trim(input('post.username'));
        $password = trim(input('post.password'));

        if($password != '123'){
        	jsonReturn('002',null,'密码错误！');exit;
        }else{
        	$username = substr($user_uid,0,4);
        	if($username != 'user'){
        		jsonReturn('002',null,'用户名格式不正确！');exit;
        	}else{
        		$num = substr($user_uid,4);
        		$array = array(0,1,2,3,4,5,6,7,8,9);
        		if(!in_array($num,$array)){
        			jsonReturn('002',null,'用户名格式不正确！');exit;
        		}else{
        			$user = Db::name('huanke_user');
        			$user->startTrans();//开启事务
        			$sel = $user->lock(true)->where('status=1')->count();//加锁查询 
        			$rand = md5($user_uid . mt_rand(10000000,99999999));  //session_id
        			$return = array('session_id' => $rand, 'user_name' => $user_uid);
        			session('userinfo',$return);
        			if($sel >= 100){
        				$add = [
        					'session_id' => $rand,
        					'username'   => $user_uid,
        					'time'       => time(),
        					'status'     => 0,
        				];
        				$ret = $user->lock(true)->insert($add);
        				if($ret){
        					$user->commit();  //提交事务
        					jsonReturn('003',$return);exit;
        				}else{
        					$user->commit();  //提交事务
        					jsonReturn('002',null,'登录失败！');exit;
        				}
        			}else{
						$add = [
        					'session_id' => $rand,
        					'username'   => $user_uid,
        					'time'       => time(),
        					'status'     => 1,
        				];
        				$ret = $user->lock(true)->insert($add);
        				if($ret){
        					$user->commit();  //提交事务
        					jsonReturn('001',$return);exit;
        				}else{
        					$user->commit();  //提交事务
        					jsonReturn('002',null,'登录失败！');exit;
        				}
        			}
        		}
        	}
        }
	}



	//查询当前用户前面还有多少人在排队
	public function lineUp()
	{
		$rand = trim(input('post.session_id'));
		$where = [
				'session_id' => $rand,
				'status'     => 0, 
			];
		$user = Db::name('huanke_user');
		$sel = $user->where($where)->find();

		$map = array(
				'id' 		=> array('<',$sel['id']),
				'status'    => 0,
			);
		$count = $user->where($map)->count();

		if($count){
			jsonReturn('001',$count);
		}else{
			//查询当前在线人数
			$num = $user->where('status=1')->count();
			if($num < 100){
				$update = [
					'status' => 1,
					'time'   => time(),
				];
				$ret = $user->where($where)->update($update);
				jsonReturn('002',$rand);
			}else{
				jsonReturn('001','0');
			}
			
		}		

	}


	//取消排队
	public function cancelLineUp()
	{
		$rand = trim(input('post.session_id'));
		$where = [
				'session_id' => $rand,
				'status'     => 0, 
			];

		$del = Db::name('huanke_user')->where($where)->delete();	
		if($del){
			jsonReturn('001',null,"成功取消排队！");
		}else{
			jsonReturn('002',null,"取消失败！");
		}

	}

}



?>