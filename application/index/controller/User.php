<?php
/**
 * Created by PhpStorm.
 * User: 高笛淳
 * Date: 2017/9/1
 * Time: 11:02
 */

namespace app\index\controller;

use app\common\controller\BaseController;
use app\index\model\User as UserModel;
use app\index\model\Role as RoleModel;
use app\index\model\Rule as RuleModel;
use think\Config;
use think\Exception;
use think\Loader;
use think\Session;
use think\Db;
use think\Request;
class User extends BaseController
{
    /**
     * 新增单一用户
     */
//    public function addOneUser()
//    {
//        //验证是否在新增用户的时候选择了角色
//        $data = input('post.data/a');
//        $role = trim(input("post.data.role"));
//
//        if(!$role){
//            jsonReturn("002",null,"请选择权限！");
//            return;
//        }
//
//        $phone = trim($data['phone']);
//        $email = trim($data['email']);
//
//        $to_check_data = [
//            'phone' =>  $phone,
//            'email' =>  $email
//        ];
//
//        //验证密码，手机，邮箱格式是否正确
//        $validate = Loader::validate('User');
//        if(!$validate->check($to_check_data)){
//            jsonReturn('002',null,$validate->getError());
//            return;
//        }
//
//        $status = $data['status'];
//        switch($status) {
//            case '0':
//                $inschool = '在校';
//                break;
//            case '1':
//                $inschool = '离校';
//                break;
//            case '2':
//                $inschool = '其他';
//                break;
//        }
//
//        $insert_data = [
//            'name'       => trim($data['name']),
//            'role_id'    => $role,
//            'sex'        => trim($data['gender']),
//            'phone'      => $phone,
//            'email'      => $email,
//            'user_uid'   => trim($data['userID']),
//            'inschool'   => $inschool
//        ];
//
//        $insert_data['portrait'] = "uploads/portrait/moren.png";
//
//        //新增用户
//        $user = UserModel::create($insert_data);
//        $id = $user->id;
//        if($id){
//            jsonReturn("001",$id,"新增用户成功！");
//        }else{
//            jsonReturn("002",null,"新增用户失败！");
//        }
//    }

    /**
     * 删除用户
     */
    public function deleteUser(){

        $id = trim(input('post.id'));

        $event = Loader::controller('Api','event');
        $img = $event->deleteUserApi($id);

        if($img){
            if(!preg_match("/([a-zA-Z\/]+)moren.png$/",$img)){
                @unlink($img);
            }
            jsonReturn('001',null,'删除成功！');
        }else{
            jsonReturn('002',null,'删除失败！');
        }
    }

    /**
     * 批量删除用户
     * @param Request $request
     */
//    public function multiDeleteUser(Request $request ){
//        $id_arr = $request->param('id/a');
//
//        try{
//            foreach ($id_arr as $id){
//                $event = Loader::controller('Api','event');
//                $img = $event->deleteUserApi($id);
//
//                if($img){
//                    if(!preg_match("/([a-zA-Z\/]+)moren.png$/",$img)){
//                        @unlink($img);
//                    }
//                }
//            }
//            jsonReturn('001','','删除成功！');
//        }catch (\Exception $e){
//            jsonReturn('002',$e,'删除失败！');
//        }
//    }

    /**
     * 加载用户列表数据
     */
    public function loadUserListData(){
        $res = [];
        $re = [];
        $map = [
            'a.id' => array('neq', Session::get('user_info.user_id'))
        ];
        $field = 'a.id,a.name as userName,a.sex,a.user_uid as cardNumber,a.phone,a.email,a.inschool,a.professional,a.college,a.major,a.website,b.role_id';
        $res = Db::name('user')
            ->alias('a')
            ->join('nk_corr_user_role b','a.id=b.user_id')
            ->field($field)
            ->where($map)
            ->select();
//        if($res){
//            foreach ($res as $k => $v){
//                $tmp = 'http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].'/'.$v['portrait'];
//                unset($v['portrait']);
//                $v['portrait'] = $tmp;
//                $re[] = $v;
//            }
//            jsonReturn('001',$re);
//        }

        $role = Db::name('role')->column('name','id');

        if($res){
            foreach ($res as $k => $v) {

                $roleArr = explode(',',$v['role_id']);
                $roleRes = [];
                foreach ($roleArr as $kk => $vv) {
                    array_push($roleRes,$role[$vv]);
                }

                $res[$k]['role'] = implode(',',$roleRes);
            }
            jsonReturn('001',$res);
        }
    }

