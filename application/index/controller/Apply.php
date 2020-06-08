<?php
/**
 * Created by PhpStorm.
 * User: 高笛淳
 * Date: 2017/10/11
 * Time: 9:45
 */

namespace app\index\controller;

use app\common\controller\BaseController;
use think\Db;
use think\Exception;
use think\Session;
class Apply extends BaseController
{
    //-------------------------------------------------开课---------------------------------------------------------//
    /**
     * 开课申请
     */
    public function apply()
    {
        $user_id = Session::get('user_info.user_id');
        
        $data = input('');

        if(!$data['belong_college']){
            jsonReturn('002',null,"请选择学院信息！");
            return;
        }
        if(!$data['curriculum_name']){
            jsonReturn('002',null,"请添加课程名称！");
            return;
        }
        if(!$data['lab_id']){
            jsonReturn('002',null,"请选择实验室！");
            return;
        }

       
        if ( !$data['id'] )//首次提交开课申请
        {
            //验证该门课程是否已经申请过
            $where = [
                'curriculum_name' => $data['curriculum_name'],
                'status'          => array('neq',2)
            ];
            $res  = Db::name('startCurriculum')->where($where)->find();
            if($res){
                jsonReturn('002',null,"您已经申请了该门课程！");
            }else{
                $insert = [
                    'lab_id'           => $data['lab_id'],     
                    'curriculum_name'  => $data['curriculum_name'],
                    'curriculum_guide' => $data['curriculum_guide'],
                    'curriculum_rec'   => $data['curriculum_rec'],
                    'belong_college'   => $data['belong_college'],
                    'user_id'          => $user_id,
                    'flag'             => 0,
                    'create_time'      => date("Y-m-d H:i:s"),
                    'update_time'      => date("Y-m-d H:i:s")
                ];

                $id = Db::name('startCurriculum')->insertGetId($insert);

                if($id){
                    jsonReturn('001',$id,'已申请，请等待审核！');
                }else{
                    jsonReturn('002',null,'申请失败，请重新申请！');
                }
            }
        }else{//被驳回后再次提交开课申请
            $temp = [
                'curriculum_name'  => $data['curriculum_name'],
                'curriculum_guide' => $data['curriculum_guide'],
                'curriculum_rec'   => $data['curriculum_rec'],
                'belong_college'   => $data['belong_college'],
                'user_id'          => $user_id,
                'flag'             => 0,
                'status'             => 0,//未审核
                'create_time'      => date("Y-m-d H:i:s"),
                'update_time'      => date("Y-m-d H:i:s")
            ];

            $r = Db::table('nk_start_curriculum')->where('id', $data['id'])->update($temp);
            
            if ($r)
            {
                jsonReturn('001', $data['id'], '已申请，请等待审核！');
            }else{
                jsonReturn('002', null, '提交失败，请重新提交！');
            }
        }//else 结束
        
    }

    /**
     * 开课申请审批列表
     */
    public function applyList()
    {
        $status = input('status',0);
        $lab_id = Session::get('user_infocas.labid');  //获取当前进入的实验室id
        $fields = 'a.id,a.status,a.curriculum_name,a.curriculum_guide,a.curriculum_rec,a.belong_college,b.name as teacher,c.college_name';

        $map = [
            'a.status' => $status,
            'a.lab_id' => $lab_id
        ];
        $res = [];
        $res = Db::name('start_curriculum')
            ->alias('a')
            ->field($fields)
            ->join('nk_user b','a.user_id=b.id')
            ->join('nk_base_college c','a.belong_college=c.id')
            ->where($map)
            ->select();

        jsonReturn('001',$res);
    }

    /**
     * 开课申请审核
     */
    public function check()
    {
        $d = input();

        $id = $d['id'];
        $status = $d['status'];
        
        $update = [
            'status'         => $status,
            'curriculum_num' => date('Y') . time(),
            'checker_id'    => Session::get('user_info.user_id'),
            'check_time'    => date("Y-m-d H:i:s",time()),
        ];

        $ret = Db::name('start_curriculum')->where('id',$id)->update($update);

        if($ret){
            //查询资源信息
            switch($status){
                case '1':
                    $status = "已通过";
                    break;
                case '2':
                    $status = "未通过";
                    break;
            }
            jsonReturn("001",$status,'操作成功！');
        }elseif($ret === 0){
            jsonReturn("002",null,'已审核！');
        }else{
            $status = '未审核';
            jsonReturn("002",$status,'操作失败！');
        }
    }

    /**
     * 开课申请详情
     */
    public function detail()
    {
        $id = input('post.id');
        $fields = 'a.id,a.status,a.curriculum_name,a.curriculum_guide,a.curriculum_rec,a.belong_college,b.name as teacher,c.college_name';
        $map['a.id'] = $id;

        $res = Db::name('start_curriculum')
            ->alias('a')
            ->field($fields)
            ->join('nk_user b','a.user_id=b.id')
            ->join('nk_base_college c','a.belong_college=c.id')
            ->where($map)
            ->select();

        if($res){
            foreach ($res as $k => $v) {
                $res[$k]['curriculum_guide'] = html_entity_decode($v['curriculum_guide']);
                $res[$k]['curriculum_rec'] = html_entity_decode($v['curriculum_rec']);
            }
        }

        if($res){
            jsonReturn('001',$res);
        }else{
            jsonReturn('002',null,"查询失败！");
        }
    }

    /**
     *  删除开课申请信息
     */
    public function remove()
    {
        $id = input('post.id');

        $map = [
            'id' =>  $id
        ];
        Db::startTrans();
        try{
            $del= Db::name('start_curriculum')->where($map)->delete();
            Db::name('corr_teaching')->where('curriculum_id',$id)->delete();
            Db::name('order')->where('curriculum_id',$id)->delete();
            Db::commit();
            jsonReturn('001',$del,"删除成功！");
        }catch (Exception $e) {
            Db::rollback();
            jsonReturn('002',null,"删除失败！");
        }
    }

    /**
     *  删除 课程申请使用
     */
    public function remove_use () {
        $id = input('post.id');

        Db::startTrans();
        try{
            //仅删除nk_corr_teaching和nk_order表中记录
            $del = Db::name('corr_teaching')->where('id', $id)->delete();
            Db::name('order')->where('corr_id', $id)->delete();
            Db::commit();
            jsonReturn('001',$del,"删除成功！");
        }catch (Exception $e) {
            Db::rollback();
            jsonReturn('002',null,"删除失败！");
        }
    }


