<?php
/**
 * Created by PhpStorm.
 * User: 高笛淳
 * Date: 2017/12/21
 * Time: 9:56
 * 校历
 */

namespace app\index\controller;

use app\index\model\CalendarInfo;
use think\Controller;
use think\Db;
use think\Exception;
use think\Session;
use app\index\model\CalendarInfo as CalendarInfoModel;
class SchoolCalendar extends Controller
{
    //---------------------------------------------校历---------------------------------------------------//
    /**
     * 创建校历
     */
    public function createCalendar()
    {
        $data = input('post.');

        $insert = [
            'name' => $data['name'],
            'range_time' => date("Y-m-d",$data['starTime'])." ".date("Y-m-d",$data['endTime']),
            'remark' => $data['remarks'],
            'create_time' => date("Y-m-d H:i:s"),
            'update_time' => date("Y-m-d H:i:s")
        ];

        $id = Db::name('calendar')->insertGetId($insert);
        if($id){
            jsonReturn('001',$id,'校历创建成功！');
        }else{
            jsonReturn('002',null,'校历创建失败！');
        }
    }

    /**
     * 校历列表
     */
    public function getCalendarList(){
        $res = Db::name('calendar')->select();
        jsonReturn("001",$res);
    }

    /**
     * 校历列表-删除校历信息
     */
    public function DelCalendarList(){
        $id = input('post.id');

        //删除校历表和校历详情表中的信息
        Db::startTrans();
        try{
            Db::name('calendar')->where('id',$id)->delete();
            Db::name('calendar_info')->where('corr_calendar_id',$id)->delete();
            Db::commit();
            jsonReturn('001', null, '删除成功！');
        }catch (Exception $e) {
            Db::rollback();
            jsonReturn('002', null, '删除失败！');
        }

    }

    /**
     * 管理员点击日历禁用的日程时，显示此条禁用数据
     */
    public function show_not_open ()
    {
        $d = input();
        if ( $d['sched_id'] )
        {
            $res = Db::table('nk_lab_schedule')->where('sched_id', $d['sched_id'])
                    ->where('status', 0)->select();
            
            if ( $res )
            {
                return json( ['data'=>$res,'code'=>'001'] );
            }else{
                return json( ['msg'=>'未查到数据','code'=>'002'] );
            }
            
        }
    }

    /*
    解除禁用 即删除禁用的数据记录
    */

    public function open_lab ()
    {
        $d = input();

        // 启动事务
        Db::startTrans();

        try{
            Db::table('nk_lab_schedule')->where('sched_id', $d['sched_id'])->delete();
            Db::commit();
            return json( ['msg'=>'解除OK', 'code'=>'001'] );
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json( ['msg'=>'解除失败', 'code'=>'002'] );
        }

    }

    /**
     * 校历时间范围
     */
    public function getCalendarRangeTime(){
        $time = [];
        $id = input('post.schoolYear_id');
        $map = [
            'id' => $id
        ];
        $res = Db::name('calendar')->where($map)->find();
        if($res){
            $tmp = explode(" ",$res['range_time']);
            $time['start'] = $tmp[0];
            $time['end'] = $tmp[1];
        }
        jsonReturn("001",$time);
    }

    /**
     * 设置校历详情信息
     */
    public function setCalendarInfo(){
        $corr_id = input('schoolYear');
        $data = input('post.data/a');

        $insert = [];
        if($data){
            foreach ($data as $k => $v) {
                $insert[] = [
                    'corr_calendar_id' => $corr_id,
                    'term_name' => $v['Term_name'],
                    'term_start_time' => date("Y-m-d",$v['Term_startTime']),
                    'term_end_time' => date("Y-m-d",$v['Term_endTime']),
                    'term_remark' => $v['Term_remark'],
                    'event' => $v['Event']?serialize($v['Event']):$v['Event']
                ];
            }

            $num = Db::name('calendar_info')->insertAll($insert);
            if($num){
                jsonReturn("001",$num,"设置成功！");
            }else{
                jsonReturn("002",null,"设置失败！");
            }
        }else{
            jsonReturn("002",null,"数据信息不存在！");
        }
    }

    /**
     * 修改校历查询详情
     */
    public function getOneCalendarInfo(){
        $id = input('post.schoolYear_id',1);
        $result = [];
        $time = [];
        $where = [
            'id' => $id
        ];
        $re = Db::name('calendar')->where($where)->find();
        if($re){
            $tmp = explode(" ",$re['range_time']);
            $time['start'] = $tmp[0];
            $time['end'] = $tmp[1];
        }

        $result['calendar_id'] = $re['id'];

        $map = [
            'corr_calendar_id' => $id
        ];
        $res = Db::name('calendar_info')->where($map)->select();
        if($res){
            foreach ($res as $k => $v) {
                $res[$k]['event'] = unserialize($v['event']);
            }
            $result['time'] = $time;
        }
        $result['info'] = $res;
        jsonReturn("001",$result);
    }