    /**
     * 用户列表修改权限详情
     */
    public function getOneUserInfo(){
        $id = input('post.id');
        $map = [
            'id' => $id
        ];
        $field = 'a.id,a.name as userName,a.sex,a.user_uid as cardNumber,a.phone,a.email,a.inschool,a.professional,a.college,a.major,a.website,b.role_id';
        $res = Db::name('user')
            ->alias('a')
            ->join('nk_corr_user_role b','a.id=b.user_id')
            ->field($field)
            ->where($map)
            ->find();
        if($res){
            $roleArr = explode(',',$res['role_id']);
            $res['role_id'] = $roleArr;
        }
        jsonReturn('001',$res);
    }

    /**
     * 修改权限
     */
    public function alterRole(){
        $id = input('post.id');
        $role = input('post.arr/a');

        $map = [
            'user_id'   =>  $id
        ];

        $update = [
            'role_id' => implode(',',$role)
        ];
        $num = Db::name('corr_user_role')->where($map)->update($update);
        if($num){
            jsonReturn('001',$num,'修改成功！');
        }else{
            jsonReturn('002',null,'修改失败！');
        }

    }

    /**
     * 角色列表信息
     */
    public function roleList(){

        $collect = RoleModel::all();
        if($collect){
            foreach ($collect as $key => $value){
                $roleList[] = $value->toArray();
            }
            $res = $roleList?$roleList:[];
            jsonReturn("001",$res);
        }else{
            jsonReturn("001",null);
        }
    }

    /**
     * 查看个人信息
     */
    public function selectSelfInfo(){
        $user_id = Session::get('user_info.user_id');

        if(!$user_id){
            jsonReturn('002',null,'用户不存在！');
            return;
        }

        $map = [
            'id' => $user_id
        ];
        $res = Db::name('user')->where($map)->find();
        $res['resume'] = html_entity_decode($res['resume']);
        if($res){
            jsonReturn('001',$res);
        }else{
            jsonReturn('002',null,'查询信息有误！');
        }
    }

    /**
     * 修改个人信息
     */
    public function modifySelfInfo(){

        $user_id = Session::get('user_info.user_id');

        if(!$user_id){
            jsonReturn('002',null,'用户不存在！');
            return;
        }
        $data = input('post.');

        $email = trim($data['email']);

        $result = $this->validate(
            [
                'email' => $email
            ],
            [
                'email|邮箱'   => 'email'
            ]);

        if(true !== $result){
            // 验证失败 输出错误信息
            jsonReturn('002',null,$result);
            return;
        }

        $portrait_data = trim($data['image']);
//        if(!preg_match("/([a-zA-Z\/]+)moren.png$/",$portrait_data)){
//            $portrait_path = saveBase64Img($portrait_data,"","portrait");
//            if(!$portrait_path){
//                //上传失败
//                jsonReturn('002',null,"头像上传失败！");
//                return;
//            }
//        }else{
//            $portrait_path = $portrait_data;
//        }
        // if(preg_match("/^data:image\/(jpeg|png|gif);base64,$/",$portrait_data)){
        //     $portrait_path = saveBase64Img($portrait_data,"","portrait");
        //     if(!$portrait_path){
        //         //上传失败
        //         jsonReturn('002',null,"头像上传失败！");
        //         return;
        //     }
        // }else{
        //     $portrait_path = $portrait_data;
        // }
        // if(preg_match("/^data:image\/(jpeg|png|gif);base64,$/",$portrait_data)){
        //     $portrait_path = saveBase64Img($portrait_data,"","portrait");
        //     if(!$portrait_path){
        //         //上传失败
        //         jsonReturn('002',null,"头像上传失败！");
        //         return;
        //     }
        // }else{
        //     $portrait_path = $portrait_data;print_r($portrait_path);exit;
        // }
        $portrait_path = saveBase64Img($portrait_data,"","portrait");
        $update_data = [
            'id'         => $user_id,
            'email'      => $email,
            'website'    => trim($data['website']),
            'college'    => trim($data['college']),
            'major'      => trim($data['major']),
            'professional'      => trim($data['professional']),
            'resume'      => trim($data['resume']),
            'portrait'   => $portrait_path,
            'sex'        => $data['sex']
        ];

//        $map = [
//            'id' => $user_id
//        ];
//
//        $orginImg = UserModel::get($map)->value('portrait');

        $num = Db::name('user')->update($update_data);

        if($num){
//            if(!preg_match("/([a-zA-Z\/]+)moren.png$/",$orginImg)){
//                @unlink($orginImg);
//            }
            jsonReturn('001',$num,'更新成功！');
            return;
        }
        jsonReturn('002',null,'更新失败！');
    }

