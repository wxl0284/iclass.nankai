<?php
/**
 * Created by sublime 3.0
 * User: 愿得一人行
 * Date: 2017/10/12
 * Time: 11:56
 */

namespace app\index\controller;

use app\common\controller\BaseController;
use app\index\model\examinationStrategy as examinationStrategyModel;
use think\Db;

use think\Session;

class Theory extends BaseController
{

    /**
     * 理论测试-知识点
     */
    //知识点列表页
    public function knowledgeIndex()
    {
        $user_id = Session::get('user_info.user_id');

        $map = [
            'a.user_id' => $user_id
        ];

        $fields = 'a.knowledge_name,a.describe,b.curriculum_name,b.curriculum_num,a.id,a.curriculum_id';

        $res = Db::name('knowledge_point')
            ->alias('a')
            ->join('curriculum b', 'a.curriculum_id=b.id','left')
            ->field($fields)
            ->where($map)
            ->select();

        foreach($res as $k=>$v){
            $res[$k]['curr_name'] = $v['curriculum_name'] . '(' .$v['curriculum_num'] . ')';
        }
        if($res){
            jsonReturn('001',$res);
        }else{
            jsonReturn('002',null,"查询失败！");
        }
    }


//    //修改习题，知识点列表
//    public function knowledgeList()
//    {
//        $exercises_id = input('post.exercises_id',6);
//
//        $map = [
//            'a.id'  =>  $exercises_id
//        ];
//
//        $fields = 'b.id,knowledge_name';
//
//        $res = Db::name('exercises')
//            ->alias('a')
//            ->join('nk_knowledge_point b','a.curriculum_id=b.curriculum_id')
//            ->field($fields)
//            ->where($map)
//            ->select();
//
//        if($res){
//            jsonReturn('001',$res);
//        }else{
//            jsonReturn('002',null,"查询失败！");
//        }
//    }


    /**
     * 课程关联的知识点列表
     */
    public function knowledgeCurrCorrList()
    {
        $id = input('post.curriculum_id');
        $map = [
            'curriculum_id' => $id
        ];

        $res = Db::name('knowledge_point')
            ->field('id,knowledge_name')
            ->where($map)
            ->select();

        if($res){
            jsonReturn('001',$res);
        }else{
            jsonReturn('002',null,"查询失败！");
        }
    }


    /**
     * 修改习题库关联课程的知识点
     */
    public function AlterCorrList()
    {
        $id = input('post.exercises_id');

        $map = [
            'a.id' => $id
        ];

        $res = Db::name('exercises')
            ->alias('a')
            ->join('nk_knowledge_point b','a.curriculum_id=b.curriculum_id')
            ->where($map)
            ->field('b.id, b.knowledge_name')
            ->select();

        if($res){
            jsonReturn('001',$res);
        }else{
            jsonReturn('002',null,"查询失败！");
        }
    }




    //新增知识点    
    public function knowledgeAdd()
    {
        $data = input('post.data/a');
        $user_id = Session::get('user_info.user_id');
        $curriculum_id   = $data['curriculum_id'];     //课程id
        $knowledge_name  = $data['pointname'];     //知识点名称
//        $pid             = empty($data['fatherpoint'])?0:$data['fatherpoint'];   //父类知识点id
        $describe        = $data['pointdescribe']; //描述

        //验证知识点名称是否存在
        $knowledge = Db::name('knowledge_point');
        $ret = $knowledge->where('knowledge_name', $knowledge_name)->select();
        if($ret){
            jsonReturn('002',null,"该知识点已经存在！");
        }else{
            $add = [
                'curriculum_id'  => $curriculum_id,
                'knowledge_name'  => $knowledge_name,
//                'pid'             => $pid,
                'user_id'        => $user_id,
                'describe'        => $describe,
                'create_time'     => date("Y-m-d H:i:s"),
                'update_time'     => date("Y-m-d H:i:s"),
            ];
            $result = $knowledge->insert($add);
            if($result){
                jsonReturn('001',null,'添加成功！');
            }else{
                jsonReturn('002',null,"添加失败！");
            }
        }

    }