    /**
     * 教师端开课申请已通过审核列表
     */
    public function applyTeacherCheckedList(){
        $res = [];
        $user_id = Session::get('user_info.user_id');
        $lab_id = Session::get('user_infocas.labid');  //获取当前进入的实验室id

        if($user_id){
            $fields = 'a.id,a.status,a.curriculum_name,a.curriculum_guide,a.curriculum_rec,a.belong_college,b.name as teacher,c.college_name';

            $map = [
                'a.status'  => 1,
                'a.user_id' => $user_id,
                'a.lab_id'  => $lab_id
            ];

            $res = Db::name('start_curriculum')
                ->alias('a')
                ->field($fields)
                ->join('nk_user b','a.user_id=b.id')
                ->join('nk_base_college c','a.belong_college=c.id')
                ->where($map)
                ->select();

        }
        jsonReturn('001',$res);
    }

    /**
     * 教师端开课申请待审核审核列表
     */
    public function applyTeacherWaitList(){
        $res = [];
        $user_id = Session::get('user_info.user_id');
        $lab_id = Session::get('user_infocas.labid');  //获取当前进入的实验室id
        if($user_id){
            $fields = 'a.id,a.status,a.curriculum_name,a.curriculum_guide,a.curriculum_rec,a.belong_college,b.name as teacher,c.college_name';

            $map = [
                'a.status'  => 0,
                'a.user_id' => $user_id,
                'a.lab_id'  => $lab_id
            ];

            $res = Db::name('start_curriculum')
                ->alias('a')
                ->field($fields)
                ->join('nk_user b','a.user_id=b.id')
                ->join('nk_base_college c','a.belong_college=c.id')
                ->where($map)
                ->select();

        }
        jsonReturn('001',$res);
    }

    public function reject_lessons(){//查询该老师被驳回的开课申请
        $res = [];
        $user_id = Session::get('user_info.user_id');
        $lab_id = Session::get('user_infocas.labid');  //获取当前进入的实验室id
        
        if($user_id){
            $fields = 'a.lab_id,a.id,a.status,a.curriculum_name,a.curriculum_guide,a.curriculum_rec,a.belong_college,a.reason,b.name as teacher,c.college_name';

            $map = [
                'a.status'  => 2,//start_curriculum表 status=2 被驳回
                'a.user_id' => $user_id,
                'a.lab_id'  => $lab_id
            ];

            $res = Db::name('start_curriculum')
                ->alias('a')
                ->field($fields)
                ->join('nk_user b','a.user_id=b.id')
                ->join('nk_base_college c','a.belong_college=c.id')
                ->where($map)
                ->select();

        }

        if ( $res )//处理被驳回课程中 课程介绍和大纲的html实体
        {
            foreach ($res as $k => $v)
            {
                $res[$k]['curriculum_guide'] = html_entity_decode( $v['curriculum_guide'] );
                $res[$k]['curriculum_rec'] = html_entity_decode( $v['curriculum_rec'] );
            }
        }
        jsonReturn('001',$res);
    }//reject_lessons 结束

    //-------------------------------------------------上课---------------------------------------------------------//

    /**
     * 已经审核通过可以选为上课的课程
     */
//    public function listCheckedCourse() {
//        //只能选择自己申请的通过的课
//        $res = [];
//        $user_id = Session::get('user_info.user_id');
//        if($user_id){
//            $map = [
//                'status' => 1,
//                'user_id'=> $user_id
//            ];
//
//            $field = 'id,curriculum_name';
//
//            $res = Db::name('start_curriculum')->alias('a')->where($map)->field($field)->select();
//
//            $cond = [
//                'teacher_id' => $user_id,
//                'status' => 2
//            ];
//            $re = Db::name('corr_teaching')->where($cond)->select();
//        }
//
//        $return = [];
//
//        foreach ($res as $k => $v) {
//            foreach ($re as $kk => $vv) {
//                if($v['id'] == $vv['curriculum_id']){
//                    $return[] = $v;
//                }
//            }
//        }
//
//        jsonReturn('001',$return);
//    }

//    public function listCheckedCourse() {
//        //只能选择自己申请的通过的课
//        $res = [];
//        $user_id = Session::get('user_info.user_id');
//        if($user_id){
//            $map = [
//                'status' => 1,
//                'user_id'=> $user_id
//            ];
//
//            $field = 'id,curriculum_name';
//
//            $res = Db::name('start_curriculum')->where($map)->field($field)->select();
//        }
//
//        jsonReturn('001',$res);
//    }

//    public function listCheckedCourse() {
//        $res = [];
//        $map = [
//            'a.status' => 1,
//            'b.status' => 2
//        ];
//
//        $field = 'a.id,curriculum_name';

//        $res = Db::name('start_curriculum')->where($map)->field($field)->select();
//
//        $cond = [
//            'status' => 2
//        ];
//        $re = Db::name('corr_teaching')->where($cond)->select();
//
//        $return = [];
//
//        if($re){
//            foreach ($res as $k => $v) {
//                foreach ($re as $kk => $vv) {
//                    if($v['id'] == $vv['curriculum_id']){
//                        $return[] = $v;
//                    }
//                }
//            }
//        }else{
//            $return = $res;
//        }

//
//        print_r($res);
//        print_r($re);
//        print_r($return);

//        jsonReturn('001',$return);
//    }


    public function listCheckedCourse() {

        $lab_id = Session::get('user_infocas.labid');  //获取当前进入的实验室id
        
        //$user_id = Session::get('user_info.user_id'); //原来用此值
        $user_id = input()['teacher_id'];//获取选中老师的user_id，后来改用此值
    
        $res = [];

        $map = [
            'status' => 1,
            'flag'   => 0,
            'lab_id' => $lab_id,
            'user_id'=> $user_id
        ];

        $field = 'id,curriculum_name';

        $res = Db::name('start_curriculum')->where($map)->field($field)->select();
      
        if ( $res )
        {
            jsonReturn('001',$res);
        }
        
    }

    /**
     * 根据时间戳在labschedule中检测预约时间是否有效
     * @param $start
     * @param $end
     * @return bool
     */
    protected function checkAppointTimeByTimestamp($start, $end){

        //查找不可预约时间
        $unUse = Db::name('labSchedule')->select();
        foreach ($unUse as $k => $v) {
            if (($start >= strtotime($v['start_time'])) && ($start < strtotime($v['end_time']))) {
                return false;
            } elseif (($start >= strtotime($v['start_time'])) && ($end < strtotime($v['end_time']))) {
                return false;
            } elseif (($end >= strtotime($v['start_time'])) && ($end < strtotime($v['end_time']))) {
                return false;
            }
        }

        return true;
    }

