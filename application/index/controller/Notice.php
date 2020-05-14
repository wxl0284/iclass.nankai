<?php
/**
 * Created by sublime 3.0
 * User: 愿得一人行
 * Date: 2017/9/28
 * Time: 10:56
 */

namespace app\index\controller;

use app\common\controller\BaseController;
use think\Db;
use think\Exception;
use think\Session;
use think\Loader;

class Notice extends BaseController
{
	/**
	 * 超级管理员-通知公告（增删改查）
	 */

	//通知公告列表
	public function index()
	{
        $user_id = Session::get('user_info.user_id');

        $map = [
            'b.user_id' => $user_id
        ];

        $event = Loader::controller('Api','event');
        $role= $event->getRoleInfo();

        $sign = '';
        if($role){
            if(in_array(2,$role)){
                $sign = 'show';
            }else{
                $sign = 'hide';
            }

//            $ret = Db::name('notice')->order('id desc')->select();
            $ret = Db::name('notice')
                ->alias('a')
                ->join('nk_corr_user_notice b','a.id=b.notice_id')
                ->field('a.id,a.title,a.publisher,a.content,a.browse_num,a.create_time,a.update_time,b.status')
                ->where($map)
                ->order('a.id desc')
                ->select();


            if($ret){
                $res = [];
                foreach ($ret as $key => $value) {
                    $time = $value['update_time']?$value['update_time']:$value['create_time'];
                    unset($ret[$key]['update_time']);
                    unset($ret[$key]['create_time']);
                    $ret[$key]['time'] = $time;
                    unset($ret[$key]['content']);
                }
                $res['control'] = $sign;
                $res['data'] = $ret;
                jsonReturn('001',$res);
            }else{
                jsonReturn('002',null,'暂无信息！');
            }
        }else{
            jsonReturn('002',null,'您暂无权限进行操作！');
        }

	}


	//新增通知公告（实验室负责人）
	public function save()
	{
		$data = input('post.');
		$notice = Db::name('notice');

        $notice->startTrans();
        try{
            //新增数据
            $add = [
                'title'       => $data['title'],            //标题名
                'publisher'   => Session::get('user_info.user_name'),		//发布者
//			'content' 	  => htmlentities($data['content']),			//内容
                'content' 	  => $data['content'],			//内容
                'browse_num'  => 0,						//浏览次数
                'create_time' => date("Y-m-d H:i:s"),		//发布时间
                'update_time' => date("Y-m-d H:i:s")
            ];

            $noticeID = $notice->insertGetId($add);
            if(empty($noticeID)){
                jsonReturn('002',null,"新增失败！");
            }else{
                $ids = Db::name('user')->field('id')->select();
                $inser = [];
                foreach ($ids as $k => $v)
                {
                    $inser[$k]['notice_id']      = $noticeID;		   //通知公告表id
                    $inser[$k]['user_id']        = $v['id'];     //用户表id
                    $inser[$k]['status']         = 0;                 //消息状态（0：未查看，1：已查看）
                    $inser[$k]['create_time']    = date("Y-m-d H:i:s");
                }
                $result[] = Db::name('corr_user_notice')->insertAll($inser);
                if(empty($result)){
                    $notice->rollback();      //事务回滚
                    jsonReturn('002',null,"新增失败！");
                }else{
                    $notice->commit();		  //事务确认
                    jsonReturn('001',null,"新增成功！");
                }
            }
        }catch (Exception $e)
        {
            $notice->rollback();
            jsonReturn('002',null,"新增失败！");
        }
	}


	//查看单个通知公告
	public function read()
	{
		$id = input('post.id');
		$ret = Db::name('notice')->where('id',$id)->find();

		$map = [
		    'user_id' => Session::get('user_info.user_id'),
            'notice_id' => $id
        ];
		$up = [
		    'status' => 1
        ];
        Db::name('corr_user_notice')->where($map)->update($up);

		if($ret){
            $time = $ret['update_time']?$ret['update_time']:$ret['create_time'];
            unset($ret['update_time']);
            unset($ret['create_time']);
            $ret['time'] = $time;
            $ret['content'] = html_entity_decode($ret['content']);

			//修改浏览次数
			Db::name('notice')->where('id',$id)->setInc('browse_num');
			jsonReturn('001',$ret);
		}else{
            jsonReturn('002',null,'暂无信息！');
		}
	}

	//修改通知公告-修改显示
	public function edit()
	{
		$id = input('post.id');
		$ret = Db::name('notice')->where('id',$id)->find();
		//查询用户群id

		if($ret){
            $ret['content'] = html_entity_decode($ret['content']);

			jsonReturn('001',$ret);
		}else{
            jsonReturn('002',null,'查询失败！');
		}

	}


	//修改通知公告-修改更新
	public function update()
	{
		$data = input('post.');
		$id = $data['id'];
		$notice = Db::name('notice');
        $notice->startTrans();

        try{
            //新增数据
            $save = [
                'title'       => $data['title'],            //标题名
                'publisher'   => Session::get('user_info.user_name'),		//发布者
                'content' 	  => $data['content'],			//内容
                'browse_num'  => 0,
                'update_time' => date("Y-m-d H:i:s"),		//发布时间
            ];

            $num = $notice->where('id',$id)->update($save);
            if(empty($num)){
                jsonReturn('002',null,"修改失败！");
            }else{
                $cond = [
                    'notice_id' => $id
                ];
                $up = [
                    'status' => 0,
                    'update_time' => date("Y-m-d H:i:s")
                ];
                $result = Db::name('corr_user_notice')->where($cond)->update($up);
                if(empty($result)){
                    $notice->rollback();      //事务回滚
                    jsonReturn('002',null,"修改失败！");
                }else{
                    $notice->commit();		  //事务确认
                    jsonReturn('001',null,"修改成功！");
                }
            }
        }catch (Exception $e)
        {
            $notice->rollback();
            jsonReturn('002',null,"修改失败！");
        }

	}


	//删除通知公告
	public function delete()
	{
		$id = input('post.id');
		$notice = Db::name('notice');
        $notice->startTrans();
        try{
            //删除通知公告表
            $res = $notice->where('id',$id)->delete();
            Db::name('corr_user_notice')->where('notice_id',$id)->delete();
            if($res){
                $notice->commit();
                jsonReturn('001',null,"删除成功！");
            }elseif(0===$res){
                $notice->rollback();
                jsonReturn('002',null,"没有删除数据！");
            }else{
                $notice->rollback();
                jsonReturn('002',null,"删除失败！");
            }

        }catch (Exception $e)
        {
            $notice->rollback();
            jsonReturn('002',null,"删除失败！");
        }
	}






}