    //修改知识点-修改显示
    public function knowledgeEdit()
    {
        $id = input('post.id');

        $map = [
            'a.id' => $id
        ];

        $fields = 'a.knowledge_name,a.describe,concat(b.curriculum_name,b.curriculum_num) as curr_name,a.id,a.curriculum_id';

        $res = Db::name('knowledge_point')
            ->alias('a')
            ->join('curriculum b', 'a.curriculum_id=b.id','left')
            ->field($fields)
            ->where($map)
            ->find();

        if($res){
            jsonReturn('001',$res);
        }else{
            jsonReturn('002',null,"查询失败！");
        }
    }


    //修改知识点-更新信息
    public function knowledgeSave()
    {
        $data            = input('post.data/a');
        $id              = input('post.id');

        $curriculum_id  = $data['curriculum_id'];          //课程id
        $knowledge_name  = $data['pointname'];          //知识点名称
//        $pid             = $data['fatherpoint'];        //父类知识点id
        $describe        = $data['pointdescribe'];      //描述

        //验证知识点名称是否存在
        $knowledge = Db::name('knowledge_point');
        $ret = $knowledge->where('id','<>', $id)->where('knowledge_name', $knowledge_name)->select();
        if($ret){
            jsonReturn('002',null,"该知识点已经存在！");exit;
        }else{
            $save = [
                'curriculum_id'  => $curriculum_id,
                'knowledge_name'  => $knowledge_name,
//                'pid'             => $pid,
                'describe'        => $describe,
                'update_time'     => date("Y-m-d H:i:s"),
            ];
            $result = $knowledge->where('id', $id)->update($save);
            if($result){
                jsonReturn('001',null,"更新成功！");
            }else{
                jsonReturn('002',null,"更新失败！");
            }
        }
    }


    //删除知识点
    public function knowledgeDelete()
    {
        $id = input('post.id/a');
        $ret = Db::name('knowledge_point')->where('id','in',$id)->delete();
        if($ret){
            jsonReturn('001',null,"删除成功！");
        }else{
            jsonReturn('002',null,"删除失败！");
        }
    }

    //知识点搜索查询
    public function knowledgeQuery()
    {
        $curriculum_id  = input('post.curriculum_id');     //课程名称
        if($curriculum_id) {
            $map = [
                'a.curriculum_id' =>  $curriculum_id
            ];
        }else{
            $map = [];
        }

        $fields = 'a.knowledge_name,a.describe,concat(b.curriculum_name,b.curriculum_num) as curr_name,a.id,a.curriculum_id';

        $res = Db::name('knowledge_point')
            ->alias('a')
            ->join('curriculum b', 'a.curriculum_id=b.id','left')
            ->field($fields)
            ->where($map)
            ->select();

        if($res){
            jsonReturn('001',$res);
        }else{
            jsonReturn('002',null,"没有匹配的知识点！");
        }
    }



    /**
     * 习题库管理
     */
    //查看习题库列表
    public function exerciseIndex()
    {
        $user_id = Session::get('user_info.user_id');

        $map = [
            'a.user_id' => $user_id
        ];

        $fields = 'a.id,a.exercises_type,a.subject,a.help,b.curriculum_name,b.curriculum_num';

        $res = Db::name('exercises')
            ->alias('a')
            ->join('nk_curriculum b','b.id=a.curriculum_id')
            ->field($fields)
            ->where($map)
            ->select();
        foreach($res as $k=>$v){
            $res[$k]['curr_name'] = $v['curriculum_name'] . '(' .$v['curriculum_num'] . ')';
        }
        if($res){
            jsonReturn('001',$res);
        }else{
            jsonReturn('002',null,"查询失败！");
        }
    }