    /**
     * 当前校历
     */
    public function getCurrentCalendarInfo(){
        $id = '';
        $re = [];
        $cal = Db::name('calendar')->select();
        if($cal){
            foreach ($cal as $k => $v){
                $tmp = explode(' ', $v['range_time']);
                $current = time();
                if(($current> strtotime($tmp[0])) && ($current<strtotime($tmp[1]))){
                    $id = $v['id'];

                    $map = [
                        'corr_calendar_id' => $id
                    ];
                    $res = Db::name('calendar_info')->where($map)->select();
                    if($res){
                        foreach ($res as $kk => $vv) {
                            $res[$kk]['event'] = unserialize($vv['event']);
                        }
                    }
                    $re['data'] = $cal[$k];
                    $re['info'] = $res;
                    jsonReturn("001",$re);
                }
            }
        }
    }

    /**
     * 修改校历信息
     */
    public function modifyCalendarInfo(){
        $data = input("post.");
        $corr_id = $data['schoolYear'];
        $wait = [];

        foreach ($data['data'] as $k => $v){
            if(array_key_exists('id',$v) ){
                $wait[] = [
                    'id' => $v['id'],
                    'corr_calendar_id' => $corr_id,
                    'term_name' => $v['Term_name'],
                    'term_start_time' => date("Y-m-d",$v['Term_startTime']),
                    'term_end_time' => date("Y-m-d",$v['Term_endTime']),
                    'term_remark' => $v['Term_remark'],
                    'event' => $v['Event']?serialize($v['Event']):$v['Event']
                ];
            }else{
                $wait[] = [
                    'corr_calendar_id' => $corr_id,
                    'term_name' => $v['Term_name'],
                    'term_start_time' => date("Y-m-d",$v['Term_startTime']),
                    'term_end_time' => date("Y-m-d",$v['Term_endTime']),
                    'term_remark' => $v['Term_remark'],
                    'event' => $v['Event']?serialize($v['Event']):$v['Event']
                ];
            }
        }
        $model = new CalendarInfoModel();
        try{
            $model->saveAll($wait);
            jsonReturn("001",null,"修改成功");
        }catch (Exception $e){
            jsonReturn("002",null,"修改失败");
        }
    }
    //-------------------------------------------日程安排--------------------------------------------------------//
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
     * 判定时间是否在lab_schedule表中设置为不开放
     * @param $start_time
     * @param $end_time
     * @return bool
     */
    protected function isExist($start_time,$end_time){
        $lab_id = Session::get('user_infocas.labid');  //实验室id
        $sql = "select `id` from `nk_lab_schedule` WHERE `status`=0 AND `lab_id`='" . $lab_id . "' AND ((`start_time`>='" . $start_time . "' AND `start_time`<='" . $end_time . "') OR (`end_time`>='" . $start_time . "' AND `end_time`<='" . $end_time . "') OR (`start_time`<='" . $start_time . "' AND `end_time`>='" . $end_time . "'))";
        
        $is_exist = Db::query($sql);
        if($is_exist){
            return true;
        }
        return false;
    }

