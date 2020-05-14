<?php
/**
 * Created by PhpStorm.
 * User: 高笛淳
 * Date: 2017/9/4
 * Time: 14:43
 */

namespace app\index\controller;

use app\common\controller\BaseController;
use app\index\model\Attendance as AttendanceModel;
use app\index\model\CorrAttendance as CorrAttendanceModel;
use think\Request;
use think\Db;
use think\Loader;
class Attendance extends BaseController
{
    /**
     * 下载模板表
     */
    public function downBaseTemplate(){
        BaseTemplate('attendance.xlsx','考勤表');
    }

    /**
     * 上传Attendance excel数据
     */
    public function uploadAttendance(){
        $request = Request::instance();
        $file = $request->file('excel');
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->move(ROOT_PATH . 'public' . DS . 'phpexcel');
        if($info){
            $exclePath = $info->getSaveName();  //获取文件名
            $file_name = 'public' . DS . 'phpexcel' . DS . $exclePath;   //上传文件的地址

            //写入数据库
            $add = array(
                'name' => "考勤表",
                'url'  => $file_name,
                'date' => date("Y-m-d H:i:s"),
            );

            $result = Db::name('import')->insert($add);
            if($result){
                jsonReturn('001',null,"上传成功！");
            }else{
                jsonReturn('002',null,"上传失败！");
            }
        }else{
            jsonReturn('002',null,$file->getError());
        }
    }

    /**
     * 将导入的考勤管理信息写入数据库
     */
    public function writeDatabase(){
        
        Loader::import('PHPExcel.Classes.PHPExcel');
        Loader::import('PHPExcel.Classes.PHPExcel.IOFactory.PHPExcel_IOFactory');
        Loader::import('PHPExcel.Classes.PHPExcel.Reader.Excel5');

        $data = input('post.data/a');

        if(!$data || !$data['url'] || !$data['name']){
            jsonReturn("002",null,"请先上传文件！");
            return;
        }

        $file = ROOT_PATH .'/public/'. $data['url'];
        $objReader =\PHPExcel_IOFactory::createReader('Excel2007');
        $obj_PHPExcel =$objReader->load($file, $encode = 'utf-8');  //加载文件内容,编码utf-8
        $excel_array=$obj_PHPExcel->getsheet(0)->toArray();   //转换为数组格式
        //判断上传表格是否为用户表
        $first = array('实验名称','实验类型','所属课程','教学实验室','实验日期','开始时间','结束时间','预约人数','签到人数','未签到人数','出勤率');

        $attendance = [];
        $student = [];
        $res = [];
        if(empty(array_diff($excel_array[0],$first))){
            unset($excel_array[0]);
            //出勤信息
            $attendance = [
                'experiment_name'       =>  $excel_array[1][0],
                'experiment_type'       =>  $excel_array[1][1],
                'experiment_course'     =>  $excel_array[1][2],
                'experiment_address'    =>  $excel_array[1][3],
                'date'                  =>  $excel_array[1][4],
                'start_time'            =>  $excel_array[1][5],
                'end_time'              =>  $excel_array[1][6],
                'bespeak_num'           =>  $excel_array[1][7],
                'sign_num'              =>  $excel_array[1][8],
                'unsign_num'            =>  $excel_array[1][9],
                'rate'                  =>  $excel_array[1][10],
            ];

            unset($excel_array[1]);
            unset($excel_array[2]);

            Db::startTrans();
            try{
                $AttendanceModel = new AttendanceModel;
                $AttendanceModel->save($attendance);
                $attendance_id = $AttendanceModel->id;

                foreach ($excel_array as $key => $v){
                    if(empty(trim($v[0])) || empty(trim($v[1])) || empty(trim($v[3]))){
                        continue;
                    }
                    $student[] = [
                        'student_id'     =>  $v[0],
                        'name'           =>  $v[1],
                        'class'          =>  $v[2],
                        'is_attendance'  =>  $v[3],
                        'attendance_id'  =>  $attendance_id,
                    ];
                }

                $CorrAttendanceModel = new CorrAttendanceModel;
                $CorrAttendanceModel->saveAll($student);

                $event = Loader::controller('Api','event');
                $event->insertImport($data,'考勤信息');

                Db::commit();
                jsonReturn('001',null,'数据导入成功！');
                return;
            }catch (\Exception $e){
                Db::rollback();
                jsonReturn('002',null,"数据存储失败！");
            }
        }else{
            jsonReturn('002',null,"上传数据有误，请核实后再传！");
        }
    }

    /**
     * 以实验为基础，显示单次实验的考勤整体信息
     */
    public function showAttendance(){
        
        $col = AttendanceModel::all();
        $res = [];
        if(null !== $col){
            foreach ($col as $key => $value) {
                $res[] = $value->toArray();
            }
        }
        jsonReturn('001',$res);
    }

    /**
     * 以实验为基础，显示单次实验所有学生的考勤详细信息
     */
    public function showAttendanceDetail(){
        
        $id = trim(input('post.id'));
        $map = [
            'attendance_id' =>  $id
        ];
        $col = CorrAttendanceModel::all($map);
        $res = [];
        if(null !== $col){
            foreach ($col as $key => $value) {
                $res[] = $value->toArray();
            }
        }
        jsonReturn('001',$res);
    }


    /**
     * 删除考勤信息
     */
    public function showAttendanceDelete()
    {
        
        $id = trim(input('post.id'));

        $attendance = Db::name('attendance');             //实例化考勤表
        $corr_attendance = Db::name('corr_attendance');   //实例化学生出勤情况表
        $attendance->startTrans();                        //开启事务

        //删除考勤表中数据
        $ret = $attendance->where('id',$id)->delete();
        if(empty($ret)){
            jsonReturn('002',null,"删除失败！");
        }else{
            //删除学会说呢过出勤情况表
            $result = $corr_attendance->where('attendance_id', $id)->delete();
            if(empty($result)){
                $attendance->rollback();          //事务回滚
            }else{
                $attendance->commit();
                jsonReturn('001',$ret,"删除成功！");
            }
        }

    }










}