    //新增习题
    public function exerciseAdd()
    {
        
        $data  = input('post.data/a');

        $user_id = Session::get('user_info.user_id');

        //单选题
        if($data['exertype'] == "单选"){
            $datas = input('post.datas/a');
            $option =implode('&;&', array_column($datas['items'], 'value'));  //试题选项以&;&符号区分
            $add = [
                'user_id'           => $user_id,
                'exercises_type'     => $data['exertype'],      //习题类型
                'proposed_score'     => $data['score'],         //建议分数
                'curriculum_id'      => $data['subcur'],        //所属课程id
                'cognition_type'     => $data['cogtype'],       //认知类型
                'problem_difficulty' => $data['problem'],       //习题难度
                'knowledge_id'       => $data['fatherpoint'],   //所属知识点ID
                'subject'            => $data['titleContent'],  //题目内容
                'help'               => $data['notes'],         //注释/帮助
                'option'             => $option,                //选项
                'answer'             => $data['anjudge'],       //答案
                'create_time'        => date("Y-m-d H:i:s"),
                'update_time'        => date("Y-m-d H:i:s"),
            ];
        }

        //多选题
        if($data['exertype'] == "多选"){
            $dataz = input('post.dataz/a');
            $option =implode('&;&', array_column($dataz['itemzs'], 'values'));  //试题选项以&;&符号区分
            $add = [
                'user_id'           => $user_id,
                'exercises_type'     => $data['exertype'],      //习题类型
                'proposed_score'     => $data['score'],         //建议分数
                'curriculum_id'      => $data['subcur'],        //所属课程id
                'cognition_type'     => $data['cogtype'],       //认知类型
                'problem_difficulty' => $data['problem'],       //习题难度
                'knowledge_id'       => $data['fatherpoint'],   //所属知识点ID
                'subject'            => $data['titleContent'],  //题目内容
                'help'               => $data['notes'],         //注释/帮助
                'option'             => $option,                //选项
                'answer'             => $data['anjudge'],       //答案
                'create_time'        => date("Y-m-d H:i:s"),
                'update_time'        => date("Y-m-d H:i:s"),
            ];
        }

        //判断题
        if($data['exertype'] == "判断"){
            $dataz = input('post.dataz/a');
            $option =implode('&;&', array_values($dataz));  //试题选项以&;&符号区分
            $add = [
                'user_id'           => $user_id,
                'exercises_type'     => $data['exertype'],      //习题类型
                'proposed_score'     => $data['score'],         //建议分数
                'curriculum_id'      => $data['subcur'],        //所属课程id
                'cognition_type'     => $data['cogtype'],       //认知类型
                'problem_difficulty' => $data['problem'],       //习题难度
                'knowledge_id'       => $data['fatherpoint'],   //所属知识点ID
                'subject'            => $data['titleContent'],  //题目内容
                'help'               => $data['notes'],         //注释/帮助
                'option'             => $option,                //选项
                'answer'             => $data['anjudge'],       //答案
                'create_time'        => date("Y-m-d H:i:s"),
                'update_time'        => date("Y-m-d H:i:s"),
            ];
        }

        $ret = Db::name('exercises')->insert($add);
        if($ret){
            jsonReturn('001',null,"新增成功！");
        }else{
            jsonReturn('002',null,"新增失败！");
        }

    }


    //修改习题-修改显示
    public function exerciseEdit()
    {
        $id = input('post.id');

        $map = [
            'a.id' => $id
        ];

        $fields = 'a.id,a.exercises_type,a.subject,a.help,a.proposed_score,a.cognition_type,a.problem_difficulty,a.answer,a.option,a.curriculum_id,a.knowledge_id';

        $res = Db::name('exercises')
            ->alias('a')
            ->join('nk_curriculum b','b.id=a.curriculum_id')
            ->field($fields)
            ->where($map)
            ->find();

        $res['option'] = explode('&;&',$res['option']);

        if($res){
            jsonReturn('001',$res);
        }else{
            jsonReturn('002',null,"查询失败！");
        }

    }


