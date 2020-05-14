<?php
/**
 * Created by sublime 3.0
 * User: 愿得一人行
 * Date: 2017/9/2
 * Time: 14:56
 */

namespace app\index\controller;

use app\common\controller\BaseController;
use think\Loader;
use think\Db;
use think\Session;

class Coursemanage extends BaseController
{

	/**
	 * 基础课程表信息通过excel表格批量导入和批量导出
	 */
	//课程表excel表格模板下载
    public function downloadExcelCurr()
    {	
        BaseTemplate('curriculumtable.xlsx','课程信息表');
    }

    //批量导入课程信息-文件上传 
    public function uploadCurr()
    {
        upload();
    }

    //批量导入课程信息-写入数据库 
    public function inserExcelCurr()
    {
        Loader::import('PHPExcel.Classes.PHPExcel');
        Loader::import('PHPExcel.Classes.PHPExcel.IOFactory.PHPExcel_IOFactory');
        Loader::import('PHPExcel.Classes.PHPExcel.Reader.Excel5');

        $data = input('post.data/a');

        if(!$data || !$data['url'] || !$data['name']){
            jsonReturn("002",null,"请先上传文件！");
            return;
        }

        //获取文件路径
        $file_name = ROOT_PATH . $data['url'];

        $objReader =\PHPExcel_IOFactory::createReader('Excel2007');
        $obj_PHPExcel =$objReader->load($file_name, $encode = 'utf-8');  //加载文件内容,编码utf-8

        $excel_array=$obj_PHPExcel->getsheet(0)->toArray();   //转换为数组格式
        //判断上传表格是否为课程表
        $user_name = array('课程编号','课程名称','学时','学分','上课周次','学年','学期','学院','专业');

        foreach ($excel_array[0] as $k => $v) {
            if(empty($v)){
                unset($excel_array[0][$k]);
            }
        }

        if($excel_array[0] === $user_name){
            array_shift($excel_array);  //删除第一个数组(标题);
            $city = [];
            $base_curriculum = Db::name('curriculum');
            foreach($excel_array as $k=>$v) {
                if(empty(trim($v[0])) || empty(trim($v[1]))){
                    continue;
                }
                //用户信息插入数据库
                $city[$k]['curriculum_num']   = $v[0];         			//课程编号
                $city[$k]['curriculum_name']  = $v[1];         			//课程名称
                $city[$k]['hours']      	  = $v[2];         			//学时
                $city[$k]['credit']           = $v[3];         			//学分
                $city[$k]['class_frequency']  = $v[4];         			//上课周次
                $city[$k]['school_year']      = $v[5];        			//学年
                $city[$k]['semester']         = $v[6];         		//学期
                $city[$k]['college']          = $v[7];         		//学院
                $city[$k]['major']            = $v[8];         		//专业
                $city[$k]['create_time']      = date("Y-m-d H:i:s");    //创建时间
                $city[$k]['update_time']      = date("Y-m-d H:i:s");    //更新时间

                //验证课程编号和课程名称是否已经存在
                $where = array(
                	'curriculum_num'  => $v[0],		//课程编号
                	'curriculum_name' => $v[1],		//课程名称
                	'school_year'     => $v[5],		//学年
                	'semester'        => $v[6],	//学期
                );
                $check = $base_curriculum->where($where)->select();
                $n = [];
                if($check){
                	$n[] = $k+1;
                }

            }
            if(!empty($n)){
            	$num = implode(',', $n);
            	jsonReturn('002',null,"您所添加的第{$num}行的记录已经存在！");exit;
            }else{
            	$result = $base_curriculum->insertAll($city); //批量插入数据
            	if($result){
                    $event = Loader::controller('Api','event');
                    $event->insertImport($data,'基础课程信息');

	                jsonReturn('001',null,"批量导入成功！");
	            }else{
	                jsonReturn('007',null,"数据存储失败！");
	            }
            }                
        }else{
            jsonReturn('009',null,"上传文件不是课程信息表！");
        }
    }

    //教务人员课程列表显示
    public function TeachIndexCurr()
    {
        $ret = Db::name('curriculum')->select();
        if($ret){
            jsonReturn('001',$ret);
        }else{
            jsonReturn('002',null,"查询失败！");
        }
    }




    //课程信息列表显示
    public function IndexCurr()
    {
        $map = [
            'b.user_id' => Session::get('user_info.user_id'),
            'b.status' => 1
        ];

        $fields = 'a.id,a.curriculum_name,a.curriculum_num,a.hours,a.credit';

        $ret = Db::name('curriculum')
            ->alias('a')
            ->join('nk_start_curriculum b','b.curriculum_id=a.id')
            ->where($map)
            ->field($fields)
            ->select();
        if($ret){
            jsonReturn('001',$ret);
        }else{
            jsonReturn('002',null,"查询失败！");
        }
    }

    /**
     * 课程列表显示
     */
    public function showListCurr()
    {
        $ret = Db::name('curriculum')->field('id,curriculum_name,curriculum_num')->select();
        foreach($ret as $k=>$v){
            $rut[$v['id']] = $v['curriculum_name'] . '(' . $v['curriculum_num'] . ')';
        }
        if($rut){
            jsonReturn('001',$rut);
        }else{
            jsonReturn('002',null,"查询失败！");
        }
    }


    //删除课程表记录
    public function deleteCurr()
    {
        $id  = input('post.id');

        $map = [
            'curriculum_id' => $id
        ];

    	Db::startTrans();
    	try{
            Db::name('curriculum')->where('id','in', $id)->delete();
            Db::name('start_curriculum')->where($map)->delete();
            Db::commit();
            jsonReturn('001',null,"删除成功！");
        }catch (\Exception $e) {
    	    Db::rollback();
            jsonReturn('002',null,"删除失败！");
        }

    }






    /**
     * 查询课程信息(用于教师查询),当前登陆教师，查询已通过审核的课程，用于添加知识点
     */
    public function QueryCurrList()
    {   
        $user_id = Session::get('user_info.user_id');

        $map = [
            'a.status'    =>  1,
            'a.user_id'   =>  $user_id
        ];

        $fields = "b.id,b.curriculum_name,b.curriculum_num";

        $res = Db::name('start_curriculum')
            ->alias('a')
            ->join('nk_curriculum b','a.curriculum_id=b.id')
            ->where($map)
            ->field($fields)
            ->select();
        foreach($res as $k=>$v){
            $res[$k]['curr_name'] = $v['curriculum_name'] . '(' .$v['curriculum_num'] . ')';
        }
        if($res){
            jsonReturn('001',$res);
        }else{
            jsonReturn('002',null,"未查到符合要求的课程！");
        }
    }


 



}
