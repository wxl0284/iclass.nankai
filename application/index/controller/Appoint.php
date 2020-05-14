<?php
/**
 * Created by PhpStorm.
 * User: 高笛淳
 * Date: 2017/10/10
 * Time: 15:33
 */

namespace app\index\controller;

use app\common\controller\BaseController;
use app\index\model\LabOrder;
use think\Db;
use think\Session;
class Appoint extends BaseController
{
    /**
     * 预约实验室
     */
    public function event() {
        $data = json_decode(urldecode($_POST['data']),true);

        $start = date("Y-m-d H:i:s" , trim($data['startTime']));
        $end = date("Y-m-d H:i:s" , trim($data['endTime']));

        if(strtotime($start) > strtotime($end)){
            jsonReturn('002',null,'预约开始时间与结束时间有误！');
            return;
        }

        if(strtotime($start)<time() || strtotime($end)<time()){
            jsonReturn('002',null,'预约时间有误！');
            return;
        }
        $lab_id = trim($data['name']);
        $lab = Db::name('baseLaboratory')->where('id',$lab_id)->value('laboratory_name');

        $insertData = [
            'user_id'   =>  session::get('user_info.user_id'),
            'lab_id'    => $lab_id,
            'lab_name'  =>  $lab,
            's_time'  =>  $start,
            'e_time'  =>  $end,
            'curriculum_id' => $data['option'],
            'charge'  =>  trim($data['people']),
        ];


        $sql = "select `id` from `nk_lab_order` WHERE `status`<>2 AND ((`s_time`>='".$start."' AND `s_time`<='".$end."') OR (`e_time`>='".$start."' AND `e_time`<='".$end."') OR (`s_time`<='".$start."' AND `e_time`>='".$end."'))";

        $id = Db::query($sql);
        if($id){
            jsonReturn('002',$id,'该实验在这个时间段已被占用！');
        }else{
            Db::startTrans();
            $model = new LabOrder;
            $model->data($insertData);
            $model->save($insertData);
            $num = $model->id;

            if($num){
                Db::commit();
                $c = [
                    'start' => $start,
                    'end'	=>	$end,
                    'title' => "{$data['people']}预约了{$lab}"
                ];

                jsonReturn('001',$c,'预约成功！');
            }else{
                Db::rollback();
                jsonReturn('002',null,'预约失败！');
            }
        }
    }

    /**
     * 预约日历显示
     */
    public function data(){
        $user_id = Session::get('user_info.user_id');

        if($user_id){
            $fields = 'lab_name,s_time as start,e_time as end,charge';
            $map = [
                'status' => array('in',array('0','1'))
//                'user_id' => $user_id
            ];
            $collection = Db::name('lab_order')
                ->field($fields)
                ->order('update_time desc')
                ->where($map)
                ->select();

            $res =[];
            $color = ["#E91E63","#2196F3","#9C27B0","#03A9F4","#6B7BBD","#80A7A0","#925582","#A279A4","#ADC238","#C6C648"];

            if(null !== $collection){
                foreach ($collection as $item){
                    $num = mt_rand(0,9);
                    $item['color'] = $color[$num];
                    $res[] = $item;
                }

                if($res){
                    foreach ($res as $key => $value){
                        $res[$key]['title'] = "{$value['charge']}预约了{$value['lab_name']}";
                        unset($res[$key]['charge']);
                        unset($res[$key]['lab_name']);
                    }
                }

                jsonReturn('001',$res);
            }
        }
    }


    /**
     * 预约详情列表
     */
    public function appointDetail(){
        $user_id = Session::get('user_info.user_id');
        $status = input('post.status');
        if(null != $status){
            $map = [
                'a.status' => $status
            ];

            $res = Db::name('labOrder')
                ->alias('a')
                ->field('b.curriculum_name,a.lab_name,a.s_time,a.e_time,a.charge,a.status,a.id,a.user_id')
                ->join('nk_curriculum b','b.id=a.curriculum_id')
                ->where($map)
                ->select();
        }else{
            $res = Db::name('labOrder')
                ->alias('a')
                ->field('b.curriculum_name,a.lab_name,a.s_time,a.e_time,a.charge,a.status,a.id,a.user_id')
                ->join('nk_curriculum b','b.id=a.curriculum_id')
                ->select();
        }


        if($res){
            foreach ($res as $k => $v){
                $res[$k]['appoint_time'] = $v['s_time'].'至'.$v['e_time'];
                switch($v['status']) {
                    case 0:
                        $res[$k]['status'] = '待审核';
                        break;
                    case 1:
                        $res[$k]['status'] = '已通过';
                        break;
                    case 2:
                        $res[$k]['status'] = '未通过';
                        break;
                }

                unset($res[$k]['s_time']);
                unset($res[$k]['e_time']);

                if($v['user_id'] == $user_id){
                    $res[$k]['ishow'] = 'show';
                }else{
                    $res[$k]['ishow'] = 'hidden';
                }
            }

            jsonReturn('001',$res);
        }else{
            jsonReturn('002',null,'查无结果！');
        }

    }

