<?php
/**
 * Created by PhpStorm.
 * User: 高笛淳
 * Date: 2017/10/13
 * Time: 9:18
 */

namespace app\index\controller;

use app\common\controller\BaseController;
use app\index\model\Role as RoleModel;
use app\index\model\Rule as RuleModel;

class Permission extends BaseController
{
    /**
     * 角色权限poptip列表
     */
    public function ruleList()
    {
        $col = RoleModel::all();
        $tmp = [];
        if($col){
            foreach ($col as $k => $v) {
                array_push($tmp,$v->toArray());
            }
        }

        $rules = self::getRule($tmp);

        jsonReturn('001',$rules);
    }

    /**
     * 获取权限
     * @param $ruleArr
     * @return array
     */
    protected static function getRule($ruleArr){
        $model = new RuleModel();

        $rules = [];
        $index = '';
        $id = '';
        foreach ($ruleArr as $k => $v) {
            $result = [];
            $field = 'id,pid,name';
            $map = [
                'id' => array('in',$v['rules'])
            ];

            //权限数组
            $resArr = $model->where($map)->field($field)->select();

            foreach ($resArr as $key => $value) {
                $result[] = $value->toArray();
            }

            $re = self::deal($result);
            $rule = [];
            $rule['id'] = $v['id'];
            $rule['name'] = $v['name'];
            $rule['portrayal'] = $re;

            $rules[] = $rule;
        }

        return $rules;
    }

    /**
     * 处理权限数组
     * @param $param
     * @return array
     */
    protected static function deal($param){

        $parent = [];
        $result = [];

        foreach ($param as $key => $value) {
            if($value['pid'] == 0){
                $parent[] = $value;
                unset($param[$key]);
            }
        }

        foreach ($parent as $k => $v) {
            $child = [];
            foreach ($param as $kk => $vv) {
                if($v['id'] == $vv['pid']){
                    $child[] = $vv['name'];
                    unset($param[$kk]);
                }
            }
            $result[$k]['title'] = $parent[$k]['name'];
            $result[$k]['cent'] = $child;
            unset($parent[$k]);

        }

        return $result;
    }



    /**
     * 角色权限编辑时，当前角色的权限
     */
    public function editRuleList()
    {
        $id = input('id');
        $col = RoleModel::get($id);
        $arr = $col->toArray();

        $role_name = $arr['name'];
        $rule_ids = explode(',',$arr['rules']);


        $col = RoleModel::all();
        $tmp = [];
        if($col){
            foreach ($col as $k => $v) {
                array_push($tmp,$v->toArray());
            }
        }
        //权限id
        $ruleArr = array_column($tmp,'rules');

        $str = '';
        foreach ($ruleArr as $k => $v) {
            $str .= ','.$v;
        }
        $rules = explode(',',$str);
        $rules = array_unique($rules);

        foreach ($rules as $index => $item) {
            if(empty($item)){
                unset($rules[$index]);
            }
        }
        $ids = implode(',',$rules);

        $model = new RuleModel();

            $field = 'id,pid,name';
            $map = [
                'id' => array('in',$ids)
            ];

            //权限数组
            $resArr = $model->where($map)->field($field)->select();

            foreach ($resArr as $key => $value) {
                $param[] = $value->toArray();
            }

        $result = [];
        $parent = [];
        foreach ($param as $key => $value) {
            if($value['pid'] == 0){
                $parent[] = $value;
                unset($param[$key]);
            }
        }

        foreach ($parent as $k => $v) {
            $child = [];
            foreach ($param as $kk => $vv) {
                if($v['id'] == $vv['pid']){

                    $vv['title'] = $vv['name'];
                    unset($vv['pid']);
                    unset($vv['name']);
                    if(in_array($vv['id'],$rule_ids)){
                        $vv['checked'] = true;
                    }else{
                        $vv['checked'] = false;
                    }
                    $child[] = $vv;
                    unset($param[$kk]);
                }
            }
            $parent[$k]['title'] = $parent[$k]['name'];
            unset($parent[$k]['pid']);
            unset($parent[$k]['name']);
            $result[$k] = $parent[$k];

            $result[$k]['children'] = $child;
            unset($parent[$k]);

        }

            $res = [
                'role_name' => $role_name,
                'menu'      => [
                    'id' => 0,
                    'title' => '菜单',
                    'expand' => true,
                    'children' => $result
                ]
            ];
            jsonReturn('001',$res);
    }


    /**
     * 修改权限
     */
    public function alterPermission()
    {
        $data = input('post.data/a');
        $id = input('post.id');
        sort($data);

        $rule = implode(',',$data);

        $rules = [
            'rules' => $rule
        ];

        $map = [
            'id' => $id
        ];

        $model = new RoleModel();

        $res = $model->save($rules,$map);

        if($res){
            jsonReturn('001',$res,'更新成功！');
        }elseif (0 === $res){
            jsonReturn('002',null,'没有更新数据！');
        }
        else{
            jsonReturn('002',null,'更新失败！');
        }
    }
}