    //修改习题-更新习题
    public function exerciseSave()
    {
        $data  = input('post.data/a');
        $user_id = Session::get('user_info.user_id');
        $id = $data['id'];
        //单选题
        if($data['exercises_type'] == "单选"){
            $save = [
                'user_id'           => $user_id,
                'exercises_type'     => $data['exercises_type'],         //习题类型
                'proposed_score'     => $data['proposed_score'],         //建议分数
                'curriculum_id'      => $data['curriculum_id'],        //所属课程id
                'cognition_type'     => $data['cognition_type'],         //认知类型
                'problem_difficulty' => $data['problem_difficulty'],     //习题难度
                'knowledge_id'       => $data['knowledge_id'],           //所属知识点ID
                'subject'            => $data['subject'],                //题目内容
                'help'               => $data['help'],                   //注释/帮助
                'option'             => implode('&;&', $data['option']), //选项
                'answer'             => $data['answer'],                 //答案
                'update_time'        => date("Y-m-d H:i:s"),
            ];
        }

        //多选题
        if($data['exercises_type'] == "多选"){
            $save = [
                'user_id'           => $user_id,
                'exercises_type'     => $data['exercises_type'],         //习题类型
                'proposed_score'     => $data['proposed_score'],         //建议分数
                'curriculum_id'      => $data['curriculum_id'],         //所属课程id
                'cognition_type'     => $data['cognition_type'],         //认知类型
                'problem_difficulty' => $data['problem_difficulty'],     //习题难度
                'knowledge_id'       => $data['knowledge_id'],           //所属知识点ID
                'subject'            => $data['subject'],                //题目内容
                'help'               => $data['help'],                   //注释/帮助
                'option'             => implode('&;&', $data['option']), //选项
                'answer'             => $data['answer'],                 //答案
                'update_time'        => date("Y-m-d H:i:s"),
            ];
        }

        //判断题
        if($data['exercises_type'] == "判断"){
            $save = [
                'user_id'           => $user_id,
                'exercises_type'     => $data['exercises_type'],         //习题类型
                'proposed_score'     => $data['proposed_score'],         //建议分数
                'curriculum_id'      => $data['curriculum_id'],         //所属课程id
                'cognition_type'     => $data['cognition_type'],         //认知类型
                'problem_difficulty' => $data['problem_difficulty'],     //习题难度
                'knowledge_id'       => $data['knowledge_id'],           //所属知识点ID
                'subject'            => $data['subject'],                //题目内容
                'help'               => $data['help'],                   //注释/帮助
                'option'             => implode('&;&', $data['option']), //选项
                'answer'             => $data['answer'],                 //答案
                'update_time'        => date("Y-m-d H:i:s"),
            ];
        }

        $ret = Db::name('exercises')->where('id',$id)->update($save);
        if($ret){
            jsonReturn('001',null,"更新成功！");
        }else{
            jsonReturn('002',null,"更新失败！");
        }
    }


    //删除习题
    public function exerciseDelete()
    {
        $id = input('post.id/a');
        $ret = Db::name('exercises')->where('id','in', $id)->delete();
        if($ret){
            jsonReturn('001',null,"删除成功！");
        }else{
            jsonReturn('002',null,"删除失败！");
        }

    }


    /**
     * 试卷策略管理
     */
    //查看试卷策略列表
    public function teststrategyIndex()
    {
        $fields = 'a.id,a.strategy_name,a.update_time,b.curriculum_name,b.curriculum_num,c.name as setters';

        if(!Session::has('user_info.user_id')){
            jsonReturn('002',null,"查询失败！");
        }else{
            $map = [
                'setters_id' => Session::get('user_info.user_id')
            ];

            $res = Db::name('base_teststrategy')
                ->alias('a')
                ->join('nk_curriculum b','a.curriculum_id=b.id')
                ->join('nk_user c','c.id=a.setters_id')
                ->field($fields)
                ->where($map)
                ->select();
            foreach($res as $k=>$v){
                $res[$k]['curr_name'] = $v['curriculum_name'] . '(' .$v['curriculum_num'] . ')';
            }
            if($res){
                jsonReturn('001',$res);
            }else{
                jsonReturn('002',null,"查无结果！");
            }
        }

    }

    /**
     * 修改试卷关联策略
     */
    public function CorrteststrategyIndex()
    {
        $id = input('post.id');
        $map = [
            'a.id' => $id
        ];
        $fields = 'b.strategy_name,b.id';

        $res = Db::name('test_paper')
            ->alias('a')
            ->join('nk_base_teststrategy b','a.curriculum_id=b.curriculum_id')
            ->where($map)
            ->field($fields)
            ->select();

        if($res){
            jsonReturn('001',$res);
        }else{
            jsonReturn('002',null,"查询失败！");
        }
    }

    /**
     * 修改试卷，课程关联策略
     */
    public function alterCurrStrategy() {
        $id = input('post.curriculum_id');
        $map = [
            'curriculum_id' => $id
        ];
        $fields = 'strategy_name,id';

        $res = Db::name('base_teststrategy')

            ->where($map)
            ->field($fields)
            ->select();

        if($res){
            jsonReturn('001',$res);
        }else{
            jsonReturn('002',null,"查询失败！");
        }
    }