    /**
     * 查看一段时间是否已经被预约，而且已经通过审核
     * @param $datetimeArr
     * @return bool
     */
    protected function checkIsOrdered($datetimeArr) {
        $lab_id = Session::get('user_infocas.labid');  //获取当前进入的实验室id

        foreach ($datetimeArr as $k => $v) {

            $start_time = $v[0];
            $end_time = $v[1];
            //查找预约时间是否已经被占用
            $sql = "select `id` from `nk_order` WHERE `status`=1 AND `lab_id`='" . $lab_id . "' AND ((`start_time`>='" . $start_time . "' AND `start_time`<='" . $end_time . "') OR (`end_time`>='" . $start_time . "' AND `end_time`<='" . $end_time . "') OR (`start_time`<='" . $start_time . "' AND `end_time`>='" . $end_time . "'))";

            $exist = Db::query($sql);
            if($exist){
                return true;
            }
        }

        return false;

    }


    /**
     * 判定传入的日期重复范围是否合理
     * @param $sDate
     * @param $eDate
     * @return bool
     */
    protected function checkDate($sDate, $eDate){
        $sTimestamp = strtotime($sDate);
        $eTimestamp = strtotime($eDate);
        if(($sTimestamp == $eTimestamp) || ($sTimestamp > $eTimestamp)){
            return false;
        }else{
            return true;
        }
    }


    /**
     * 返回一段时间内的时间数组
     * @param $sDate
     * @param $eDate
     * @param $sTime
     * @param $eTime
     * @return array
     */
    protected function getDatetimeArr($sDate, $eDate, $sTime, $eTime) {
        $timeArr = []; //时间数组
        $dateArr = []; //日期数组
        $result = []; //结果数组

        //时间
        $timeArr[0] = $sTime;
        $timeArr[1] = $eTime;

        //日期
        $sDateObj = date_create($sDate);
        $eDateObj = date_create($eDate);
        $diff = date_diff($sDateObj,$eDateObj);
        $diffDay = $diff->d;

        $dateArr[0] = $sDate;
        if($diffDay>1){
            for ($i = 1; $i < $diffDay; $i++) {
                array_push($dateArr,date("Y-m-d",strtotime("+".$i."day",strtotime($sDate))));
            }
        }
        array_push($dateArr,$eDate);

        $dateNum = count($dateArr);
        $timeNum = count($timeArr);

        //循环得到datetime数组
        for ($j = 0; $j<$dateNum; $j++) {
            for ($k = 0; $k<$timeNum; $k++) {
                $result[$j][$k] = $dateArr[$j]." ".$timeArr[$k];
            }
        }

        return $result;
    }


    /**
     * 根据datetimeArr在labschedule中检测预约时间是否有效
     * @param $datetimeArr
     * @return bool
     */
    protected function checkAppointTimeByArr($datetimeArr){
        $dateNum = count($datetimeArr);

        //查找不可预约时间
        $unUse = Db::name('labSchedule')->select();

        for($i = 0; $i<$dateNum; $i++){
            $start = strtotime($datetimeArr[$i][0]);
            $end = strtotime($datetimeArr[$i][1]);
            
            foreach ($unUse as $k => $v) {
                if (($start >= strtotime($v['start_time'])) && ($start < strtotime($v['end_time']))) {
                    return false;
                } elseif (($start >= strtotime($v['start_time'])) && ($end < strtotime($v['end_time']))) {
                    return false;
                } elseif (($end >= strtotime($v['start_time'])) && ($end < strtotime($v['end_time']))) {
                    return false;
                }
            }
        }

        return true;
    }


    /**
     * 教师上课申请数据
     * @param $datetimeArr
     * @param $data
     * @param $user_id
     * @return array
     * @throws Exception
     */
    protected function buildTeacherApplyOrderData($datetimeArr, $data, $user_id){
        $insert = [];
        $arr = [];

        foreach ($datetimeArr as $k => $v) {
            $arr[] = [
                'start_time' => $v[0],
                'end_time'  => $v[1]
            ];
        }


        if((int)$data['pattern'] === 1){
            $xq = [];
            foreach ($data['chk_value'] as $k => $v) {
                switch($v) {
                    case 0:
                        array_push($xq,' 周日');
                        break;
                    case 1:
                        array_push($xq,' 周一');
                        break;
                    case 2:
                        array_push($xq,' 周二');
                        break;
                    case 3:
                        array_push($xq,' 周三');
                        break;
                    case 4:
                        array_push($xq,' 周四');
                        break;
                    case 5:
                        array_push($xq,' 周五');
                        break;
                    case 6:
                        array_push($xq,' 周六');
                        break;
                    default:
                        throw new Exception("服务器出现故障，请联系管理员！");
                        break;
                }
            }

            $week = '';

            for ($i=0;$i<count($xq);$i++) {
                if($i === count($xq)-1){
                    $week .= $xq[$i];
                }else{
                    $week .= $xq[$i] . ',';
                }
            }

            $info = '上课时间为:'.$data['start_date'].'至'.$data['end_date'].'日,'.$week.'的'.$data['starTimeT'].'-'.$data['endTimeT'].'分';
        }else{
            $info = '上课时间为:'.$data['start_date'].'至'.$data['end_date'].'日,'.$data['starTimeT'].'-'.$data['endTimeT'].'分';
        }


        $insert = [
            'college_id'    => $data['department'],
            'curriculum_id' => $data['courseName'],
            'teacher_id'    => $user_id,
            'time_info'     => serialize($arr),
            'info'          => $info,
            'lab_id'        => $data['lab_id'],
            'create_time'   => date('Y-m-d H:i:s'),
            'update_time'   => date('Y-m-d H:i:s')
        ];

        return $insert;
    }

    /**
     * 按周定时获取一段重复范围内的有效日期
     * @param $sDate
     * @param $eDate
     * @param $xq
     * @return array
     */
    protected function getNeedDateByWeek($sDate, $eDate, $xq){
        $dateArr = []; //日期
        $result = []; //结果
        //日期
        $sDateObj = date_create($sDate);
        $eDateObj = date_create($eDate);
        $diff = date_diff($sDateObj,$eDateObj);
        $diffDay = $diff->days;

        $dateArr[0] = $sDate;
        if($diffDay>1){
            for ($i = 1; $i < $diffDay; $i++) {
                array_push($dateArr,date("Y-m-d",strtotime("+".$i."day",strtotime($sDate))));
            }
        }
        array_push($dateArr,$eDate);

        $dateNum = count($dateArr);
        $xqNum = count($xq);
        for ($i = 0; $i < $dateNum; $i++) {
            for ($j =0; $j < $xqNum; $j++) {
                if(date("w",strtotime($dateArr[$i])) == $xq[$j]){
                    $result[] = $dateArr[$i];
                }
            }
        }

        return $result;
    }