    /**
     * 预约审核
     */
    public function alterAppoint(){
        $id = input('post.id');
        $status = input('post.status');

        //更新资源状态
        $save = array('status' => $status);
        $ret = Db::name('lab_order')->where('id',$id)->update($save);
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
     * 删除预约审核
     */
    public function removeAppoint()
    {
        $id = input('post.id');

        $map = [
            'id' =>  $id
        ];

        $del= Db::name('lab_order')->where($map)->delete();

        if($del){
            jsonReturn('001',$del,"删除成功！");
        }else{
            jsonReturn('002',null,"删除失败！");
        }
    }

    /**
     * 手机端预约列表
     */
    public function phoneAppointList(){
        $lab_id = input('post.lab_id');
        $current_date = date("Y-m-d",strtotime(input('post.current_date')));

        $start_time = $current_date. ' 00:00:00';
        $end_time = $current_date. ' 23:59:59';

        $map = [
            'lab_id' => $lab_id,
            's_time' => array('between',array($start_time,$end_time)),
            'e_time' => array('between',array($start_time,$end_time))
        ];
        $res = [];
        $col = LabOrder::all($map);


        if(null !== $col){
            foreach ($col as $k => $v)
            {
                $col[$k]['time'] = substr($v['s_time'],11) . '-'. substr($v['e_time'],11);
                unset($col[$k]['s_time']);
                unset($col[$k]['e_time']);
                $res[] = $v->hidden(['create_time','update_time'])->toArray();
            }
        }

        if($res){
            jsonReturn('001',$res);
        }else{
            jsonReturn('002',null);
        }
    }

    /**
     * 手机端预约列表弹层
     */
    public function phoneAppointAlert(){
        $id = input('post.id');
        $col = LabOrder::get($id);
        $res = [];
        if(null !== $col){
            $res = $col->toArray();
        }
        jsonReturn('001',$res);
    }

    /**
     * 手机端预约课程显示
     */
    public function phoneGetCurriculum(){
//        $fields = "concat(curriculum_name,curriculum_num)";
//        $ret = Db::name('curriculum')->column($fields);

        $fields = "curriculum_name,curriculum_num";
        $res = Db::name('curriculum')->field($fields)->select();

        foreach ($res as $k => $v)
        {
            $ret[] = $v['curriculum_name'] . '(' . $v['curriculum_num'] . ')';
        }

        if($ret){
            jsonReturn('001',$ret);
        }else{
            jsonReturn('002',null,"查询失败！");
        }

    }

    /**
     * 手机端预约实验室
     */
    public function phoneEvent() {

		$user_id = input('post.user_id');
        $id = input('post.id');
        $data = input('post.data/a');
        $date_timestamp = strtotime($data['date']);


        if(empty($data['starTime']) || empty($data['endTime'])){
            jsonReturn('002',null,'请选择时间！');
            return;
        }

        $start = date('Y-m-d',$date_timestamp) . " " .trim($data['starTime'][0]). ":" . trim($data['starTime'][1]). ":00";
        $end = date('Y-m-d',$date_timestamp) . " " .trim($data['endTime'][0]). ":" . trim($data['endTime'][1]) . ":00";

        if(strtotime($start) > strtotime($end)){
            jsonReturn('002',null,'此时间段不可预约！');
            return;
        }

        if(strtotime($start)<time() || strtotime($end)<time()){
            jsonReturn('002',null,'此时间段不可预约！！');
            return;
        }
        $curr = explode('(',$data['curriculum'][0]);

        $curriculum_id = Db::name('curriculum')->where('curriculum_name',$curr[0])->field('id')->find();

        $lab_id = trim($id);
        $lab_name = $data['selectlab'];

        $insertData = [
            'user_id'   =>  $user_id,
            'lab_id'    => $lab_id,
            'lab_name'  =>  $lab_name,
            's_time'  =>  $start,
            'e_time'  =>  $end,
            'curriculum_id' => $curriculum_id['id'],
            'charge'  =>  trim($data['people']),
        ];

        $sql = "select `id` from `nk_lab_order` WHERE `status`<>2 AND ((`s_time`>='".$start."' AND `s_time`<='".$end."') OR (`e_time`>='".$start."' AND `e_time`<='".$end."') OR (`s_time`<='".$start."' AND `e_time`>='".$end."'))";

        $id = Db::query($sql);
        if($id){
            jsonReturn('002',$id,'该实验在这个时间段已被占用！');
        }else{
            Db::startTrans();
            $model = new LabOrder;
            $model->data($insertData);
            $model->save($insertData);
            $num = $model->id;

            if($num){
                Db::commit();
                $c = [
                    'start' => $start,
                    'end'	=>	$end,
                    'title' => "{$data['people']}预约了{$lab_name}"
                ];

                jsonReturn('001',$c,'预约成功！');
            }else{
                Db::rollback();
                jsonReturn('002',null,'预约失败！');
            }
        }
    }
}