    /**
     * 上传头像
     */
//    public function upUserPortrait() {
//        upload();
//    }

    /**
     * 修改用户信息
     */
//    public function modifyUserInfo(){
//        $id = input('post.id');
//        //验证是否在新增用户的时候选择了角色
//        $data = input('post.data/a');
//        $role = trim(input("post.data.role"));
//
//        if(!$role){
//            jsonReturn("002",null,"请选择权限！");
//            return;
//        }
//
//        $status = $data['status'];
//        switch($status) {
//            case '0':
//                $inschool = '在校';
//                break;
//            case '1':
//                $inschool = '离校';
//                break;
//            case '2':
//                $inschool = '其他';
//                break;
//        }
//
//        $phone = trim($data['phone']);
//        $email = trim($data['email']);
//
//        $update_data = [
//            'name'       => trim($data['name']),
//            'role_id'    => $role,
//            'sex'        => trim($data['gender']),
//            'phone'      => $phone,
//            'email'      => $email,
//            'user_uid'   => trim($data['userID']),
//            'inschool'    => $inschool
//        ];
//
//
//        $result = $this->validate(
//            [
//                'phone'  => $phone,
//                'email' => $email,
//            ],
//            [
//                'phone|手机号'  => 'number|length:11',
//                'email|邮箱'   => 'email',
//            ]);
//        if(true !== $result){
//            // 验证失败 输出错误信息
//            jsonReturn('002',null,$result);
//            return;
//        }
//
//        $map = [
//            'id' => $id
//        ];
//
//        $num = UserModel::where($map)->update($update_data);
//
//        if($num){
//            jsonReturn('001',$num,'更新成功！');
//            return;
//        }
//        jsonReturn('002',null,'更新失败！');
//    }

    /**
     * 批量导入、导出用户信息
     */
    //用户excel表格模板下载
//    public function downloadExcelUser()
//    {
//        BaseTemplate('usertable.xlsx','用户信息表');
//    }

    //批量导入用户信息-文件上传
    public function uploadUser()
    {
        upload();
    }

    //批量导入用户信息-写入数据库
    public function inserExcelUser()
    {
        Loader::import('PHPExcel.Classes.PHPExcel');
        Loader::import('PHPExcel.Classes.PHPExcel.IOFactory.PHPExcel_IOFactory');
        Loader::import('PHPExcel.Classes.PHPExcel.Reader.Excel5');
        $data = input('post.');

        if(!$data || !$data['url'] || !$data['name']){
            jsonReturn("002",null,"请先上传文件！");
            return;
        }

        //获取文件路径
        $file_name = ROOT_PATH. $data['url'];

        $objReader =\PHPExcel_IOFactory::createReader('Excel2007');
        $obj_PHPExcel =$objReader->load($file_name, $encode = 'utf-8');  //加载文件内容,编码utf-8
        $excel_array=$obj_PHPExcel->getsheet(0)->toArray();   //转换为数组格式

        //判断上传表格是否为用户表
        $user_name = array('姓名','一卡通号','角色');

        foreach ($excel_array[0] as $k => $v) {
            if(empty($v)){
                unset($excel_array[0][$k]);
            }
        }

        $role = Db::name('role')->column('id','name');
        if($excel_array[0] === $user_name){
            array_shift($excel_array);  //删除第一个数组(标题);
            $insert_user = [];
            $insert_corr = [];

            Db::startTrans();
            try{
                foreach($excel_array as $k=>$v) {
                    $role_id = $role[$v[2]];

                    //用户信息插入数据库
                    if((int)$role_id === 3){
                        $insert_user[$k]['teacher_num'] = $v[1];
                    }

                    $insert_user[$k]['name']       = $v[0];         //姓名
                    $insert_user[$k]['user_uid']   = $v[1];         //一卡通账号
                    $insert_user[$k]['portrait']   = 'uploads/portrait/moren.png';            //头像


                    //验证一卡通好是否已经存在
                    $check = Db::name('user')->where('user_uid',$v[1])->select();
                    if($check){
                        $n = $k+1;
                        jsonReturn('002',null,"数据导入失败，该表中第{$n}行用户已经存在！");
                        exit;
                    }
                    else{
                        $id = Db::name('user')->insertGetId($insert_user[$k]); //批量插入数据
                        $insert_corr[] = [
                            'user_id' => $id,
                            'role_id' => $role_id
                        ];
                    }
                }

                Db::name('corr_user_role')->insertAll($insert_corr);
                Db::commit();
                jsonReturn('001',null,"数据导入成功！");
            }catch (Exception $e) {
                Db::rollback();
                jsonReturn('002',null,"数据存储失败！");
            }

        }else{
            jsonReturn('002',null,"上传文件不是用户信息表！");
        }
    }





