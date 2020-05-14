<?php
/**
 * Created by PhpStorm.
 * User: 高笛淳
 * Date: 2017/9/4
 * Time: 9:50
 */

namespace app\index\controller;

use app\common\controller\BaseController;
use app\index\model\BaseLaboratory as BaseLaboratoryModel;
use app\index\model\Instrucment as InstrucmentModel;
use think\Db;

class BaseLaboratory extends BaseController
{
    /**
     * 上传实验室图片
     * @param $sign
     */
    public function uploadLabratoryImage($sign)
    {
        upload($sign);
    }

    /**
     * 新增实验室基本信息
     */
    public function addLaboratory(){
        
        $data = input('post.data/a');

        $inserData = [
            'laboratory_name'   =>  trim($data['laboratory_name']),
            'college'           =>  trim($data['college']),
            'curriculum_name'   =>  trim($data['curriculum_name']),
            'core_id'           =>  trim($data['core_id']),
            'laboratory_type'   =>  trim($data['laboratory_type']),
            'accommodate_num'   =>  trim($data['accommodate_num']),
            'occupied_area'     =>  trim($data['occupied_area']),
            'instrument'        =>  trim($data['instrument']),
            'setup_time'        =>  trim($data['setup_time']),
            'approval'          =>  trim($data['approval']),
            'address'           =>  trim($data['address']),
            'laboratory_num'    =>  trim($data['laboratory_num']),
            'laboratory_user'   =>  trim($data['laboratory_user']),
            'image'             =>  trim($data['image'])
        ];

        $Base = BaseLaboratoryModel::create($inserData);
        $id = $Base->id;
        if($id){
            jsonReturn('001',$id,'新增成功！');
        }else{
            jsonReturn('002',null,'新增失败！');
        }
    }

    /**
     * 实验室列表
     */
    public function showLaboratoryList(){
        
        $res = [];
        $col = BaseLaboratoryModel::all();
        if(null !== $col){
            foreach ($col as  $key => $val){
                $res[] = $val->hidden(['create_time','update_time'])->toArray();
            }
        }
        jsonReturn('001',$res);
    }

    /**
     * 实验室详情
     */
    public function showLaboratoryInfo(){
        
        $id = input('post.id');
        $res = [];
        $model = BaseLaboratoryModel::get($id);
        if(null !== $model){
            $res = $model->hidden(['create_time','update_time'])->toArray();
        }
        jsonReturn('001',$res);
    }

    /**
     * 修改实验室信息
     */
    public function updateLaboratory(){
        
        $data = input('post.data/a');
        $id = input('post.id');

        $col = BaseLaboratoryModel::get($id);
        if($col){
            $res = $col->toArray();
            $image = $res['image'];
        }

        $updateData = [
            'id'                =>  $id,
            'laboratory_name'   =>  trim($data['laboratory_name']),
            'college'           =>  trim($data['college']),
            'curriculum_name'   =>  trim($data['curriculum_name']),
            'core_id'           =>  trim($data['core_id']),
            'laboratory_type'   =>  trim($data['laboratory_type']),
            'accommodate_num'   =>  trim($data['accommodate_num']),
            'occupied_area'     =>  trim($data['occupied_area']),
            'instrument'        =>  trim($data['instrument']),
            'setup_time'        =>  trim($data['setup_time']),
            'approval'          =>  trim($data['approval']),
            'address'           =>  trim($data['address']),
            'laboratory_num'    =>  trim($data['laboratory_num']),
            'laboratory_user'   =>  trim($data['laboratory_user']),
            'image'             =>  trim($data['image'])
        ];

        $model = BaseLaboratoryModel::update($updateData);

        $rid = $model->id;
        if($rid){
            @unlink($image);
            jsonReturn('001',$rid,'更新成功！');
        }else{
            jsonReturn('002',null,'更新失败！');
        }
    }

    /**
     * 删除实验室信息
     */
    public function delOneLaboratory(){
        
        $id = trim(input('post.id'));

        $img = BaseLaboratoryModel::where(['id'=>$id])->value('image');

        $num = BaseLaboratoryModel::destroy($id);
        if($num){
            if($img){
                @unlink($img);
            }
            jsonReturn('001',$num,'删除成功！');
        }else{
            jsonReturn('002',$num,'删除失败！');
        }
    }

