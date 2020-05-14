<?php
/**
 * Created by sublime 3.0
 * User: 愿得一人行
 * Date: 2017/9/30
 * Time: 10:56
 */

namespace app\index\controller;

use app\common\controller\BaseController;
use think\Db;
use think\Session;

class PersonalNotice extends BaseController
{

	/**
	 * 个人通知公告
	 */
	//查看消息列表
	public function index()
	{	
		$user_id = Session::get('user_info.user_id');
		$state = input('post.state',0);
		//查询用户-通知关联表
		$notice_id = Db::name('user_notice')->where('user_id',$user_id)->where('state',$state)->order('state')->select();
		$ids = array_column($notice_id, 'notice_id');
		//查询通知表
		$ret = Db::name('notice')->where('id','in',$ids)->select();
		if($ret){
            foreach ($ret as $key => $value) {
                $time = $value['update_time']?$value['update_time']:$value['create_time'];
                unset($ret[$key]['update_time']);
                unset($ret[$key]['create_time']);
                $ret[$key]['time'] = $time;
                $ret[$key]['content'] = html_entity_decode($ret[$key]['content']);
            }
			jsonReturn('001',$ret);
		}else{
			jsonReturn('002',null,"没有匹配到数据！");
		}
	}

	//查看单个消息
	public function read()
	{
		$id = input('post.id');
		//查询消息详情
		$ret = Db::name('notice')->where('id',$id)->find();
		if($ret){
			$user_id = Session::get('user_info.user_id');
			$where = array('notice_id' => $id, 'user_id' => $user_id);
			//查询消息状态
			$rut = Db::name('user_notice')->where($where)->find();
			if($rut['state'] === 0 ){
				$result = Db::name('user_notice')->where($where)->update(['state' => 1]);
			}
			//修改浏览次数
			Db::name('notice')->where('id',$id)->setInc('browse_num');

            $time = $ret['update_time']?$ret['update_time']:$ret['create_time'];
            unset($ret['update_time']);
            unset($ret['create_time']);
            $ret['time'] = $time;
            $ret['content'] = html_entity_decode($ret['content']);

			jsonReturn('001',$ret);
		}else{
			jsonReturn('002',null,"查询失败！");
		}
	}


	//删除个人消息
	public function delete()
	{
		$ids = input('post.ids');
		$user_id = Session::get('user_info.user_id');   //获取用户id

		//删除用户-公告表
		$ret[] = Db::name('user_notice')->where('user_id', $user_id)->where('notice_id','in',$ids)->delete();
		if($ret){
			jsonReturn('001',null,"删除成功！");
		}else{
			jsonReturn('002',null,"删除失败！");
		}
	}








}