    /**
     * 学生用户批量导入
     */
    //批量导入学生用户信息-文件上传
    public function uploadStudentUser()
    {
        upload();
    }

    //批量导入学生用户信息-写入数据库
    public function inserExcelstudentUser()
    {
        Loader::import('PHPExcel.Classes.PHPExcel');
        Loader::import('PHPExcel.Classes.PHPExcel.IOFactory.PHPExcel_IOFactory');
        Loader::import('PHPExcel.Classes.PHPExcel.Reader.Excel5');
        $data = input('post.');

        if(!$data || !$data['url'] || !$data['name']){
            jsonReturn("002",null,"请先上传文件！");
            return;
        }

        //获取文件路径
        $file_name = ROOT_PATH. $data['url'];

        $objReader =\PHPExcel_IOFactory::createReader('Excel2007');
        $obj_PHPExcel =$objReader->load($file_name, $encode = 'utf-8');  //加载文件内容,编码utf-8
        $excel_array=$obj_PHPExcel->getsheet(0)->toArray();   //转换为数组格式

        //判断上传表格是否为用户表
        $user_name = array('姓名','一卡通号','课程编号','课程名称');

        foreach ($excel_array[0] as $k => $v) {
            if(empty($v)){
                unset($excel_array[0][$k]);
            }
        }
        $user_student = Db::name('user_student');
        $lab_id = Session::get('user_infocas.labid');
        if($excel_array[0] === $user_name){
            array_shift($excel_array);  //删除第一个数组(标题);
            $install = [];
            foreach($excel_array as $k=>$v){
                $install[$k]['name'] = $v[0];               //姓名
                $install[$k]['user_uid'] = $v[1];           //一卡通号
                $install[$k]['curriculum_num'] = $v[2];     //课程编号
                $install[$k]['curriculum_name'] = $v[3];    //课程名称
                $install[$k]['lab_id'] = $lab_id;           //实验室id

                $sel = $user_student->where($install[$k])->find();
                if($sel){
                    $result[$k] = $user_student->where('id',$sel['id'])->update($install[$k]);
                }else{
                    $result[$k] = $user_student->insert($install[$k]);
                }

            }
            if($result){
                jsonReturn('001',null,'批量导入成功！');
            }else{
                jsonReturn('002',null,'批量导入失败！');
            }
        }else{
            jsonReturn('002',null,"上传文件不是学生用户信息表！");
        }
    }



    /**
     * 学生用户管理
     * 
     */
    //加载学生用户列表数据
    public function loadStudentUserList()
    {
        $curriculum_id = input('curriculum_id');
        $ret = Db::name('start_curriculum')->where('id',$curriculum_id)->find();

        $map = array(
            'curriculum_num'  => $ret['curriculum_num'],
            'curriculum_name' => $ret['curriculum_name']
        );
        $result = Db::name('user_student')->where($map)->select();
        if($result){
            jsonReturn('001',$result);
        }else{
            jsonReturn('002',null,'未查学生信息，请添加！');
        }


    }

    //删除学生信息
    public function deleteStudentUser()
    {
        $id = input('post.id');
        $ret = Db::name('user_student')->where('id',$id)->delete();
        if($ret){
            jsonReturn('001','删除成功！');
        }else{
            jsonReturn('002','删除失败！');
        }
    }




}