    /**
     * 手机端实验室信息显示
     */
    public function phoneShowLaboratoryList(){

        $res = [];
        $col = BaseLaboratoryModel::all();
        if(null !== $col){
            foreach ($col as  $key => $val){
                $col[$key]['image'] = "http://".$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT']."/". $val['image'];
                $res[] = $val->hidden(['create_time','update_time'])->toArray();
            }
        }
        jsonReturn('001',$res);
    }

    /**
     * 上传仪器图片
     * @param $sign
     */
    public function uploadInstruImage($sign)
    {
        upload($sign);
    }

    /**
     * 新增实验室仪器信息
     */
    public function addLaboratoryInstru(){

        $data = input('post.data/a');

        $inserData = [
            'lab_id'   =>  trim($data['lab_name']),
            'instru_name'           =>  trim($data['instru_name']),
            'instru_image'   =>  trim($data['instru_image']),
            'instru_options'           =>  json_encode($data['items'])
        ];

        $Base = InstrucmentModel::create($inserData);
        $id = $Base->id;
        if($id){
            jsonReturn('001',$id,'新增成功！');
        }else{
            jsonReturn('002',null,'新增失败！');
        }
    }

    /**
     * 修改仪器信息
     */
    public function updateLaboratoryInstru(){

        $data = input('post.data/a');
        $id = input('post.id');

//        $col = InstrucmentModel::get($id);
//        if($col){
//            $res = $col->toArray();
//            $image = $res['instru_image'];
//        }

        $updateData = [
            'id'                =>  $id,
            'lab_id'   =>  trim($data['lab_name']),
            'instru_name'           =>  trim($data['instru_name']),
            'instru_image'   =>  trim($data['instru_image']),
            'instru_options'           =>  json_encode($data['items'])
        ];

        $model = InstrucmentModel::update($updateData);

        $rid = $model->id;
        if($rid){
//            @unlink($image);
            jsonReturn('001',$rid,'更新成功！');
        }else{
            jsonReturn('002',null,'更新失败！');
        }
    }

    /**
     * 实验室列表
     */
    public function showLaboratoryInstruList(){
        $res = [];
        $re = [];
        $field = 'a.id,a.instru_name,a.instru_image,a.instru_options,b.laboratory_name';
        $map = [];
        $res = Db::name('instrucment')
            ->alias('a')
            ->field($field)
            ->join('nk_base_laboratory b','b.id=a.lab_id')
            ->where($map)
            ->select();

        if($res){
            foreach ($res as $k => $v){

                $tmp = 'http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].'/'.$v['instru_image'];
                unset($v['instru_image']);
                $v['instru_image'] = $tmp;
                $re[] = $v;
            }
            jsonReturn('001',$re);
        }

    }

    /**
     * 删除实验室信息
     */
    public function delOneLaboratoryInstru(){

        $id = trim(input('post.id'));

        $img = InstrucmentModel::where(['id'=>$id])->value('instru_image');

        $num = InstrucmentModel::destroy($id);
        if($num){
            if($img){
                @unlink($img);
            }
            jsonReturn('001',$num,'删除成功！');
        }else{
            jsonReturn('002',$num,'删除失败！');
        }
    }

    /**
     * 实验室和仪器信息
     */
    public function showLaboratoryInfoAndInstru(){

        $id = input('post.id');
        $res = [];
        $model = BaseLaboratoryModel::get($id);
        if(null !== $model){
            $res['lab'] = $model->hidden(['create_time','update_time'])->toArray();
        }
        $map = [
            'lab_id' => $id
        ];
        $inst = InstrucmentModel::all($map);
        if(null !== $inst){
            $res['inst'] = $inst;
        }else{
            $res['inst'] = [];
        }
        jsonReturn('001',$res);
    }

    /**
     * 仪器详情
     */
    public function showInstruInfo(){

        $id = input('post.id');
        $res = [];

        $map = [
            'id' => $id
        ];
        $inst = InstrucmentModel::get($map);
        if(null !== $inst){
            $res = $inst;
        }
        jsonReturn('001',$res);
    }
}