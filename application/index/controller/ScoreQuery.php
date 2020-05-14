<?php
/**
 * Created by sublime 3.0
 * User: 愿得一人行
 * Date: 2017/10/29
 * Time: 11:56
 */

namespace app\index\controller;

use app\common\controller\BaseController;
use think\Db;

use think\Session;

class ScoreQuery extends BaseController
{

	/**
	 * 成绩查询-学生页面
	 */
	public function ScoreQueryStudent()
	{
		$user_uid = Session::get('user_info.user_uid');
        $lab_id = Session::get('user_infocas.labid');
		//查询学生表
        $where = [
            'user_uid' => $user_uid,
            'lab_id'   => $lab_id
        ];
        $user_student = Db::name('user_student')->where($where)->select();
        //遍历查询成绩表
        $arr = [];
        $ret = [];
        $student_score = Db::name('student_score');
        foreach($user_student as $k=>$v){
            $ret[$k] = $student_score->where('student_id', $v['id'])->select();
            //遍历加入课程信息
            foreach($ret[$k] as $key=>$val){
                $arr[$k][$key]['curriculum_num']  = $v['curriculum_num'];   //课程编号
                $arr[$k][$key]['curriculum_name'] = $v['curriculum_name'];  //课程名称
                $arr[$k][$key]['ex_results']      = $val['ex_results'];     //实验成绩
                $arr[$k][$key]['re_results']      = $val['re_results'];     //报告成绩
                $arr[$k][$key]['sum_results']     = $val['sum_results'];    //总成绩
                
            } 
        }
        //将二维数组装换成一维数组
        $result = [];
        foreach($arr as $key=>$val){
            foreach($val as $k=>$v){
                $result[] = $v;
            }
        }
        if($result){
            jsonReturn('001',$result);
        }else{
            jsonReturn('002',null,'您目前没有考试成绩！');
        }

	}  




	/**
	 * 成绩查询-教师页面
	 */
	public function ScoreQueryTeacher()
	{
		$id = input('id');
        //查询课程编号
        $start_curriculum = Db::name('start_curriculum')->where('id',$id)->find();
        //联合查询学生表和成绩表
        $field = 'b.user_uid,b.name,b.curriculum_name,b.curriculum_num,a.id as score_id,a.ex_results,a.re_results,a.sum_results';
        $map = [
            'b.curriculum_num' => $start_curriculum['curriculum_num']
        ];
        $result = Db::name('student_score')
                    ->alias('a')
                    ->join('nk_user_student b','a.student_id=b.id')
                    ->where($map)
                    ->field($field)
                    ->select();
        if($result){
            jsonReturn('001',$result);
        }else{
            jsonReturn('002',null,'该课程下没有考试成绩！');
        }

	}

    /**
     * 成绩批改-教师页面
     */
    public function CorrectionsScore()
    {
        $id = input('id');
        $name = input('name');

        //查询实验表
        $score = Db::name('student_score')->where('id',$id)->find();
        $score['user_name'] = $name;

        if($score){
            jsonReturn('001',$score);
        }else{
            jsonReturn('002','查询失败！');
        }


    }


    /**
     * 成绩批改-批改提交
     */
    public function CorrectionsUpdate()
    {
        $id          = input('id'); 
        $re_results  = input('report');
        $sum_results = input('total');
        //$comment     = input('comment');

        $data = [
            're_results'  =>  $re_results,
            'sum_results' =>  $sum_results,
            //'comment'     =>  $comment,
        ];

        $ret = Db::name('student_score')->where('id',$id)->update($data);
        if($ret){
            jsonReturn('001','提交成功！');
        }else{
            jsonReturn('002','提交失败！');
        }

    }





