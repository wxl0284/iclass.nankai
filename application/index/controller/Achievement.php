<?php
/**
 * Created by sublime 3.0
 * User: 愿得一人行
 * Date: 2017/9/11
 * Time: 11:56
 */

namespace app\index\controller;

use app\common\controller\BaseController;
use think\Loader;
use think\Db;
use think\Request;
use think\Session;

class Achievement extends BaseController
{


	/**
	 * 实验批改
	 */
	//列表显示
	public function experimenstList()
	{
        
		$user_id = Session::get('user_info.user_id');
		//查询布置试验表
		$ret = Db::name('experiment')->where('user_id',$user_id)->select();

		if(empty($ret)){
			jsonReturn('002',null,"您当前没有可批改的试验！");exit;
		}
		
		//获取当前时间
		$time = date("Y-m-d H:i:s");
		//比对当前时间与实验发布时间
		$arr = [];
		foreach($ret as $k=>$v){
			if($v['start_time'] >= $time){
				$arr[$k] = $v;
				$arr[$k]['state'] = "实验中未开始"; 
				$arr[$k]['time'] = $v['start_time'] . " 至 " . $v['end_time'];
			}
			if($v['start_time'] <= $time && $v['end_time'] >= $time){
				$arr[$k] = $v;
				$arr[$k]['state'] = "实验中"; 
				$arr[$k]['time'] = $v['start_time'] . " 至 " . $v['end_time'];
			}
			if($v['end_time'] < $time){
				$arr[$k] = $v;
				$arr[$k]['state'] = "实验结束";
				$arr[$k]['time'] = $v['start_time'] . " 至 " . $v['end_time'];
			}
		}

		$achievement = Db::name('achievement');
		$curriculum = Db::name('curriculum');
		//查询提交状态和批改状态
		for($z=0;$z<count($arr);$z++){
			//查询提交个数
			$achieve[$z] = $achievement->where('experiment_id',$arr[$z]['id'])->where('submit_state','已提交')->count();
			$arr[$z]['submit_num'] = $achieve[$z];
			//查询未批改个数
			$where = [
				'experiment_id' => $arr[$z]['id'],
				'total_score'   => null,
			];
			$corr_num[$z] = $achievement->where($where)->count();
			$arr[$z]['correcting_num'] = $corr_num[$z];
			//替换课程名称
			$curr_name[$z] = $curriculum->where('id',$arr[$z]['curriculum_id'])->find();
			$arr[$z]['curriculum_name'] = $curr_name[$z]['curriculum_name'] . '(' . $curr_name[$z]['curriculum_num'] . ')';
	
		}
		if($arr){
			jsonReturn('001',$arr);
		}else{
			jsonReturn('002',null,"查询失败！");
		}

	}


	//选择开课名称-列表查询
	public function startCurriculumList()
	{
		$id = input('post.id');

		$experiment = Db::name('experiment')->where('curriculum_id',$id)->select();

		//替换课程名称
		$curr = Db::name('curriculum')->where('id',$id)->find();

		//获取当前时间
		$time = date("Y-m-d H:i:s");
		//比对当前时间与实验发布时间
		$arr = [];
		$achievement = Db::name('achievement');
		foreach($experiment as $k=>$v){
			if($v['start_time'] >= $time){
				$experiment[$k]['state'] = "实验中未开始"; 		
			}
			if($v['start_time'] <= $time && $v['end_time'] >= $time){
				$experiment[$k]['state'] = "实验中"; 
			}
			if($v['end_time'] < $time){
				$experiment[$k]['state'] = "实验结束";
			}
			$experiment[$k]['time'] = $v['start_time'] . " 至 " . $v['end_time'];
			//替换课程名称
			$experiment[$k]['curriculum_name'] = $curr['curriculum_name'] . '(' . $curr['curriculum_num'] . ')';

			//查询提交个数
			$experiment[$k]['submit_num'] = $achievement->where('experiment_id',$v['id'])->where('experiment_score','<>','')->whereOr('presentation_name','<>','')->count();
			//查询未批改个数
			$experiment[$k]['correcting_num'] = $achievement->where('experiment_id',$v['id'])->where('total_score',null)->count();

		}
		if($experiment){
            jsonReturn('001',$experiment);
        }else{
            jsonReturn('002',null,"没有符合要求的实验！");
        }

	}





    /**
     * 实验批改页面查询列表
     */
    public function searchList()
    {
        
        $id = input('post.id');
       	$ret = Db::name('achievement')->where('experiment_id',$id)->select();

        if($ret){
            jsonReturn('001',$ret);
        }else{
            jsonReturn('002',null,"没有符合要求的学生！");
        }
	}