    //新增试卷策略
    public function teststrategyAdd()
    {
        try{
            $data  = input('post.data/a');    //接收课程名称和策略名称
            $datas = input('post.datas/a');    //接收策略条目数

            $baseTeststrategyModel     = Db::name('base_teststrategy');    //实例化基础试卷策略表
            $examinationStrategyModel  = Db::name('examination_strategy'); //实例化试卷策略条目表
            $exercisesModel            = Db::name('exercises');

            $baseTeststrategyModel->startTrans();                         //开启数据库事务
            //查询该课程下的策略名称是否存在
            $ret = $baseTeststrategyModel->where('strategy_name', $data['strategy_name'])->select();
            if($ret){
                jsonReturn('002',null,"该策略名称已经存在！");
            }else{
                if(!Session::has('user_info.user_name')){
                    jsonReturn('002',null,"新增策略失败！");
                }else{
                    //向基础试卷策略表中添加数据
                    $setters = Session::get('user_info.user_id');
                    $add = [
                        'curriculum_id'   => $data['curriculum_id'],                   //课程id
                        'strategy_name'   => $data['strategy_name'],                    //策略名称
                        'create_time'     => date("Y-m-d H:i:s"),                       //创建时间
                        'update_time'     => date("Y-m-d H:i:s"),                       //修改时间
                        'setters_id'      => $setters,                                  //制定人id
                    ];
                    $baseTeststrategyModel->insert($add);
                    $strategyID = $baseTeststrategyModel->getLastInsID();
                    if(empty($strategyID)){
                        jsonReturn('002',null,"新增策略失败！");
                    }else{
                        $wait = [];

                        foreach ($datas as $k => $v){
                            $cond  = [
                                'curriculum_id' => $data['curriculum_id'],
                                'knowledge_id' => $v['knowledge_id'],
                                'exercises_type' => $v['exercises_type'],
                                'problem_difficulty' => $v['problem_difficulty']
                            ];

                            $num = $exercisesModel->where($cond)->count();
                            if($num<$v['questions_num']){
                                jsonReturn('002',null,"您所添加的第".($k+1)."个策略条目，抽题数量超过题库中的数量！！");
                                exit;
                            }else{
                                $v['strategy_id'] = $strategyID;
                                $v['create_time'] = date("Y-m-d H:i:s");
                                $v['update_time'] = date("Y-m-d H:i:s");
                                $wait[] = $v;
                            }
                        }

                        //批量插入试卷策略条目
                        $result[] = $examinationStrategyModel->insertAll($wait);
                        if(empty($result)){
                            $baseTeststrategyModel->rollback();     //事务回调
                            jsonReturn('002',null,"新增策略失败！");
                        }else{
                            $baseTeststrategyModel->commit();       //提交事务
                            jsonReturn('001',null,"新增策略成功！");
                        }
                    }
                }
            }
        }catch (\Exception $e)
        {
            echo $e;
        }

    }


    //修改试卷策略-修改显示
    public function teststrategyEdit()
    {
        $id = input('post.id');

        $res = Db::name('base_teststrategy')->field('id,strategy_name,curriculum_id')->where('id', $id)->find();

        $curr = Db::name('examination_strategy')->field('id as sub_id,knowledge_id,exercises_type,problem_difficulty,questions_num')->where('strategy_id',$res['id'])->select();


        $res['data'] = $curr;

        if($res){
            jsonReturn('001',$res);
        }else{
            jsonReturn('002',null,"查询失败！");
        } 
    }

    /**
     * 修改策略 知识点
     */
    public function CorrStrategyKnowledge()
    {
        $id = input('post.id');

        $curriculum_id = Db::name('base_teststrategy')
            ->where('id',$id)
            ->value('curriculum_id');

        $res = Db::name('knowledge_point')
            ->field('id,knowledge_name')
            ->where('curriculum_id',$curriculum_id)
            ->select();

        if($res){
            jsonReturn('001',$res);
        }else{
            jsonReturn('002',null,"查询失败！");
        }
    }