    /**
     * 课程成绩比例图
     */
	public function experimentCorrScore(){
	   $id = input('id');
       //查询课程编号
       $num = Db::name('start_curriculum')->where('id',$id)->find();

       //课程成绩比例图
       $field = 'b.name as user_name,b.curriculum_name,a.sum_results';
       $result = Db::name('student_score')
                    ->alias('a')
                    ->join('nk_user_student b','a.student_id=b.id')
                    ->where('b.curriculum_num',$num['curriculum_num'])
                    ->order('sum_results desc')
                    ->field($field)
                    ->select();
        if($result){
            $curr_name = $result[0]['curriculum_name'];
            $score1 = 0;
            $score2 = 0;
            $score3 = 0;
            $score4 = 0;
            $score5 = 0;
            foreach($result as $k=>$v){
                if($v['sum_results'] < 60){
                    $score1 += 1;
                }
                if($v['sum_results'] >= 60 && $v['sum_results'] < 70){
                    $score2 += 1;
                }
                if($v['sum_results'] >= 70 && $v['sum_results'] < 80){
                    $score3 += 1;
                }
                if($v['sum_results'] >= 80 && $v['sum_results'] < 90){
                    $score4 += 1;
                }
                if($v['sum_results'] >= 90 && $v['sum_results'] < 100){
                    $score5 += 1;
                }
            }
            $arr = [
                'score1' => $score1,
                'score2' => $score2,
                'score3' => $score3,
                'score4' => $score4,
                'score5' => $score5,
            ];

            $pie = array('curr_name' => $curr_name, 'arr' => $arr);

            //前十名学生成绩分数图
            $result1 = Db::name('student_score')
                        ->alias('a')
                        ->join('nk_user_student b','a.student_id=b.id')
                        ->where('b.curriculum_num',$num['curriculum_num'])
                        ->order('sum_results desc')
                        ->field($field)
                        ->limit(10)
                        ->select();
            $user_name = array_map('array_shift',$result1);
            $score = array_map('end',$result1);

            $bar = array('user_name' => $user_name, 'score' => $score);

            $array = array('pie' => $pie, 'bar' => $bar);
            if($array){
                jsonReturn('001',$array);
            }
        }else{

            $arr = [
                'score1' => 0,
                'score2' => 0,
                'score3' => 0,
                'score4' => 0,
                'score5' => 0,
            ];
            $curr_name = $num['curriculum_name'];
            $pie = array('curr_name' => $curr_name, 'arr' => $arr);

            $bar = [];
            $array = array('pie' => $pie, 'bar' => $bar);

            jsonReturn('002',$array,'当前没有考试成绩，无法统计数据！');
        }
        

    }




    /**
     * 教师部分-每次试验的学生成绩平均值（柱状图）
     */
  //   public function AverageAchievement()
  //   {
  //   	$user_id = Session::get('user_info.user_id');
		// $experiment = Db::name('experiment')->where('user_id',$user_id)->select();

		// $achievement = Db::name('achievement');
		// $arr    = [];
		// $avg    = [];
		// $x_data = [];
  //       $y_data = [];
		// foreach($experiment as $k=>$v){
		// 	$arr[$k] = $achievement->where('experiment_id',$v['id'])->select();

		// 	$avg[$k] = round(array_sum(array_column($arr[$k],'total_score'))/count($arr[$k]),2);
		// 	array_push($x_data,$v['experiment_name']);
  //           array_push($y_data,$avg[$k]);
		// }
       
  //       $title = [
  //           'text' => '实验与成绩关系图'
  //       ];

  //       $options = [
  //           'title'  => $title,
  //           'x_data' => $x_data,
  //           'y_data' => $y_data
  //       ];

  //       jsonReturn('001',$options);

  //   }



    /**
     * 教师部分-某个实验的学生成绩分布图（饼状图）
     */
    // public function  ExperimentalResults()
    // {
    // 	$id = input('post.id');
    // 	$user_id = Session::get('user_info.user_id');
    // 	$experiment = Db::name('experiment');
    // 	if(empty($id)){
    // 		$ret = $experiment->where('user_id',$user_id)->find();
    // 	}else{
    // 		$ret = $experiment->where('id',$id)->find();
    // 	}

    // 	//查询成绩
    // 	$achievement = Db::name('achievement');
    // 	//60分以下
    // 	$ret1 = $achievement->where('experiment_id',$ret['id'])->where('total_score','<',60)->count();
    // 	//60分~70分
    // 	$ret2 = $achievement->where('experiment_id',$ret['id'])->where('total_score','>=',60)->where('total_score','<',70)->count();
    // 	//70分~80分
    // 	$ret3 = $achievement->where('experiment_id',$ret['id'])->where('total_score','>=',70)->where('total_score','<',80)->count();
    // 	//80分~90分
    // 	$ret4 = $achievement->where('experiment_id',$ret['id'])->where('total_score','>=',80)->where('total_score','<',90)->count();
    // 	//90分~100分
    // 	$ret5 = $achievement->where('experiment_id',$ret['id'])->where('total_score','>=',90)->where('total_score','<',100)->count();

    // 	//组合
    // 	$series_data = [
    // 		['value' => $ret1, 'name' => '60分以下'],
    // 		['value' => $ret2, 'name' => '60分~70分'],
    // 		['value' => $ret3, 'name' => '70分~80分'],
    // 		['value' => $ret4, 'name' => '80分~90分'],
    // 		['value' => $ret5, 'name' => '90分~100分'],
    // 	];


    // 	//拼接数组
    // 	$title = [
    //         'text' => $ret['experiment_name'] . '实验的成绩比例图'
    //     ];
    //     $legend = [
    //     	'data' => ['60分以下','60分~70分','70分~80分','80分~90分','90分~100分']
    //     ];
    //     $series = [
    //     	'name' => '成绩分数段',
    //     	'data' => $series_data,
    //     ];

    //     $options = [
    //         'title'  => $title,
    //         'legend' => $legend,
    //         'series' => $series
    //     ];

    //     jsonReturn('001',$options);
    // }



}
