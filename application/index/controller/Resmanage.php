<?php
/**
 * Created by PhpStorm.
 * User: 高笛淳
 * Date: 2017/8/30
 * Time: 10:23
 */

namespace app\index\controller;

use app\common\controller\BaseController;
use think\Session;
use think\Db;
class Resmanage extends BaseController
{

    /**
     * 上传资源
     * @param string $sign
     */
//    public function uploads($sign='')
//    {
//        upload($sign);
//    }
//

    /**
     * 资源中心，上传资源
     */
    public function upSource(){
        $data = str_replace(PHP_EOL, '', input('post.'));
        $ext = substr($_FILES['file']['name'],strpos($_FILES['file']['name'],'.'));
        $allow_size = 524288000;

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
        $rets =  Db::name('pubResources')->where('name',$name)->select();
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
                    if(!in_array($ext,['.zip','.rar'])){
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
                    'name'          => $name,
                    'user_id'       => $userid,
                    'type'          => $rec_type,
                    'url'           => $store_name,
                    'time'          => date("Y-m-d H:i:s"),
                    'describe'      => $describe,
                ];
                $userId = Db::name('pubResources')->insertGetId($add);

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
     *	资源中心，资源列表查询
     */
    public function resourceQuery()
    {
        $status = input('status',0);
        $input_val = input('input_val');

        $user_id = Session::get('user_info.user_id');

        $list = [];
        $fields = 'b.name as uploadName,a.name as resourceName,a.id,a.type,a.time as uptime,a.status,a.url,a.describe';
        if($user_id){
            if($status==0){
                $cond = [
                        'user_id' => $user_id,
                        'status' => 0
                ];
                if($input_val != ''){
                   $cond['a.name|a.type'] = array('like','%'. $input_val . '%');

                }
                $list = Db::name('pubResources')
                    ->alias('a')
                    ->join('nk_user b','a.user_id=b.id')
                    ->where($cond)
                    ->field($fields)
                    ->select();
            }else if($status==1){
                $map = [
                    'status'   =>  1,
                ];
                if($input_val != ''){
                    $map['a.name|a.type'] = array('like','%'. $input_val . '%');
                }
                $list = Db::name('pubResources')
                    ->alias('a')
                    ->join('nk_user b','a.user_id=b.id')
                    ->where($map)
                    ->field($fields)
                    ->select();

            }else{
                $cond = [
                    'user_id' => $user_id,
                    'status' => 2,
                ];
                if($input_val != ''){
                    $cond['a.name|a.type'] = array('like','%'. $input_val . '%');
                }
                $list = Db::name('pubResources')
                    ->alias('a')
                    ->join('nk_user b','a.user_id=b.id')
                    ->where($cond)
                    ->field($fields)
                    ->select();

            }
            if($list){
                foreach ($list as $k => $v) {
                    $ext = substr($v['url'], strpos($v['url'],'.'));
                    $list[$k]['downloadName'] = $v['resourceName'] . $ext;
                }
            }
            jsonReturn('001',$list);
        }else{
            jsonReturn('002',null,'请先登录！');
        }
    }

    /**
     * 资源审批列表
     */
    public function resourceCheckList(){
        $status = input('post.status',0);
        $list = [];
        $fields = 'b.name as uploadName,a.name as resourceName,a.id,a.type,a.time as uptime,a.status,a.url,a.describe';
        $cond = [
            'status' => $status
        ];

        $list = Db::name('pubResources')
            ->alias('a')
            ->join('nk_user b','a.user_id=b.id')
            ->where($cond)
            ->field($fields)
            ->select();

        if($list){
            foreach ($list as $k => $v) {
                $ext = substr($v['url'], strpos($v['url'],'.'));
                $list[$k]['downloadName'] = $v['resourceName'] . $ext;
            }
        }

        jsonReturn('001',$list);
    }

    /**
     *  资源详情查询
     */
    public function particulars()
    {

        $id = input('post.id');
        $list = [];
        $fields = 'b.name as uploadName,a.name as resourceName,a.id,a.type,a.time as uptime,a.status,a.url,a.describe';
        $cond = [
            'a.id' => $id
        ];

        $list = Db::name('pubResources')
            ->alias('a')
            ->join('nk_user b','a.user_id=b.id')
            ->where($cond)
            ->field($fields)
            ->select();
        jsonReturn('001',$list);
    }

    /**
     *  删除资源
     */
    public function delete()
    {
        $id = input('post.id');

        $map = [
            'id'    =>  $id
        ];
        $data = Db::name('pubResources')->where($map)->find();
        $image_path = $data['url'];

        $del= Db::name('pubResources')->where($map)->delete();

        if($del){
            @unlink($image_path);
            jsonReturn('001',$del,"删除成功！");
        }else{
            jsonReturn('002',null,"删除失败！");
        }
    }

    /**
     *	资源审核
     *
     */
    public function examine()
    {
        $id = input('post.id');
        $status = input('post.status');

        //更新资源状态
//        $save = array('status' => $status);
        $save = [
            'status' => $status,
            'checker_id'    => Session::get('user_info.user_id'),
            'check_time'    => date("Y-m-d H:i:s",time())
        ];
        $ret = Db::name('pubResources')->where('id',$id)->update($save);
        if($ret){
            //查询资源信息
            switch($status){
                case '1':
                    $status = "已通过";
                    break;
                case '2':
                    $status = "未通过";
                    break;
                default:
                    $status = "服务器故障";
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
     * 资源搜索查找
     */
   public function search()
   {
        $data = input('post.data');
        $ret = Db::name('pub_resources')
               ->where('name|type|describe','like','%'. $data . '%')
               ->select();
        if($ret){
            jsonReturn('001',$ret);
        }else{
            jsonReturn('002',null,$ret);
        }
        
   }

    /**
     * 下载资源
     */
//    public function download_file(){
//
//        $id = input('id');
//        $data = Db::name('resources')->where('id',$id)->find();
//
//        //更新下载量
//        $save = array(
//            'download' => $data['download'] + 1,
//        );
//        $upload = Db::name('resources')->where('id',$id)->update($save);
//        if($upload){
//            //判断资源类型
//            if($data['type'] == '静态资源'){
//                $file = $data['url'];
//            }else{
//                $file = $data['file'];
//            }
//            //下载文件
//            $name = $data['name'];
//            if(is_file($file)){
//                $length = filesize($file);
//                $type = mime_content_type($file);
//                $showname =  ltrim(strrchr($file,'/'),'/');
//                $filename = $name . strrchr($showname,'.'); //文件名替换
//                header("Content-Description: File Transfer");
//                header('Content-type: ' . $type);
//                header('Content-Length:' . $length);
//                if(preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])){ //for IE
//                    header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
//                }else{
//                    header('Content-Disposition: attachment; filename="' . $filename . '"');
//                }
//                readfile($file);
//            }else{
//                @header("http/1.1 404 not found");
//                @header("status: 404 not found");
//                @header("Content-type: text/html; charset=utf-8");
//                echo "<script>alert(\"文件不存在！\");window.history.go(-1);window.location.reload();</script>";
//                exit();
//            }
//        }
//    }


    /**
     * 资源统计饼图、柱状图（通用）
     */
   public function resourceStatisticPieData()
   {
       //查询文档资源
       $text = Db::name('pub_resources')->where('type','文档')->count();
       //查询图片资源
       $img = Db::name('pub_resources')->where('type','图片')->count();
       //查询视频资源
       $video = Db::name('pub_resources')->where('type','视频')->count();
       //查询压缩文件资源
       $file = Db::name('pub_resources')->where('type','压缩文件')->count();
       //查询可执行文件资源
       $executable = Db::name('pub_resources')->where('type','可执行文件')->count();
       $ret = array(
            'text'       => $text,
            'img'        => $img,
            'video'      => $video,
            'file'       => $file,
            'executable' => $executable,
        );
       $rut = array($text,$img,$video,$file,$executable,);
       $array = array('pie' => $ret, 'bar' => $rut);
       jsonReturn('001',$array);
   }

    /**
     * 资源统计柱图
     */
//    public function resourceStatisticBarData()
//    {
//        $field = 'type as name, sum(download) as num';
//        $res = Db::name('resources')->field($field)->group('type')->select();
//
//        $xAxis = [];
//        $series_data = [];
//        foreach ($res as $key => $value) {
//            $xAxis[] = $value['name'];
//            $series_data[] = $value['num'];
//        }
//
//        $options = [
//            'x_data' => $xAxis,
//            'y_data' => $series_data
//        ];
//
//        jsonReturn('001',$options);
//    }

}