    //修改试卷策略-更新策略
    public function teststrategySave()
    {
        $id = input('post.id');
        $data  = input('post.data/a');    //接收课程名称和策略名称
        $datas = input('post.datas/a');    //接收策略条目数

        $baseTeststrategyModel     = Db::name('base_teststrategy');    //实例化基础试卷策略表
        $examinationStrategyModel  = new examinationStrategyModel(); //实例化试卷策略条目表
        $exercisesModel            = Db::name('exercises');

        $baseTeststrategyModel->startTrans();                         //开启数据库事务
        //查询该课程下的策略名称是否存在
        $ret = $baseTeststrategyModel
            ->where('id','<>', $id)
            ->where('strategy_name', $data['strategy_name'])
            ->select();
        if($ret){
            jsonReturn('002',null,"该策略名称已经存在！");
        }else{

            //向基础试卷策略表中添加数据
            $setters = Session::get('user_info.user_id');
            $save = [
                'setters_id'      => $setters,                                  //制定人id
                'curriculum_id'   => $data['curriculum_id'],                    //课程id
                'strategy_name'   => $data['strategy_name'],                    //策略名称
                'update_time'     => date("Y-m-d H:i:s"),                       //修改时间
            ];
            $res = $baseTeststrategyModel->where('id',$id)->update($save);

            if(empty($res)){
                jsonReturn('002',null,"修改策略失败！");
            }else{
                $update = [];
                $insert = [];

                foreach ($datas as $k => $v){
                    $cond  = [
                        'curriculum_id'      => $data['curriculum_id'],
                        'knowledge_id'       => $v['knowledge_id'],
                        'exercises_type'     => $v['exercises_type'],
                        'problem_difficulty' => $v['problem_difficulty']
                    ];

                    $num = $exercisesModel->where($cond)->count();
                    if($num<$v['questions_num']){
                        jsonReturn('002',null,"您所添加的第".($k+1)."个策略条目，抽题数量超过题库中的数量！！");
                        exit;
                    }else{
                        if($v['sub_id']){
                            $update[] = [
                                'id'                 => $v['sub_id'],
                                'knowledge_id'       => $v['knowledge_id'],
                                'exercises_type'     => $v['exercises_type'],
                                'problem_difficulty' => $v['problem_difficulty'],
                                'questions_num'      => $v['questions_num'],
                                'update_time'        => date("Y-m-d H:i:s")
                            ];
                        }else{
                            $insert[] = [
                                'strategy_id'        => $id,
                                'knowledge_id'       => $v['knowledge_id'],
                                'exercises_type'     => $v['exercises_type'],
                                'problem_difficulty' => $v['problem_difficulty'],
                                'questions_num'      => $v['questions_num'],
                                'create_time'     => date("Y-m-d H:i:s"),
                                'update_time'     => date("Y-m-d H:i:s")
                            ];
                        }
                    }
                }

                //批量插入试卷策略条目
                $coli = $examinationStrategyModel->saveAll($insert);
                $colu = $examinationStrategyModel->saveAll($update);
                if(empty($coli) && empty($colu)){
                    $baseTeststrategyModel->rollback();     //事务回调
                    jsonReturn('002',null,"修改策略失败！");
                }else{
                    $baseTeststrategyModel->commit();       //提交事务
                    jsonReturn('001',null,"修改策略成功！");
                }
            }
        }

    }

    //单个删除试卷策略
    public function teststrategyDelete()
    {
        
        $id = input('post.id');   //基础策略id
        $base_teststrategy    = Db::name('base_teststrategy');    //实例化基础试卷策略表
        $examination_strategy = Db::name('examination_strategy'); //实例化试卷策略条目表
        $examination_strategy->startTrans();                      //开启数据库事务

        //删除试卷策略条目表
        $ret = $examination_strategy->where('strategy_id','in', $id)->delete();
        //删除基础试卷策略表
        if($ret){
            $rut = $base_teststrategy->where('id', $id)->delete();
            if(empty($rut)){
                $examination_strategy->rollback();     //事务回调
            }else{
                $examination_strategy->commit();       //提交事务
                jsonReturn('001',null,"删除成功！");
            }
        }else{
            $examination_strategy->rollback();         //事务回调
            jsonReturn('002',null,"删除失败！");
        }
    }