    /**
     * 将数据插入到labschedule中
     * @param $data
     * @return bool
     */
    protected function insertLabSchedule($data){

        $lab_id = Session::get('user_infocas.labid');  //实验室id
        //每次插入数据时 生成一个唯一的值作为sched_id，区分每次设置的禁用数据
        $sched_id = time() . mt_rand(1,1000000);

        foreach ($data as $k => $v) {
            $insert[] = [
                'start_time' => $v['start_time'],
                'end_time'  => $v['end_time'],
                'status'    => $v['status'],
                'lab_id'    => $lab_id,
                'sched_id'  => $sched_id,
            ];
        }
        
        $num = Db::name('labSchedule')->insertAll($insert);

        if($num){
            return $num;
        }
        return false;
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
        $diffDay = $diff->d;

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
     * 组装labschedule表插入数据
     * @param $datetimeArr
     * @param $status
     * @param $remark
     * @return array|string
     */
    protected function returnLabscheduleData($datetimeArr,$status,$remark) {
        //插入labschedule数据
        $tempData = [];
        foreach ($datetimeArr as $k => $v) {
            $start_time = $v[0];
            $end_time = $v[1];
            $is_exist = self::isExist($start_time,$end_time);
            if($is_exist){
                return "exist";
            }else{
                $tem = [
                    'status'     => $status,
                    'start_time' => $v[0],
                    'end_time'   => $v[1],
                    'remark'    =>  $remark
                ];
            }
            $tempData[] = $tem;
        }

        return $tempData;
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
     * 查看一段时间是否已经被预约，而且已经通过审核
     * @param $datetimeArr
     * @return bool
     */
    protected function checkIsOrdered($datetimeArr, $lab_id=0) {

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
     * 构建order表数据
     * @param $datetimeArr
     * @param $data
     * @return array
     * @throws Exception
     */
    protected function buildOrderData($datetimeArr, $data){
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
            'curriculum_id' => $data['curriculum_id'],
            'teacher_id'    => $data['teacher_id'],
            'time_info'     => serialize($arr),
            'info'          => $info,
            'create_time'   => date('Y-m-d H:i:s'),
            'update_time'   => date('Y-m-d H:i:s')
        ];

        return $insert;
    }

    /**
     * 教师日历构建order表数据
     * @param $datetimeArr
     * @param $data
     * @param $user_id
     * @return array
     */
//    protected function buildTeacherOrderData($datetimeArr, $data, $user_id){
//        $insert = [];
//        foreach ($datetimeArr as $k => $v) {
//            $insert[] = [
//                'college_id'    => $data['department'],
//                'curriculum_id' => $data['curriculum_id'],
//                'teacher_id'    => $user_id,
//                'start_time'    => $v['0'],
//                'end_time'      => $v['1'],
//                'create_time'   => date('Y-m-d H:i:s'),
//                'update_time'   => date('Y-m-d H:i:s')
//            ];
//        }
//
//        return $insert;
//    }

    /**
     * 教师上课申请数据
     * @param $datetimeArr
     * @param $data
     * @param $user_id
     * @return array
     */
//    protected function buildTeacherApplyOrderData($datetimeArr, $data, $user_id){
//        $insert = [];
//        foreach ($datetimeArr as $k => $v) {
//            $insert[] = [
//                'college_id'    => $data['department'],
//                'curriculum_id' => $data['courseName'],
//                'teacher_id'    => $user_id,
//                'start_time'    => $v['0'],
//                'end_time'      => $v['1'],
//                'create_time'   => date('Y-m-d H:i:s'),
//                'update_time'   => date('Y-m-d H:i:s')
//            ];
//        }
//
//        return $insert;
//    }

    /**
     * 实验室开放情况管理
     */
    public function manageSchedule()
    {
        //status 实验室开放状态 0不开放 1开放
        //pattern 定时模式 0无，1按周
        $data = input('post.');
        //halt($data);
        //实验室不开放
        if((int)$data['status'] === 0){
            if($data['inputOption'] == 'not_cycle'){
                //不设置周期
                $temp = 0;

                if($data['start_time'] > $data['end_time']){
                    $temp = $data['start_time'];
                    $data['start_time'] = $data['end_time'];
                    $data['end_time'] = $temp;
                }

                $start_time = date("Y-m-d H:i:s",$data['start_time']);
                $end_time = date("Y-m-d H:i:s",$data['end_time']);

                $is_exist = self::isExist($start_time,$end_time);
                if($is_exist){
                    jsonReturn('002', $is_exist, '部分时间段已经设置！');
                }else{
                    $insertData[] = [
                        'status'     => $data['status'],
                        'start_time' => $start_time,
                        'end_time'   => $end_time,
                        'remark'    =>  $data['remark']
                    ];

                    $num = self::insertLabSchedule($insertData);
                    if($num){
                        jsonReturn('001',$num,'设置成功！');
                    }else{
                        jsonReturn('002',null,'设置失败！');
                    }
                }
            }elseif ($data['inputOption'] == 'is_cycle') {
                //设置周期
                if((int)$data['pattern'] === 0){
                    //不开放 设置周期 无定时
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

                    //插入labschedule数据
                    $tempData = self::returnLabscheduleData($datetimeArr,$data['status'],$data['remark']);

                    if($tempData && $tempData == "exist"){
                        jsonReturn('002', null, '部分时间段已经设置！');
                        return;
                    }elseif($tempData && is_array($tempData)){
                        $num = self::insertLabSchedule($tempData);
                        if($num){
                            jsonReturn('001',$num,'设置成功！');
                        }else{
                            jsonReturn('002',null,'设置失败！');
                        }
                    }

                }elseif ((int)$data['pattern'] === 1) {
                    //不开放，设置周期，按周定时

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

                    //组装插入数据
                    $tempData = self::returnLabscheduleData($datetimeArr,$data['status'],$data['remark']);

                    if($tempData && $tempData == "exist"){
                        jsonReturn('002', null, '部分时间段已经设置！');
                        return;
                    }elseif($tempData && is_array($tempData)){
                        $num = self::insertLabSchedule($tempData);
                        if($num){
                            jsonReturn('001',$num,'设置成功！');
                        }else{
                            jsonReturn('002',null,'设置失败！');
                        }
                    }else{
                        jsonReturn('002',null,'未在重复范围时间内找到满足条件的定时模式！');
                    }

                }
            }
        }elseif ((int)$data['status'] === 1) {

            if(!$data['department']){
                jsonReturn("002",null,"请选择课程");
                return;
            }

            if(!$data['curriculum_id']){
                jsonReturn("002",null,"请选择课程");
                return;
            }

            if(!$data['teacher_id']){
                jsonReturn("002",null,"请选择教师");
                return;
            }

            //实验开放
            if($data['inputOption'] == 'not_cycle'){
            //实验室开放，不设置周期
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
            $isOrdered = self::checkIsOrdered($datetimeArr, $data['lab_id']);

            if ($isOrdered) {
                jsonReturn('002', null, '该实验在这个时间段已被占用！');
            } else {

                $arr[0]['start_time'] = date("Y-m-d H:i:s", $data['start_time']);
                $arr[0]['end_time'] = date("Y-m-d H:i:s", $data['end_time']);
                $time_info = serialize($arr);

                $insert = [
                    'college_id'    => $data['department'],
                    'curriculum_id' => $data['curriculum_id'],
                    'teacher_id' => $data['teacher_id'],
                    'time_info' => $time_info,
                    'info' => '上课时间为:'.date("Y-m-d H:i:s", $data['start_time']).'至'.date("Y-m-d H:i:s", $data['end_time']),
                    'create_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s')
                ];

                $id = Db::name('corr_teaching')->insertGetId($insert);

                if ($id) {
                    jsonReturn('001', $id, '安排成功，请前往审核！');
                } else {
                    jsonReturn('002', null, '安排失败！');
                }
            }
            }elseif ($data['inputOption'] == 'is_cycle') {
                if((int)$data['pattern'] === 0){
                    //开放 设置周期 无定时

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
                    $isOrdered = self::checkIsOrdered($datetimeArr, $data['lab_id']);

                    if ($isOrdered) {
                        jsonReturn('002', null, '该实验在这个时间段已被占用！');
                    }else{
                        $insert = self::buildOrderData($datetimeArr, $data);

                        $num = Db::name('corr_teaching')->insertGetId($insert);

                        if ($num) {
                            jsonReturn('001', $num, '安排成功，请前往审核！');
                        } else {
                            jsonReturn('002', null, '安排失败！');
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
                    $isOrdered = self::checkIsOrdered($datetimeArr, $data['lab_id']);

                    if ($isOrdered) {
                        jsonReturn('002', null, '该实验在这个时间段已被占用！');
                    }else{
                        $insert = self::buildOrderData($datetimeArr, $data);
                        $num = Db::name('corr_teaching')->insertGetId($insert);

                        if ($num) {
                            jsonReturn('001', $num, '预约成功！');
                        } else {
                            jsonReturn('002', null, '预约失败！');
                        }
                    }

                }
            }
        }
    }


    /**
     * 校历中数据
     */
    public function getData(){

        $lab_id = Session::get('user_infocas.labid');
        $res = [];
        $temp = [];
        $map = [
            'status' => 0,
            'lab_id' => $lab_id
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

        if(empty($lab_id)){
            $cond = [
                'a.status' => 1
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
        jsonReturn('001',$res);
    }


    /**
     * 获取老师信息
     */
    public function getTeachers(){
        $map = [
            'role_id' => array('like','%3%')
        ];
        $res = [];
        $ids = Db::name('corrUserRole')->where($map)->column('user_id');

        if($ids){
            $cond = [
                'id' => array('in',$ids)
            ];
            $res = Db::name('user')->where($cond)->field('id,name')->select();
        }

        jsonReturn('001',$res);
    }

    /**
     * 预约审核详情
     */
//    public function getOrderInfo(){
//        $id = input('post.id');
//        $field = 'a.id,a.start_time,a.end_time,a.status,b.curriculum_name,c.name as teacher_name';
//        $map = [
//            'a.id' => $id
//        ];
//        $res = [];
//        $res = Db::name('order')
//            ->alias('a')
//            ->join('nk_start_curriculum b','b.id=a.curriculum_id')
//            ->join('nk_user c','c.id=a.teacher_id')
//            ->where($map)
//            ->field($field)
//            ->select();
//        jsonReturn('001',$res);
//    }

    /**
     * 预约审批列表
     */
//    public function getAllOrderInfo(){
//        $status = input('post.status',0);
//        $field = 'a.id,a.start_time,a.end_time,a.status,b.curriculum_name,c.name as teacher_name';
//        $map = [
//            'a.status' => $status
//        ];
//        $res = [];
//        $res = Db::name('order')
//            ->alias('a')
//            ->join('nk_start_curriculum b','b.id=a.curriculum_id')
//            ->join('nk_user c','c.id=a.teacher_id')
//            ->where($map)
//            ->field($field)
//            ->select();
//        jsonReturn('001',$res);
//    }

    /**
     * 预约审核
     */
//    public function orderCheck(){
//
//        $id = input('post.id');
//        $status = input('post.status');
//        if( $status == 1 ){
//            $info = Db::name('order')->where('id',$id)->find();
//            if($info){
//                $start_time = $info['start_time'];
//                $end_time = $info['end_time'];
//
//                $sql = "select `id` from `nk_order` WHERE `status`=1 AND ((`start_time`>='" . $start_time . "' AND `start_time`<='" . $end_time . "') OR (`end_time`>='" . $start_time . "' AND `end_time`<='" . $end_time . "') OR (`start_time`<='" . $start_time . "' AND `end_time`>='" . $end_time . "'))";
//
//                $exist = Db::query($sql);
//                if($exist){
//                    jsonReturn("002",null,'该时间段已被占用！');
//                    return;
//                }else{
//                    $update = [
//                        'status' => $status,
//                        'update_time' => date("Y-m-d H:i:s")
//                    ];
//
//                    $ret = Db::name('order')->where('id',$id)->update($update);
//
//                }
//            }else{
//                jsonReturn("002",null,'操作失败！');
//            }
//        }else{
//
//            $update = [
//                'status' => $status,
//                'update_time' => date("Y-m-d H:i:s")
//            ];
//
//            $ret = Db::name('order')->where('id',$id)->update($update);
//        }
//
//
//        if($ret){
//            jsonReturn("001",$ret,'操作成功！');
//        }elseif($ret === 0){
//            jsonReturn("002",null,'已审核！');
//        }else{
//            $status = '未审核';
//            jsonReturn("002",$status,'操作失败！');
//        }
//
//
//    }

    /**
     * 预约审核删除
     */
//    public function orderRemove()
//    {
//        $id = input('post.id');
//
//        $map = [
//            'id' =>  $id
//        ];
//
//        $del= Db::name('order')->where($map)->delete();
//
//        if($del){
//            jsonReturn('001',$del,"删除成功！");
//        }else{
//            jsonReturn('002',null,"删除失败！");
//        }
//    }


    /**
     * 教师预约
     */
//    public function teacherOrder(){
//        $data = input('post.');
//        $user_id = Session::get('user_info.user_id');
//
//        if(!$data['curriculum_id']){
//            jsonReturn("002",null,"请选择课程");
//            return;
//        }
//
//        if(!$user_id){
//            jsonReturn("002",null,"请登录！");
//            return;
//        }
//
//        if ($data['inputOption'] == 'not_cycle'){
//            //开放，不设置周期
//            if ($data['start_time'] > $data['end_time']) {
//                jsonReturn('002,',null,"预约时间设置不合理");
//                return;
//            }
//
//            if ($data['start_time'] < time() || $data['end_time'] < time()) {
//                jsonReturn('002,',null,"预约时间设置不合理");
//                return;
//            }
//
//            //根据时间戳判断所选时间是否在开放时间之内
//            $isFair = self::checkAppointTimeByTimestamp($data['start_time'], $data['end_time']);
//
//            if(!$isFair){
//                jsonReturn('002', null, '预约时间暂不开放！');
//                return;
//            }
//
//            //构建datetimeArr
//            $datetimeArr[0][0] = date("Y-m-d H:i:s", $data['start_time']);
//            $datetimeArr[0][1] = date("Y-m-d H:i:s", $data['end_time']);
//
//            //判断时间是否已经被预约并且已经通过审核
//            $isOrdered = self::checkIsOrdered($datetimeArr);
//
//            if ($isOrdered) {
//                jsonReturn('002', null, '该实验在这个时间段已被占用！');
//            } else {
//                $insert = [
//                    'college_id'    => $data['department'],
//                    'curriculum_id' => $data['curriculum_id'],
//                    'teacher_id'    => $user_id,
//                    'start_time'    => date("Y-m-d H:i:s", $data['start_time']),
//                    'end_time'      => date("Y-m-d H:i:s", $data['end_time']),
//                    'create_time'   => date('Y-m-d H:i:s'),
//                    'update_time'   => date('Y-m-d H:i:s')
//                ];
//
//                $id = Db::name('order')->insertGetId($insert);
//
//                if ($id) {
//                    jsonReturn('001', $id, '预约成功！');
//                } else {
//                    jsonReturn('002', null, '预约失败！');
//                }
//            }
//        }elseif ($data['inputOption'] == 'is_cycle') {
//            if((int)$data['pattern'] === 0){
//                //开放 设置周期 无定时
//
//                if(strtotime($data['starTimeT']) > strtotime($data['endTimeT'])){
//                    jsonReturn("002",null,"您设置的具体时间不合理");
//                    return;
//                }
//
//                //时间
//                $sTime = $data['starTimeT'];
//                $eTime = $data['endTimeT'];
//
//                //日期
//                $sDate = $data['start_date'];
//                $eDate = $data['end_date'];
//
//                //判定传入的日期重复范围是否合理
//                $isOK = self::checkDate($sDate, $eDate);
//                if(!$isOK){
//                    jsonReturn('002,',null,"日期重复范围设置不合理");
//                    return;
//                }
//
//                //获取日期时间数组
//                $datetimeArr = self::getDatetimeArr($sDate, $eDate, $sTime, $eTime);
//
//                //根据datetimeArr判断所选时间是否在开放时间之内
//                $isFair = self::checkAppointTimeByArr($datetimeArr);
//                if(!$isFair){
//                    jsonReturn('002', null, '预约时间暂不开放！');
//                    return;
//                }
//
//                //判断时间是否已经被预约并且已经通过审核
//                $isOrdered = self::checkIsOrdered($datetimeArr);
//
//                if ($isOrdered) {
//                    jsonReturn('002', null, '该实验在这个时间段已被占用！');
//                }else{
//                    $insert = self::buildTeacherOrderData($datetimeArr, $data, $user_id);
//                    $num = Db::name('order')->insertAll($insert);
//
//                    if ($num) {
//                        jsonReturn('001', $num, '预约成功！');
//                    } else {
//                        jsonReturn('002', null, '预约失败！');
//                    }
//                }
//            }elseif ((int)$data['pattern'] === 1){
//                //开放，设置周期，按周定时
//
//                if(strtotime($data['starTimeT']) > strtotime($data['endTimeT'])){
//                    jsonReturn("002",null,"您设置的具体时间不合理");
//                    return;
//                }
//
//                //时间
//                $sTime = $data['starTimeT'];
//                $eTime = $data['endTimeT'];
//
//                //日期
//                $sDate = $data['start_date'];
//                $eDate = $data['end_date'];
//
//                //判定传入的日期重复范围是否合理
//                $isOK = self::checkDate($sDate, $eDate);
//                if(!$isOK){
//                    jsonReturn('002,',null,"日期重复范围设置不合理");
//                    return;
//                }
//
//                //星期数组
//                $xq = $data['chk_value'];
//                if(empty($xq)){
//                    jsonReturn('002,',null,"请选择星期安排");
//                    return;
//                }
//
//                //获取日期数组
//                $dateArr = self::getNeedDateByWeek($sDate, $eDate, $xq);
//
//                //按周定时获取一段重复范围内的有效datetime
//                $datetimeArr = self::getDateTimeArrByWeek($dateArr, $sTime, $eTime);
//
//                //根据datetimeArr判断所选时间是否在开放时间之内
//                $isFair = self::checkAppointTimeByArr($datetimeArr);
//                if(!$isFair){
//                    jsonReturn('002', null, '预约时间暂不开放！');
//                    return;
//                }
//
//                //判断时间是否已经被预约并且已经通过审核
//                $isOrdered = self::checkIsOrdered($datetimeArr);
//
//                if ($isOrdered) {
//                    jsonReturn('002', null, '该实验在这个时间段已被占用！');
//                }else{
//                    $insert = self::buildTeacherOrderData($datetimeArr, $data, $user_id);
//                    $num = Db::name('order')->insertAll($insert);
//
//                    if ($num) {
//                        jsonReturn('001', $num, '预约成功！');
//                    } else {
//                        jsonReturn('002', null, '预约失败！');
//                    }
//                }
//
//            }
//        }
//    }

//    public function teacherOrder(){
//        $data = input('post.');
//        $user_id = Session::get('user_info.user_id');
//
//        $start = $data['start_time'];
//        $end = $data['end_time'];
//
//        if($start > $end){
//            jsonReturn('002',null,'预约时间有误！');
//            return;
//        }
//
//        if($start<time() || $end<time()){
//            jsonReturn('002',null,'预约时间有误！');
//            return;
//        }
//
//        //查找不可预约时间
//        $unUse = Db::name('labSchedule')->select();
//        foreach ($unUse as $k => $v)
//        {
//            if(($start >= strtotime($v['start_time'])) && ($start < strtotime($v['end_time']))){
//                jsonReturn('002',null,'预约时间有误！');
//            }elseif (($start >= strtotime($v['start_time'])) && ($end < strtotime($v['end_time'])))
//            {
//                jsonReturn('002',null,'预约时间有误！');
//            }elseif(($end >= strtotime($v['start_time'])) && ($end < strtotime($v['end_time'])))
//            {
//                jsonReturn('002',null,'预约时间有误！');
//            }
//        }
//
//        $start_time = date("Y-m-d H:i:s", $data['start_time']);
//        $end_time = date("Y-m-d H:i:s", $data['end_time']);
//        //查找预约时间是否已经被占用
//        $sql = "select `id` from `nk_order` WHERE `status`=1 AND ((`start_time`>='".$start_time."' AND `start_time`<='".$end_time."') OR (`end_time`>='".$start_time."' AND `end_time`<='".$end_time."') OR (`start_time`<='".$start_time."' AND `end_time`>='".$end_time."'))";
//
//        $id = Db::query($sql);
//
//        if($id){
//            jsonReturn('002',$id,'该实验在这个时间段已被占用！');
//        }else{
//            $insert = [
//                'curriculum_id' => $data['curriculum_id'],
//                'teacher_id'    => $user_id,
//                'start_time'    => date("Y-m-d H:i:s", $data['start_time']),
//                'end_time'      => date("Y-m-d H:i:s", $data['end_time']),
//                'create_time'   => date('Y-m-d H:i:s')
//            ];
//
//            $id = Db::name('order')->insertGetId($insert);
//
//            if($id){
//                jsonReturn('001',$id,'预约成功！');
//            }else{
//                jsonReturn('002',null,'预约失败！');
//            }
//        }
//
//    }

    /**
     * 教师教学日历显示
     */
    public function getTeacherOrderData(){

        $lab_id = Session::get('user_infocas.labid');

        $res = [];
        $temp = [];
        $map = [
            'status' => 0,
            'lab_id' => $lab_id,
        ];
        
        $unUse = Db::name('labSchedule')->where($map)->select();//查不开放的数据

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

        if(empty($lab_id)){
            $cond = [
                'a.status'   => 1,
                // 'a.teacher_id' => $user_id 
            ];
        }else{
            $cond = [
                'a.status'   => 1,//nk_order表 审核通过的
                // 'a.teacher_id' => $user_id,
                'a.lab_id'     => $lab_id 
            ]; 
        }
        
        $field = 'a.id,a.curriculum_id,a.college_id,a.start_time,a.end_time,a.soft,b.curriculum_name,c.name as teacher_name';
        $data = Db::name('order')
            ->alias('a')
            ->join('nk_start_curriculum b','b.id=a.curriculum_id')
            ->join('nk_user c','c.id=a.teacher_id')
            ->where($cond)
            ->field($field)
            ->select();

        $res['unuse'] = $temp;
        $res['data'] = $data;
        jsonReturn('001',$res);
    }


    /**
     * 修改教学日历中的每一次安排
     */
    public function updateOrder() {
        $data = input('post.');
        //查询当前记录是否是当前用户申请的课程
        $user_id = Session::get('user_info.user_id');
        $lab_id = Session::get('user_infocas.labid');  //获取当前进入的实验室id
        $org_data = Db::name('order')->where('id',$data['id'])->find();
        if($org_data['teacher_id'] != $user_id){
            jsonReturn('002',null,'您没有修改权限！');
            return;
        }
       
//        print_r($data);
        if($data['start_time'] > $data['end_time']){
            jsonReturn('002',null,'时间设置不合理！');
            return;
        }

        if(($data['start_time'] < time() || ($data['end_time'] < time()))){
            jsonReturn('002',null,'时间设置不合理！');
            return;
        }

        // $org_data = Db::name('order')->where('id',$data['id'])->find();
        $arr[0]['start_time'] = date("Y-m-d H:i:s", $data['start_time']);
        $arr[0]['end_time'] = date("Y-m-d H:i:s", $data['end_time']);

        //查询当前申请时间是否已经被占用
        $sql = "select `id` from `nk_order` WHERE `status`=1 AND `lab_id`='" . $lab_id ."' AND `id`<>'" . $data['id'] . "' AND ((`start_time`>='" . $arr[0]['start_time'] . "' AND `start_time`<='" . $arr[0]['end_time'] . "') OR (`end_time`>='" . $arr[0]['start_time'] . "' AND `end_time`<='" . $arr[0]['end_time'] . "') OR (`start_time`<='" .$arr[0]['start_time'] . "' AND `end_time`>='" . $arr[0]['end_time'] . "'))";

        $exist = Db::query($sql);
        if($exist){
            jsonReturn("002", null, '该时间段已被占用！');
            return;
        }

        $time_info = serialize($arr);
        $corr_insert = [
            'order_id'  => $data['id'],
            'teacher_id' => $data['user_id'],
            'curriculum_id' => $data['curriculum_id'],
            'college_id'    => $data['department'],
            'status' => 0,
            'time_info' => $time_info,
            'info' => '上课时间从原来的:'.$org_data['start_time'].'至'.$org_data['end_time'].'，改为：'.date("Y-m-d H:i:s", $data['start_time']).'至'.date("Y-m-d H:i:s", $data['end_time']),
            'create_time' => date('Y-m-d H:i:s',time()),
            'update_time' => date('Y-m-d H:i:s',time())
        ];

        Db::startTrans();
        try{
            Db::name('order')->where('id',$data['id'])->update(['soft' => 1,'update_time' => date('Y-m-d H:i:s',time())]);
            Db::name('corr_teaching')->where('id',$org_data['corr_id'])->update($corr_insert);
            Db::commit();
            jsonReturn('001',null,"申请已提交，请等待审核！");
        }catch (Exception $e) {
            Db::rollback();
            jsonReturn('002',null,"申请失败！");
        }

    }

    /**
     * 教学日历修改记录列表
     */
    public function updateOrderList(){

        $lab_id = Session::get('user_infocas.labid');
        $status = input('status',0);
        $res = [];

        if($status == 0){
            $fields = 'a.id,a.status,d.name as user_name,a.create_time,b.curriculum_name,b.curriculum_num';
            if(empty($lab_id)){
                $map = [
                    'a.status' => $status,
                    'order_id' => array('neq',0),
                ];
            }else{
                $map = [
                    'a.status' => $status,
                    'order_id' => array('neq',0),
                    'a.lab_id' => $lab_id
                ];
            }

            $res = Db::name('corr_teaching')
                ->alias('a')
                ->field($fields)
                ->join('nk_start_curriculum b','a.curriculum_id=b.id')
                ->join('nk_order c','a.order_id=c.id')
                ->join('nk_user d','a.teacher_id=d.id')
                ->where($map)
                ->select();
        }elseif($status == 1){
            $fields = 'a.id,a.status,d.name as user_name,a.create_time,b.curriculum_name,b.curriculum_num';
            if(empty($lab_id)){
                $map = [
                    'a.status' => $status,
                ];
            }else{
                $map = [
                    'a.status' => $status,
                    'a.lab_id' => $lab_id
                ];
            }

            $res = Db::name('corr_teaching')
                ->alias('a')
                ->field($fields)
                ->join('nk_start_curriculum b','a.curriculum_id=b.id')
                ->join('nk_user d','a.teacher_id=d.id')
                ->where($map)
                ->where('update_checker_id','not null')
                ->select();
        }elseif ($status == 2){
            $fields = 'a.id,a.status,a.create_time,d.name as user_name,b.curriculum_name,b.curriculum_num';
            if(empty($lab_id)){
                $map = [
                    'a.status' => $status,
                ];
            }else{
                $map = [
                    'a.status' => $status,
                    'a.lab_id' => $lab_id
                ];
            }

            $res = Db::name('corr_teaching')
                ->alias('a')
                ->field($fields)
                ->join('nk_start_curriculum b','a.curriculum_id=b.id')
                ->join('nk_user d','a.teacher_id=d.id')
                ->where($map)
                ->where('update_checker_id','not null')
                ->select();
        }

        jsonReturn('001',$res);
    }

    /**
     * 教学日历修改记录详情
     */
    public function updateOrderInfo() {
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

        // $info = $current[0]['info'];
        //  dump($info);exit; 
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
                // 'a.corr_id' => array('neq',$id),
                'a.soft' => array('neq',1),
                'a.lab_id' => $lab_id,
            ];
        }else{
            $cond = [
                'a.status' => 1,
                'a.soft' => array('neq',1),
                'a.lab_id' => $lab_id,
            ];
        }

        $field = 'a.id,a.start_time,a.end_time,b.curriculum_name,c.name as teacher_name';
        $data = Db::name('order')
            ->alias('a')
            ->join('nk_start_curriculum b','b.id=a.curriculum_id')
            ->join('nk_user c','c.id=a.teacher_id')
            ->where($cond)
            ->field($field)
            ->select();
        // unset($data[count($data) - 1]);
        $res['unuse'] = $temp;
        $res['data'] = $data;
        $res['current'] = $current;
        jsonReturn('001',$res);
    }

    /**
     * 教学日历修改审批
     */
    public function updateOrderCheck(){
        $id = input('post.id');
        $status = (int)input('post.status');

        $lab_id = Session::get('user_infocas.labid');

        if( $status === 1 ) {
            $corr = Db::name('corr_teaching')->where('id', $id)->find();
            if (!$corr) {
                jsonReturn('002', null, '修改记录不存在！');
                return;
            }

            //查询修改记录的预约表id
            $order_id = Db::name('corr_teaching')->where('id',$id)->value('order_id');

            $corr['time_info'] = unserialize($corr['time_info']);

            $start_time = $corr['time_info'][0]['start_time'];
            $end_time = $corr['time_info'][0]['end_time'];

            $sql = "select `id` from `nk_order` WHERE `status`=1 AND `lab_id`='" . $lab_id ."' AND `id`<>'" . $order_id . "' AND ((`start_time`>='" . $start_time . "' AND `start_time`<='" . $end_time . "') OR (`end_time`>='" . $start_time . "' AND `end_time`<='" . $end_time . "') OR (`start_time`<='" . $start_time . "' AND `end_time`>='" . $end_time . "'))";

            $exist = Db::query($sql);

            if ($exist) {
                jsonReturn("002", null, '该时间段已被占用！');
                return;
            } else {
                $insert = [
                    'corr_id' => $id,
                    'curriculum_id' => $corr['curriculum_id'],
                    'teacher_id' => $corr['teacher_id'],
                    'college_id' => $corr['college_id'],
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'soft' => 0,
                    'status' => 1,
                    'lab_id' => $lab_id,
                    'create_time' => date("Y-m-d H:i:s"),
                    'update_time' => date("Y-m-d H:i:s")
                ];
                Db::startTrans();
                try {
                    $insert_id = Db::name('order')->insertGetId($insert);

                    $corr_update = [
                        'order_id' => 0,
                        'update_checker_id' => Session::get('user_info.user_id'),
                        'update_check_time' => date("Y-m-d H:i:s",time()),
                        'status' => 1
                    ];
                    Db::name('corr_teaching')->where('id', $id)->update($corr_update);
                    Db::name('order')->where('id', $corr['order_id'])->delete();
                    Db::commit();
                    jsonReturn('001', $insert_id, '审批完成');
                } catch (Exception $e) {
                    Db::rollback();
                    jsonReturn('002', null, '审批失败！');
                }

            }
        }elseif($status === 2){
            $corr = Db::name('corr_teaching')->where('id', $id)->find();
            $map = [
                'id' => $corr['order_id']
            ];
            // $map = [
            //     'corr_id' => $corr['id']
            // ];
            $update = [
                'soft' => 0,
                'status' => 2,
            ];
            Db::startTrans();
            try{
                Db::name('order')->where($map)->update($update);
                $corr_update = [
                    'order_id' => 0,
                    'update_checker_id' => Session::get('user_info.user_id'),
                    'update_check_time' => date("Y-m-d H:i:s",time()),
                    // 'status' => 2
                ];
                Db::name('corr_teaching')->where('id',$id)->update($corr_update);
                Db::commit();
                jsonReturn('001', null, '审批完成');
            }catch (Exception $e) {
                Db::rollback();
                jsonReturn('002', null, '审批失败！');
            }
        }

    }

    /**
     * 教学日历修改删除
     */
    public function updateOrderDelete(){
        $id = input('post.id');
        // dump($id);exit;
        $map = [
            'id' =>  $id
        ];

        $del= Db::name('corr_teaching')->where($map)->delete();

        if($del){
            jsonReturn('001',$del,"删除成功！");
        }else{
            jsonReturn('002',null,"删除失败！");
        }
    }

}