    /**
     * 按周定时获取一段重复范围内的有效datetime
     * @param $dateArr
     * @param $sTime
     * @param $eTime
     * @return array
     */
    protected function getDateTimeArrByWeek($dateArr, $sTime, $eTime){
        $result = []; //结果
        $timeArr = []; //时间
        //时间
        $timeArr[0] = $sTime;
        $timeArr[1] = $eTime;

        $dateNum = count($dateArr);
        $timeNum = count($timeArr);

        //循环得到datetime数组
        for ($j = 0; $j<$dateNum; $j++) {
            for ($k = 0; $k<$timeNum; $k++) {
                $result[$j][$k] = $dateArr[$j]." ".$timeArr[$k];
            }
        }

        return $result;
    }

    /**
     * 新增上课申请
     */
    public function teachApply() {
  
        $data = input('post.');

        $user_id = Session::get('user_info.user_id');
        if(!$data['courseName']){
            jsonReturn("002",null,"请选择课程");
            return;
        }
        if(!$data['department']){
            jsonReturn("002",null,"请选择开课院系");
            return;
        }
        if(!$data['lab_id']){
            jsonReturn("002",null,"请选择实验室");
            return;
        }

        if($data['inputOption'] == 'not_cycle'){
            //不设置周期,只有开始和结束时间
            if ($data['start_time'] > $data['end_time']) {
                jsonReturn('002,',null,"预约时间设置不合理");
                return;
            }

            if ($data['start_time'] < time() || $data['end_time'] < time()) {
                jsonReturn('002,',null,"预约时间设置不合理");
                return;
            }
            //根据时间戳判断所选时间是否在开放时间之内
            $isFair = self::checkAppointTimeByTimestamp($data['start_time'], $data['end_time']);

            if(!$isFair){
                jsonReturn('002', null, '预约时间暂不开放！');
                return;
            }

            //构建datetimeArr
            $datetimeArr[0][0] = date("Y-m-d H:i:s", $data['start_time']);
            $datetimeArr[0][1] = date("Y-m-d H:i:s", $data['end_time']);

            //判断时间是否已经被预约并且已经通过审核
            $isOrdered = self::checkIsOrdered($datetimeArr);

            if ($isOrdered) {
                jsonReturn('002', null, '该时间段已被占用！');
            } else {

                $arr[0]['start_time'] = date("Y-m-d H:i:s", $data['start_time']);
                $arr[0]['end_time'] = date("Y-m-d H:i:s", $data['end_time']);
                $time_info = serialize($arr);

                $insert = [
                    'college_id' => $data['department'],
                    'curriculum_id' => $data['courseName'],
                    'lab_id' => $data['lab_id'],
                    'teacher_id' => $user_id,
                    'time_info' => $time_info,
                    'info' => '上课时间为:'.date("Y-m-d H:i:s", $data['start_time']).'至'.date("Y-m-d H:i:s", $data['end_time']),
                    'create_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s')
                ];

                if ( isset($data['apply']) && $data['apply'] == 'again' )//是再次提交上课申请
                {
                    $insert['status'] = 0;
                    $insert['reason'] = '';
                }
                /*判断nk_corr_teaching表里是由已有此curriculum_id的课程记录
                若有则 更新，若无则insert
                */
                $lession_info = Db::name('corr_teaching')->where('curriculum_id', $data['courseName'])->find();
                
                if ( $lession_info )
                {
                    $id = Db::name('corr_teaching')->where('curriculum_id', $data['courseName'])->update($insert);
                    
                    if ($id) {
                        jsonReturn('001', $id, '申请成功,请等待审核！');
                    } else {
                        jsonReturn('001', $data['courseName'], '您重复申请啦！');
                    }
                }else{
                    $id = Db::name('corr_teaching')->insertGetId($insert);

                    if ($id) {
                        jsonReturn('001', $id, '申请成功,请等待审核！');
                    } else {
                        jsonReturn('002', null, '申请失败！');
                    }
                }
                
            }

        }elseif ($data['inputOption'] == 'is_cycle') {
            if((int)$data['pattern'] === 0){
                //开放 设置周期 不按周

                if(strtotime($data['starTimeT']) > strtotime($data['endTimeT'])){
                    jsonReturn("002",null,"您设置的具体时间不合理");
                    return;
                }

                //时间
                $sTime = $data['starTimeT'];
                $eTime = $data['endTimeT'];

                //日期
                $sDate = $data['start_date'];
                $eDate = $data['end_date'];

                //判定传入的日期重复范围是否合理
                $isOK = self::checkDate($sDate, $eDate);
                if(!$isOK){
                    jsonReturn('002,',null,"日期重复范围设置不合理");
                    return;
                }

                //获取日期时间数组
                $datetimeArr = self::getDatetimeArr($sDate, $eDate, $sTime, $eTime);

                //根据datetimeArr判断所选时间是否在开放时间之内
                $isFair = self::checkAppointTimeByArr($datetimeArr);
                if(!$isFair){
                    jsonReturn('002', null, '预约时间暂不开放！');
                    return;
                }

                //判断时间是否已经被预约并且已经通过审核
                $isOrdered = self::checkIsOrdered($datetimeArr);

                if ($isOrdered) {
                    jsonReturn('002', null, '该时间段已被占用！');
                }else{
                    $insert = self::buildTeacherApplyOrderData($datetimeArr, $data, $user_id);

                    if ( isset($data['apply']) && $data['apply'] == 'again' )//是再次提交上课申请
                    {
                        $insert['status'] = 0;
                        $insert['reason'] = '';
                    }
                    /*判断nk_corr_teaching表里是由已有此curriculum_id的课程记录
                    若有则 更新，若无则insert
                    */
                    $lession_info = Db::name('corr_teaching')->where('curriculum_id', $insert['curriculum_id'])->find();

                    if ( $lession_info )
                    {
                        $id = Db::name('corr_teaching')->where('curriculum_id', $insert['curriculum_id'])->update($insert);
                        
                        if ($id) {
                            jsonReturn('001', $id, '申请成功,请等待审核！');
                        } else {
                            jsonReturn('001', $insert['curriculum_id'], '您重复申请啦！');
                        }
                    }else{
                        $id = Db::name('corr_teaching')->insertGetId($insert);

                        if ($id) {
                            jsonReturn('001', $id, '申请成功,请等待审核！');
                        } else {
                            jsonReturn('002', null, '申请失败！');
                        }
                    }
                }
            }elseif ((int)$data['pattern'] === 1){
                //开放，设置周期，按周定时

                if(strtotime($data['starTimeT']) > strtotime($data['endTimeT'])){
                    jsonReturn("002",null,"您设置的具体时间不合理");
                    return;
                }

                //时间
                $sTime = $data['starTimeT'];
                $eTime = $data['endTimeT'];

                //日期
                $sDate = $data['start_date'];
                $eDate = $data['end_date'];

                //判定传入的日期重复范围是否合理
                $isOK = self::checkDate($sDate, $eDate);
                if(!$isOK){
                    jsonReturn('002,',null,"日期重复范围设置不合理");
                    return;
                }

                //星期数组
                $xq = $data['chk_value'];
                if(empty($xq)){
                    jsonReturn('002,',null,"请选择星期安排");
                    return;
                }

                //获取日期数组
                $dateArr = self::getNeedDateByWeek($sDate, $eDate, $xq);

                //按周定时获取一段重复范围内的有效datetime
                $datetimeArr = self::getDateTimeArrByWeek($dateArr, $sTime, $eTime);

                //根据datetimeArr判断所选时间是否在开放时间之内
                $isFair = self::checkAppointTimeByArr($datetimeArr);
                if(!$isFair){
                    jsonReturn('002', null, '预约时间暂不开放！');
                    return;
                }
                
                //判断时间是否已经被预约并且已经通过审核
                $isOrdered = self::checkIsOrdered($datetimeArr);
               
                if ($isOrdered) {
                    jsonReturn('002', null, '该时间段已被占用！');
                }else{
                    $insert = self::buildTeacherApplyOrderData($datetimeArr, $data, $user_id);

                    if ( isset($data['apply']) && $data['apply'] == 'again' )//是再次提交上课申请
                    {
                        $insert['status'] = 0;
                        $insert['reason'] = '';
                    }
                    /*判断nk_corr_teaching表里是由已有此curriculum_id的课程记录
                    若有则 更新，若无则insert
                    */
                    $lession_info = Db::name('corr_teaching')->where('curriculum_id', $insert['curriculum_id'])->find();

                    if ( $lession_info )
                    {
                        $id = Db::name('corr_teaching')->where('curriculum_id', $insert['curriculum_id'])->update($insert);
                        
                        if ($id) {
                            jsonReturn('001', $id, '申请成功,请等待审核！');
                        } else {
                            jsonReturn('001', $insert['curriculum_id'], '您重复申请啦！');
                        }
                    }else{
                        $id = Db::name('corr_teaching')->insertGetId($insert);

                        if ($id) {
                            jsonReturn('001', $id, '申请成功,请等待审核！');
                        } else {
                            jsonReturn('002', null, '申请失败！');
                        }
                    }
                }
            }
        }
    }//teachApply 结束

    /**
     * 上课申请审批列表
     */
    public function teachApplyList()
    {
        $status = input('status',0);
        $lab_id = Session::get('user_infocas.labid');  //获取当前进入的实验室id

        $fields = 'a.id,a.status,d.name as user_name,c.college_name,b.curriculum_name,b.curriculum_num';

        if(empty($lab_id)){
            $map = [
                'a.status' => $status,
                'a.order_id' => 0,
            ];
        }else{
            $map = [
                'a.status' => $status,
                'a.order_id' => 0,
                'a.lab_id' => $lab_id
            ];
        }
        $res = [];
        $res = Db::name('corr_teaching')
            ->alias('a')
            ->field($fields)
            ->join('nk_start_curriculum b','a.curriculum_id=b.id')
            ->join('nk_base_college c','a.college_id=c.id')
            ->join('nk_user d','d.id=a.teacher_id')
            ->where($map)
            // ->where('a.checker_id','null')
            ->select();

        jsonReturn('001',$res);
    }

    /**
     * 上课申请详情
     */
    public function teachApplyInfo(){
        $id = input('post.id');
        $lab_id = Session::get('user_infocas.labid');  //获取当前进入的实验室id
        $current = []; //这是当前查询的那一条记录的数据

        $fields = 'a.id,a.status,a.info,a.time_info,d.name as user_name,c.college_name,b.curriculum_name,b.curriculum_num';

        $map = [
            'a.id' => $id,
            'a.lab_id' => $lab_id,
        ];

        $current = Db::name('corr_teaching')
            ->alias('a')
            ->field($fields)
            ->join('nk_start_curriculum b','a.curriculum_id=b.id')
            ->join('nk_base_college c','a.college_id=c.id')
            ->join('nk_user d','d.id=a.teacher_id')
            ->where($map)
            ->select();
           
        if($current){
            foreach ($current as $k => $v) {
                $current[$k]['time_info'] = unserialize($v['time_info']);
            }
        }

        //这是设为不开放的数据
        $temp = [];
        $map = [
            'status' => 0
        ];
        $unUse = Db::name('labSchedule')->where($map)->select();

        if(!empty($unUse)){
            $need = "00:00:00";

            foreach ($unUse as $k => $v) {
                $posS = strpos($v['start_time'],$need);
                $posE = strpos($v['end_time'],$need);
                if($posS && $posE){
                    $temp['whole'][] = $v;
                }else{
                    $temp['half'][] = $v;
                }
            }
        }
        
        //这是order中已经审核通过的数据
        $data = [];
        if($current[0]['status'] == 1){
            $cond = [
                'a.status' => 1,
                'a.corr_id'=> array('neq',$id),
                'a.lab_id' => $lab_id
            ];
        }else{
            $cond = [
                'a.status' => 1,
                'a.lab_id' => $lab_id
            ];
        }

        $field = 'a.start_time,a.end_time,b.curriculum_name,c.name as teacher_name';
        $data = Db::name('order')
            ->alias('a')
            ->join('nk_start_curriculum b','b.id=a.curriculum_id')
            ->join('nk_user c','c.id=a.teacher_id')
            ->where($cond)
            ->field($field)
            ->select();

        $res['unuse'] = $temp;
        $res['data'] = $data;
        $res['current'] = $current;
        jsonReturn('001',$res);

    }

    /*被驳回的使用申请日程信息
    */
    public function reject_use_info ()
    {
        $id = input('post.id');
        $lab_id = Session::get('user_infocas.labid');  //获取当前进入的实验室id
        $current = []; //这是当前查询的那一条记录的数据

        $fields = 'a.id,a.status,a.info,a.time_info,d.name as user_name,c.college_name,b.curriculum_name,b.curriculum_num';

        $map = [
            'a.id' => $id,
            'a.lab_id' => $lab_id,
        ];

        $current = Db::name('corr_teaching')
            ->alias('a')
            ->field($fields)
            ->join('nk_start_curriculum b','a.curriculum_id=b.id')
            ->join('nk_base_college c','a.college_id=c.id')
            ->join('nk_user d','d.id=a.teacher_id')
            ->where($map)
            ->select();
           
        if($current){
            foreach ($current as $k => $v) {
                $current[$k]['time_info'] = unserialize($v['time_info']);
            }
        }

        $res['current'] = $current;
        jsonReturn('001',$res);
    }
    /**
     * 删除上课申请信息
     */
    public function teachRemove()
    {
        $id = input('post.id');

        $map = [
            'id' =>  $id
        ];
        //查询课程id
        $curr_id = Db::name('corr_teaching')->where($map)->value('curriculum_id');
        Db::startTrans();
        try{
            $del= Db::name('corr_teaching')->where($map)->delete();
            Db::name('order')->where('corr_id',$id)->delete();

            Db::name('start_curriculum')->where('id',$curr_id)->update(['flag' => 0]);
            Db::commit();
            jsonReturn('001',$del,"删除成功！");
        }catch (Exception $e) {
            Db::rollback();
            jsonReturn('002',null,"删除失败！");
        }
    }

    /**
     * 使用申请 审核
     */
    public function teachCheck()
    {
        $d = input();

        $id = $d['id'];
        $status = $d['status'];

        $lab_id = Session::get('user_infocas.labid');

        //查找到当前记录
        $data = Db::name('corr_teaching')->where('id',$id)->find();

        if($data){
            $time_info = unserialize($data['time_info']);
        }
        
        //判断当前处于审核通过还是驳回状态
        if($status == '1'){
            //将预约日期处理成可以查询的格式
            $timeCheck = [];
            foreach ($time_info as $k => $v){
                $timeCheck[$k][0] = $v['start_time'];
                $timeCheck[$k][1] = $v['end_time'];
            }


            $isOrdered = self::checkIsOrdered($timeCheck);
            if($isOrdered){
                jsonReturn('002', null, '该时间段已被占用！');
            }else{
                //组织插入数据
                $size = count($time_info);
                $insert = [];
                for ($i = 0; $i<$size; $i++) {
                    $insert[] = [
                        'corr_id'       =>  $id,
                        'teacher_id'    =>  $data['teacher_id'],
                        'curriculum_id' =>  $data['curriculum_id'],
                        'college_id'    =>  $data['college_id'],
                        'status'        =>  $status,
                        'lab_id'        =>  $lab_id,
                        'start_time'    =>  $time_info[$i]['start_time'],
                        'end_time'      =>  $time_info[$i]['end_time'],
                        'create_time'   =>  date("Y-m-d H:i:s"),
                        'update_time'   =>  date("Y-m-d H:i:s")
                    ];
                }

                //更新并插入
                $update = [
                    'status'  => $status,
                    'checker_id'    => Session::get('user_info.user_id'),
                    'check_time'    => date("Y-m-d H:i:s",time())
                ];
                DB::startTrans();
                try{
                    Db::name('corr_teaching')->where('id',$id)->update($update);
                    //Db::name('start_curriculum')->where('id',$data['curriculum_id'])->update(['flag' => 1]);//不更改flag字段 否则上课申请的课程名称下拉框不显示此课程
                    $num = Db::name('order')->insertAll($insert);
                    Db::commit();
                    jsonReturn('001', $num, "审核完成！");
                }catch (Exception $e) {
                    Db::rollback();
                    jsonReturn("002",null,'审核失败！');
                }
            }
        }else{//被驳回
            //更新
            $update = [
                'status'        => $status,
                'checker_id'    => Session::get('user_info.user_id'),
                'check_time'    => date("Y-m-d H:i:s",time()),
                'reason'        => $d['reason'],
            ];
       
            DB::startTrans();
            try{
                Db::name('corr_teaching')->where('id',$id)->update($update);
                Db::name('start_curriculum')->where('id',$data['curriculum_id'])->update(['flag' => 0]);
                //$num = Db::name('order')->insertAll($insert); //被驳回时无须此操作
                Db::commit();
                jsonReturn('001', $num=1, "审核完成！");
            }catch (Exception $e) {
                Db::rollback();
                jsonReturn("002",null,'审核失败！');
            }
        }       
    }

    /**
     * 重新提交 上课申请
     */
    /*public function apply_again ()
    {
        halt('again');
    }*/

    /**
     * 我的课程已通过课程
     */
    public function teachApplyCheckedList()
    {
        $res = [];
        $user_id = Session::get('user_info.user_id');
        $lab_id = Session::get('user_infocas.labid');  //获取实验室id
        if($user_id){
            $fields = 'a.id,a.curriculum_id,a.status,d.name as user_name,c.college_name,b.curriculum_name,b.curriculum_num';
            if(empty($lab_id)){
                $map = [
                    'a.status'     => 1,
                    'a.teacher_id' => $user_id,
                ]; 
            }else{
                $map = [
                    'a.status'     => 1,
                    'a.teacher_id' => $user_id,
                    'a.lab_id'     => $lab_id
                ]; 
            }

            $res = Db::name('corr_teaching')
                ->alias('a')
                ->field($fields)
                ->join('nk_start_curriculum b','a.curriculum_id=b.id')
                ->join('nk_base_college c','a.college_id=c.id')
                ->join('nk_user d','a.teacher_id=d.id')
                ->where($map)
                ->where('a.checker_id','not null')
                ->select();

        }
        jsonReturn('001',$res);
    }


     /**
     * 用于修改教学日历中使用到的课程
     */
    public function teachSchoolCheckedList()
    {
       /* $res = [];
        $lab_id = Session::get('user_infocas.labid');  //获取实验室id

        $fields = 'a.id,a.curriculum_id,a.status,c.college_name,b.curriculum_name,b.curriculum_num';
        if(empty($lab_id)){
            $map = [
                'a.status'     => 1,
            ]; 
        }else{
            $map = [
                'a.status'     => 1,
                'a.lab_id'     => $lab_id
            ]; 
        }

        $res = Db::name('corr_teaching')
            ->alias('a')
            ->field($fields)
            ->join('nk_start_curriculum b','a.curriculum_id=b.id')
            ->join('nk_base_college c','a.college_id=c.id')
            ->where($map)
            ->where('a.checker_id','not null')
            ->select();

        jsonReturn('001',$res);*/

        $res = [];
        $lab_id = Session::get('user_infocas.labid');  //获取实验室id

        $fields = 'a.id,a.curriculum_id,a.status,c.college_name,b.curriculum_name,b.curriculum_num, u.name';
        if(empty($lab_id)){
            $map = [
                'a.status'     => 1,
            ]; 
        }else{
            $map = [
                'a.status'     => 1,
                'a.lab_id'     => $lab_id
            ]; 
        }

        $res = Db::name('corr_teaching')
            ->alias('a')
            ->field($fields)
            ->join('nk_start_curriculum b', 'a.curriculum_id=b.id')
            ->join('nk_base_college c', 'a.college_id=c.id')
            ->join('nk_user u', 'b.user_id = u.id')
            ->where($map)
            ->where('a.checker_id','not null')
            ->select();

        jsonReturn('001',$res);
        
    }




    /**
     * 我的课程待审核课程
     */
    public function teachApplyWaitCheckList()
    {
        $fields = 'a.id,a.curriculum_id,a.status,d.name as user_name,c.college_name,b.curriculum_name,b.curriculum_num';

        $user_id = Session::get('user_info.user_id');
        $lab_id = Session::get('user_infocas.labid');  //获取实验室id

        if(empty($lab_id)){
            $map = [
                'a.status'     => 0,
                'a.teacher_id' => $user_id
            ];
        }else{
            $map = [
                'a.status'     => 0,
                'a.teacher_id' => $user_id,
                'a.lab_id'     => $lab_id
            ];
        }
        
        $res = [];
        $res = Db::name('corr_teaching')
            ->alias('a')
            ->field($fields)
            ->join('nk_start_curriculum b','a.curriculum_id=b.id')
            ->join('nk_base_college c','a.college_id=c.id')
            ->join('nk_user d','a.teacher_id=d.id')
            ->where($map)
            ->select();

        jsonReturn('001',$res);
    }

    /**
     * 我的课程审核未通过课程
     */
    public function teachApplyUnAccessCheckList()
    {
        $fields = 'a.id, a.lab_id, a.college_id, a.reason, a.curriculum_id, a.status,d.name as user_name,c.college_name,b.curriculum_name,b.curriculum_num';

        $user_id = Session::get('user_info.user_id');
        $lab_id = Session::get('user_infocas.labid');  //获取实验室id

        if(empty($lab_id)){
            $map = [
                'a.status'     => 2,
                'a.teacher_id' => $user_id
            ];
        }else{
            $map = [
                'a.status'     => 2,
                'a.teacher_id' => $user_id,
                'a.lab_id'     => $lab_id
            ]; 
        }
        
        $res = [];
        $res = Db::name('corr_teaching')
            ->alias('a')
            ->field($fields)
            ->join('nk_start_curriculum b','a.curriculum_id=b.id')
            ->join('nk_base_college c','a.college_id=c.id')
            ->join('nk_user d','a.teacher_id=d.id')
            ->where($map)
            ->select();
        //$res['lab_id'] = $lab_id;

        jsonReturn('001',$res);
    }
    //-------------------------------------------------课程管理---------------------------------------------------------//

    /**
     * 课程介绍显示
     */
    public function courseIntro()
    {
        $curriculum_id = input('post.curriculum_id');

        $map = [
            'id' => $curriculum_id
        ];
        $field = 'curriculum_rec,curriculum_name';
        $res = [];
        $res = Db::name('start_curriculum')->where($map)->field($field)->find();
        if($res){
            $res['curriculum_rec'] = html_entity_decode($res['curriculum_rec']);
        }

        jsonReturn("001",$res);
    }

    /**
     * 课程介绍修改
     */
    public function editCourseIntro()
    {
        $data = input('post.');

        $map = [
            'id' => $data['curriculum_id']
        ];

        $update = [
            'curriculum_rec' => $data['curriculum_rec']
        ];

        $res = Db::name('start_curriculum')->where($map)->update($update);

        if($res){
            jsonReturn("001",$res,'修改成功！');
        }else{
            jsonReturn("002",null,'修改失败！');
        }

    }

    /**
     * 教学大纲显示
     */
    public function courseGuide()
    {
        $curriculum_id = input('post.curriculum_id');
        $map = [
            'id' => $curriculum_id
        ];
        $field = 'curriculum_guide,curriculum_name';
        $res = [];
        $res = Db::name('start_curriculum')->where($map)->field($field)->find();
        if($res){
            $res['curriculum_guide'] = html_entity_decode($res['curriculum_guide']);
        }
        jsonReturn("001",$res);
    }

    /**
     * 教学大纲介绍
     */
    public function editCourseGuide()
    {
        $data = input('post.');

        $map = [
            'id' => $data['curriculum_id']
        ];

        $update = [
            'curriculum_guide' => $data['curriculum_guide']
        ];

        $res = Db::name('start_curriculum')->where($map)->update($update);

        if($res){
            jsonReturn("001",$res,'修改成功！');
        }else{
            jsonReturn("002",null,'修改失败！');
        }
    }


    /**
     * 教学日历显示
     */
    public function teachCalendar(){
        $curriculum_id = input('post.curriculum_id');
        $temp = [];
        $res = [];
        $map = [
            'status' => 0
        ];
        $unUse = Db::name('labSchedule')->where($map)->select();

        if(!empty($unUse)){
            $need = "00:00:00";

            foreach ($unUse as $k => $v) {
                $posS = strpos($v['start_time'],$need);
                $posE = strpos($v['end_time'],$need);
                if($posS && $posE){
                    $temp['whole'][] = $v;
                }else{
                    $temp['half'][] = $v;
                }
            }
        }

        $user_id = Session::get('user_info.user_id');

        $cond = [
            'a.status'        => 1,
            'a.teacher_id'    => $user_id,
            'a.curriculum_id' => $curriculum_id
        ];
        $field = 'a.start_time,a.end_time,b.curriculum_name,c.name as teacher_name';
        $data = Db::name('order')
            ->alias('a')
            ->join('nk_start_curriculum b','b.id=a.curriculum_id')
            ->join('nk_user c','c.id=a.teacher_id')
            ->where($cond)
            ->field($field)
            ->select();

        $res['unuse'] = $temp;
        $res['data'] = $data;


        $arr = Db::name('start_curriculum')->where('id',$curriculum_id)->find();
        if($arr){
            $res['curriculum_name'] = $arr['curriculum_name'];
        }

        jsonReturn('001',$res);
    }

    /**
     * 教学日历修改
     */