    /**
     * 试卷管理
     */
    //查看试卷管理列表
    public function testmanageIndex()
    {
        $user_id = Session::get('user_info.user_id');

        $map = [
            'a.user_id' => $user_id
        ];

        $fields = 'a.id,a.test_name,a.release,b.strategy_name,c.curriculum_name,c.curriculum_num';

        $res = Db::name('test_paper')
            ->alias('a')
            ->join('nk_base_teststrategy b','a.strategy_id=b.id')
            ->join('nk_curriculum c','c.id=a.curriculum_id')
            ->field($fields)
            ->where($map)
            ->select();
        foreach($res as $k=>$v){
            $res[$k]['curr_name'] = $v['curriculum_name'] . '(' .$v['curriculum_num'] . ')';
        }
        if($res){
            jsonReturn('001',$res);
        }else{
            jsonReturn('002',null,"查询失败！");
        }
    }

    /**
     * 添加试卷，与课程关联的策略
     */
    public function CorrtestmanageIndex()
    {

        $curriculum_id = input('post.curriculum_id');

        $map = [
            'curriculum_id' => $curriculum_id
        ];
        $res = Db::name('base_teststrategy')->where($map)->field('id,strategy_name')->select();

        if($res){
            jsonReturn('001',$res);
        }else{
            jsonReturn('002',null,"查询失败！");
        }
    }


    //新增试卷
    public function testmanageAdd()
    {
        $data = input('post.data/a');  //接收数据
        $user_id = Session::get('user_info.user_id');
        //验证所属课程下的试卷名称是否已经存在（试卷名称要与实验名称一样）
        $test_paper = Db::name('test_paper');
        $ret = $test_paper->where('test_name', $data['test_name'])->select();
        if($ret){
            jsonReturn('002',null,"该试卷名称已经存在！");exit;
        }else{
            $add = [
                'user_id'         => $user_id,
                'test_name'       => $data['test_name'],        //试卷名称
                'strategy_id'     => $data['strategy_id'],    //所属策略
                'curriculum_id'   => $data['curriculum_id'],   //所属课程id
                'release'         => $data['animal'],           //是否发布考试
                'create_time'     => date("Y-m-d H:i:s"),
                'update_time'     => date("Y-m-d H:i:s"),
            ];

            $result = $test_paper->insert($add);
            if($result){
                jsonReturn('001',null,"新增试卷成功！");
            }else{
                jsonReturn('002',null,"新增试卷失败！");
            }
        }

    }



    //修改试卷-修改显示
    public function testmanageEdit()
    {
        $id = input('post.id');

        $map = [
            'id' => $id
        ];

        $fields = 'id,test_name,strategy_id,curriculum_id,release';

        $res = Db::name('test_paper')->where($map)->field($fields)->find();

        if($res){
            jsonReturn('001',$res);
        }else{
            jsonReturn('002',null,"查询失败！");
        }
    }



    //修改试卷-更新试卷
    public function testmanageSave()
    {
        $data = input('post.data/a');  //接收数据
        $user_id = Session::get('user_info.user_id');
        //验证所属课程下的试卷名称是否已经存在（试卷名称要与实验名称一样）
        $test_paper = Db::name('test_paper');
        $ret = $test_paper->where('id','<>',$data['id'])->where('test_name', $data['test_name'])->select();
        if($ret){
            jsonReturn('002',null,"该试卷名称已经存在！");exit;
        }else{
            $save = [
                'user_id'         => $user_id,
                'test_name'       => $data['test_name'],        //试卷名称
                'strategy_id'     => $data['strategy_id'],    //所属策略
                'curriculum_id'   => $data['curriculum_id'],   //所属课程代码
                'release'         => $data['release'],          //是否发布考试
                'update_time'     => date("Y-m-d H:i:s"),
            ];

            $result = $test_paper->where('id',$data['id'])->update($save);
            if($result){
                jsonReturn('001',null,"修改试卷成功！");
            }else{
                jsonReturn('002',null,"修改试卷失败！");
            }
        }
    }


    //删除试卷
    public function testmanageDelete()
    {
        
        $id  = input('post.id/a');
        $ret =  Db::name('test_paper')->where('id','in', $id)->delete();
        if($ret){
            jsonReturn('001',null,"删除成功！");
        }else{
            jsonReturn('002',null,"删除失败！");
        }

    }



}