    /**
     * 批改页面查询
     */
    public function searchTest()
    {
        
        $data = input('post.data/a');
        $id = input('post.id');

        $name = $data['name'];
        $number = $data['number'];
        $state = $data['state'];

        $where = [];
        if(!empty($name)){
            $where['user_name'] = $name;
        }
        if(!empty($number)){
            $where['user_uid'] = $number;
        }
        if(!empty($state)){
            $where['submit_state'] = $state;
        }

        //查询成绩表
        $where['experiment_id'] = $id;
        $score = Db::name('achievement')->where($where)->select();

        if($score){
            jsonReturn('001',$score);
        }else{
            jsonReturn('002',null,"没有相关的信息！");
        }
    }


	//点击批改学生成绩显示信息
	public function CorrectingIndex()
	{
        
		$id   = input('post.id');
		//查询布置试验表id
		$ret = Db::name('achievement')->where('id',$id)->find();
		if($ret){
			jsonReturn('001',$ret);
		}else{
			jsonReturn('002',null,"查询失败！");
		}

	}
	//点击批改学生成绩
	public function CorrectingStudent()
	{
        
		$id = input('post.id');
		$data = input('post.data/a');

		$save = [
			'presentation_score' => $data['score_report'],                      //报告成绩
			'total_score'        => $data['total'],    							//总成绩
			'comment'            => $data['remark'],                            //评语
			'update_time'        => date("Y-m-d H:i:s"),                        //更新时间
		];
		$result = Db::name('achievement')->where('id', $id)->update($save);
		if($result){
			jsonReturn('001',null,"批改成功！");
		}else{
			jsonReturn('002',null,"批改失败！");
		}


	}

	//点击下载实验报告
	public function CorrectingDownload()
	{
		$id = input('id');

		$achievement = Db::name('achievement')->where('id',$id)->find();
		$file = $achievement['presentation_url'];
		$name = $achievement['presentation_name'];
	    if(is_file($file)){
	        $length = filesize($file);
	        $type = mime_content_type($file);
	        //$showname = ltrim(strrchr($file,'/'),'/');
	        //$filename = $name . strrchr($showname,'.');
	        header("Content-Description: File Transfer");
	        header('Content-type: ' . $type);
	        header('Content-Length:' . $length);
	        if(preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])){ //for IE
	            header('Content-Disposition: attachment; filename="' . rawurlencode($name) . '"');
	        }else{
	            header('Content-Disposition: attachment; filename="' . $name . '"');
	        }
	        readfile($file);
	    }else{
//	       jsonReturn('002',null,'下载失败！源文件地址有误！请联系管理员!');
            @header("http/1.1 404 not found");
            @header("status: 404 not found");
            echo "<script>alert(\"文件不存在！\");window.history.go(-1);window.location.reload();</script>";
            exit();
	    }
	}








	/**
	 * 成绩导出
	 */
	//查询某课程下的实验名称
	public function queryExperiment()
	{
        $user_id = Session::get('user_info.user_id');
		$id = input('post.id'); //课程名称
		if($id){
            $map = [
                'user_id' => $user_id,
                'curriculum_id' => $id
            ];
			$ret = Db::name('experiment')->where($map)->select();
		}else{
		    $map = [
		        'user_id' => $user_id
            ];
			$ret = Db::name('experiment')->where($map)->select();
		}
		$arr = [];
		foreach($ret as $k=>$v){
			$arr[$k]['name'] = '【' . $v['start_time'] . '至' . $v['end_time'] . '】' . ' ' . $v['experiment_name'];
			$arr[$k]['id'] = $v['id'];
		}

		if($arr){
			jsonReturn('001',$arr);
		}else{
			jsonReturn('002',null,"该课程下暂无实验！");
		}

	} 


	//导出成绩表
	public function exportGrades()
	{
		$id = input('post.');
		//$ids = array_unique($id);
		//查询布置试验表
		$ret = Db::name('experiment')->where('id','in',$id)->find();

		//查询课程名称
		$curr_name = Db::name('curriculum')->where('id',$ret['curriculum_id'])->find();

		//将符合要求的学生成绩导出成excel表
		$data = Db::name('achievement')->where('experiment_id',$ret['id'])->select();

		if(empty($data)){
			$arr[0] = [
				'user_name'        	 => '',
				'user_uid' 		     => '',
				// 'class' 			 => '',
				'phone' 			 => '',
				'experiment_score' 	 => '',
				'presentation_score' => '',
				'total_score' 		 => '',
				'comment'			 => '',
				'experiment_name'	 => '',
				'curriculum_name'	 => '',
			];
		}else{
			foreach($data as $k=>$v){
				$arr[$k] = $v;
				$arr[$k]['experiment_name'] = $ret['experiment_name'];
				$arr[$k]['curriculum_name'] = $curr_name['curriculum_name'];
			}
		}
		$filename = $curr_name['curriculum_name'] . "成绩表";

		exportExcel($filename,$arr);   //导出excel表格
		exit;    

	}






}