//    public function editTeachCalendar(){
//        $data = input('post.');
//
//        $map = [
//            'id' => $data['curriculum_id']
//        ];
//
//        $update = [
//            'teach_calendar' => $data['teach_calendar']
//        ];
//
//        $res = Db::name('start_curriculum')->where($map)->update($update);
//
//        if($res){
//            jsonReturn("001",$res,'修改成功！');
//        }else{
//            jsonReturn("002",null,'修改失败！');
//        }
//    }


    /**
     * 上传教学资源
     */
    public function upTeachSource(){
        $data = str_replace(PHP_EOL, '', input('post.'));
        $curriculum_id = $data['curriculum_id'];
        $ext = substr($_FILES['file']['name'],strpos($_FILES['file']['name'],'.'));
        $allow_size = 512000;

        if($_FILES['file']['size'] > $allow_size){
            jsonReturn('002',null,"文件过大，上传失败！");
            return;
        }

        $name = $data['resourceName'];
        $rec_type = $data['type'];
        $describe = $data['remark'];
        $userid = Session::get('user_info.user_id');

        $rand = rand(1,999999);
        $to = time().$rand;
        $filename = md5($to).$ext;
        $dir = date("Y-m-d",time());

        //查询资源名称是否已经存在
        $rets =  Db::name('teachResource')->where('name',$name)->select();
        if($rets){
            jsonReturn('002',null,"资源名称已经存在！");
        }else{
            //判断资源类型
            switch ($rec_type){
                case '文档':
                    if(!in_array($ext,['.doc','.docx','.pdf','.txt'])){
                        jsonReturn('002',null,'资源类型与上传文件不符！');
                        return;
                    }else{
                        $store_path = "uploads/document/".$dir;
                    }
                    break;
                case '图片':
                    if(!in_array($ext,['.jpg','.png','.jpeg'])){
                        jsonReturn('002',null,'资源类型与上传文件不符！');
                        return;
                    }else{
                        $store_path = "uploads/images/".$dir;
                    }
                    break;
                case '视频':
                    if(!in_array($ext,['.mp4'])){
                        jsonReturn('002',null,'资源类型与上传文件不符！');
                        return;
                    }else{
                        $store_path = "uploads/video/".$dir;
                    }
                    break;
                case '压缩文件':
                    if(!in_array($ext,['.zip'])){
                        jsonReturn('002',null,'资源类型与上传文件不符！');
                        return;
                    }else{
                        $store_path = "uploads/static/".$dir;
                    }
                    break;
                case '可执行文件':
                    if(!in_array($ext,['.exe'])){
                        jsonReturn('002',null,'资源类型与上传文件不符！');
                        return;
                    }else{
                        $store_path = "uploads/software/".$dir;
                    }
                    break;
                default:
                    jsonReturn('002',null,'资源类型与上传文件不符！');
                    return;
                    break;
            }

            if(!is_dir($store_path)){
                mkdir($store_path,0777);
            }

            $store_name = $store_path . '/' . $filename;

            if(move_uploaded_file($_FILES['file']['tmp_name'],$store_name)){
                $add = [
                    'curriculum_id' => $curriculum_id,
                    'name'          => $name,
                    'user_id'       => $userid,
                    'type'          => $rec_type,
                    'url'           => $store_name,
                    'time'          => date("Y-m-d H:i:s"),
                    'describe'      => $describe,
                ];
                $userId = Db::name('teachResource')->insertGetId($add);

                if($userId){
                    jsonReturn("001",$userId,"上传成功！");
                }else{
                    @unlink($store_name);
                    jsonReturn("002",null,"文件保存失败，请重试！");
                }

            }else{
                jsonReturn("002",null,"文件信息有误，上传失败！");
            }
        }


    }


    /**
     * 课程管理教学资源显示
     */
    public function listTeachSource(){
        $curriculum_id = input('post.curriculum_id',1);
        $map = [
            'a.curriculum_id' => $curriculum_id
        ];
        $field = 'a.id,name,type,time,url,curriculum_name';
        $res = [];
        $res = Db::name('teach_resource')
            ->alias('a')
            ->join('nk_start_curriculum b','b.id=a.curriculum_id')
            ->where($map)
            ->field($field)
            ->select();

        if($res){
            foreach ($res as $k => $v) {
                $ext = substr($v['url'], strpos($v['url'],'.'));
                $res[$k]['downloadName'] = $v['name'] . $ext;
            }
        }

        jsonReturn("001",$res);
    }

    /**
     * 删除教学资源
     */
    public function deleteTeachSource()
    {
        $id = input('post.id');

        $map = [
            'id'    =>  $id
        ];
        $data = Db::name('teach_resource')->where($map)->find();
        $image_path = $data['url'];

        $del= Db::name('teach_resource')->where($map)->delete();

        if($del){
            @unlink($image_path);
            jsonReturn('001',$del,"删除成功！");
        }else{
            jsonReturn('002',null,"删除失败！");
        }
    }

    /**
     * 课程介绍，----简介
     */
    public function getInfo(){
        $res = [];
        $id = input('post.id');
        $curriculum_id = input('post.ids');
        $start = Db::name('start_curriculum')->where('id',$curriculum_id)->field('user_id,check_time,checker_id,create_time')
            ->find();
        $create_time = $start['create_time']; //开课申请时间
        $check_time = $start['check_time']; //开课审批时间

        $create_user = Db::name('user')->where('id',$start['user_id'])->field('name')->find(); //开课人
        $checker = Db::name('user')->where('id',$start['checker_id'])->field('name')->find(); //开课审批人


        $corr = Db::name('corr_teaching')->where('id',$id)->field('teacher_id,checker_id,check_time,create_time')->find();

        $corr_create_time = $corr['create_time']; //上课申请时间
        $corr_check_time = $corr['check_time']; //使用申请时间

        $corr_create_user = Db::name('user')->where('id',$corr['teacher_id'])->field('name')->find(); //上课人
        $corr_checker = Db::name('user')->where('id',$corr['checker_id'])->field('name')->find(); //上课审批人


        $res = [
            'start_time' => $create_time,
            'start_man'  => $create_user['name'],
            'start_check_time'  =>  $check_time,
            'start_checker'  =>  $checker['name'],
            'teach_time' => $corr_create_time,
            'teach_man'  => $corr_create_user['name'],
            'teach_check_time'  =>  $corr_check_time,
            'teach_checker'  =>  $corr_checker['name']
        ];

        jsonReturn('001',$res);
    }

    //-------------------------------------------------校历选择课程---------------------------------------------------------//
    /**
     * 校历课程
     */
    public function getCalendarCourse(){

        $res = [];
        $user_id = input('post.teacher_id');
        if($user_id){
            $map = [
                'status' => 1,
                'user_id'=> $user_id
            ];

            $field = 'id,curriculum_name';

            $res = Db::name('start_curriculum')->where($map)->field($field)->select();
        }

        jsonReturn('001',$res);

    }
}