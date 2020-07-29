<?php
/**
 * Created by PhpStorm.
 * User: 高笛淳
 * Date: 2017/8/30
 * Time: 9:28
 */

namespace app\index\controller;

use app\index\model\User as UserModel;
use app\index\model\Session as SessionModel;
use app\index\model\Rule as RuleModel;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;
use think\Cache;
use think\Log;
class Login extends Controller
{

     /**
     * 检测用户登录(后台)
     */
    public function AdminLogin(){
        $user_uid = trim(input('post.username'));
        $password = trim(input('post.password'));
        if($user_uid != 'admin'){
            jsonReturn('002',null,'账号不正确！');exit;
        }
        if($password != 'Nkvrlab2019'){
            jsonReturn('002',null,'密码不正确！');exit;
        }
        $map = [
            'user_uid'      => $user_uid,
        ];

        $user_arr = Db::name('user')->where($map)->find();

        //LDAP验证
//        $user_arr = LDAP::login();

        if(null == $user_arr){
            jsonReturn('002',null,'账号或密码错误！');
            return;
        }else{
            $auth = self::getAuth($user_arr['id']);
            if($auth){
                $datetime = date('Y-m-d H:i:s');
                $username = $user_arr['name'];
                $num = $user_arr['login_num'];
                Db::startTrans();
                try{
                    $data = [
                        'last_login_time' => $datetime,
                        'login_num' => $num+1
                    ];
                    Db::name('user')->where($map)->update($data);

                    $auth['user_info']['login_time'] = $datetime;
                    $auth['user_info']['user_name'] = $username;
                    $auth['user_info']['user_id'] = $user_arr['id'];
                    $auth['domain'] = 'https://'.$_SERVER['SERVER_NAME'];

                    $logArr = [
                        'ip' => $_SERVER['REMOTE_ADDR'],
                        'name' => $user_arr['name'],
                        'login_time' => $datetime
                    ];
                    Db::name('loginLog')->insert($logArr);
                    $insertId = Db::name('loginLog')->getLastInsID();
                    Session::set('log_id',$insertId);
                    Session::set('user_info.user_uid',$user_arr['user_uid']);
                    Session::set('user_info.user_id',$user_arr['id']);
                    Session::set('user_info.user_name',$user_arr['name']);
                    Db::commit();
                    jsonReturn('001',$auth,'登录成功！');
                }catch (\Exception $exception)
                {
                    Db::rollback();
                    jsonReturn('002',null,'登录失败！');
                }
            }else{
                jsonReturn('002',null,'没有访问权限！');
            }
        }
    }




    /**
     * 检测用户登录
     */
    public function checkLogin(){

        $user_info = session::get('user_infocas');
        $platform = input('post.platform');  //接收实验室名称
    
        //获取实验室名称
        $lab = Db::name('lab')->where('id',$platform)->field('name, manager')->find();

        //获取学生号或者教工号
        $user_uid = $user_info['studentNumber']?$user_info['studentNumber']:$user_info['teaching_number'];

        //判断此用户是否为此实验室负责人

        if ( $lab['manager'] )
        {
            $lab['manager'] = explode(',', $lab['manager']);
            if ( in_array($user_uid, $lab['manager']) )
            {
                $lab_admin = true;//是当前实验室负责人
            }else{
                $lab_admin = false;//不是当前实验室负责人
            }
        }else{
            $lab_admin = false;
        }

        //halt($lab_admin);
    
        //查询本地数据库是否有该用户
        $map = [
            'user_uid'      => $user_uid,
        ];
        $user_arr = Db::name('user')->where($map)->find();

        if(empty($user_arr)){
            /*将用户信息写入数据库*/
            //写入用户表
            $user = Db::name('user');
            $user_role = Db::name('corr_user_role');
            $user->startTrans();
            $isnertData = [
                'name' => $user_info['name'],
                'sex' => $user_info['genders'],
                'user_uid' => $user_info['teaching_number'] ? $user_info['teaching_number'] : $user_info['studentNumber'],
                'phone' => $user_info['phone'],
                'email' => $user_info['email'],
				'portrait' => 'uploads/portrait/moren.png',
                'create_time' => date("Y-m-d H:i:s"),
            ];
            $addUser = $user->insert($isnertData);
            $insertIDs = $user->getLastInsID();
            if(empty($insertIDs)){
                $user->rollback();
                header("Location: https://".$_SERVER['SERVER_NAME']."/api/caslogin");exit;
                //$this->redirect("https://".$_SERVER['SERVER_NAME']."/api/caslogin");
            }else{
                //写入权限配置表
                $roleData = [
                    'user_id' => $insertIDs,
                    'role_id' => $user_info['teaching_number'] ? '3' : '4',
                ];
                $roleInsert = $user_role->insert($roleData);
                if(empty($roleInsert)){
                    $user->rollback();
                    header("Location: https://".$_SERVER['SERVER_NAME']."/api/caslogin");exit;
                   // $this->redirect("https://".$_SERVER['SERVER_NAME']."/api/caslogin");
                }else{
                    //$user_arr['id'] = $insertIDs;
                    $where = [
                        'user_uid'      => $insertIDs,
                    ];
                    $user_arr = Db::name('user')->where($map)->find();
                    $user->commit();
                }
            }
        }

        //查询用户权限
        $auth = self::getAuth($user_arr['id'], $lab_admin);
        //halt($auth);

        //如果是'自主录播教室'，没有‘开课申请’这个功能，所以在此处删除$auth中的‘开课申请’ 和‘开课审批’
        if ( $platform == 3 )
        {
            //删除‘开课申请’
			if ( isset($auth['auth'][1]['child']) )
			{
				$temp = $auth['auth'][1]['child'];
           
				foreach ($temp as $k => $v)
				{
					$p = array_search('开课申请', $v);

					if ( $p !== false )
					{
						array_splice($temp, $k, 1); //删除‘开课申请’所在的数组项
					}
				}

				$auth['auth'][1]['child'] = $temp;
			}
            

            //删除‘开课审批’
			if ( isset($auth['auth'][4]['child']) )
			{
				$temp = $auth['auth'][4]['child'];
           
				foreach ($temp as $k => $v)
				{
					$p = array_search('开课审批', $v);

					if ( $p !== false )
					{
						array_splice($temp, $k, 1); //删除‘开课审批’所在的数组项
					}
				}

				$auth['auth'][4]['child'] = $temp;
			}
            
        }

        //halt()
        //删除$auth中的‘开课审批’和‘开课审批’结束
       
        if($auth){
            $datetime = date('Y-m-d H:i:s');
            $username = $user_arr['name'];
            $num = $user_arr['login_num'];
            Db::startTrans();
            try{
                $data = [
                    'last_login_time' => $datetime,
                    'login_num' => $num+1
                ];
                Db::name('user')->where($map)->update($data);

                $auth['user_info']['login_time'] = $datetime;
                $auth['user_info']['user_name'] = $username;
                $auth['user_info']['user_id'] = $user_arr['id'];
                $auth['domain'] = 'https://'.$_SERVER['SERVER_NAME'];
                $auth['lab_name'] = $lab['name'];

                $logArr = [
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'name' => $user_arr['name'],
                    'login_time' => $datetime
                ];
                Db::name('loginLog')->insert($logArr);
                $insertId = Db::name('loginLog')->getLastInsID();
                Session::set('log_id',$insertId);
                Session::set('user_info.user_uid',$user_arr['user_uid']);
                Session::set('user_info.user_id',$user_arr['id']);
                Session::set('user_info.user_name',$user_arr['name']);
                Session::set('user_infocas.labid', $platform);
                Db::commit();
                jsonReturn('001',$auth,'登录成功！');
                //header("Location: https://".$_SERVER['SERVER_NAME']."/web/index.html");
                //return $auth;
            }catch (\Exception $exception)
            {
                Db::rollback();
                // jsonReturn('002',null,'登录失败！');
               header("Location: https://".$_SERVER['SERVER_NAME']."/api/caslogin");exit;
               return ;
            }
        }else{
            // jsonReturn('002',null,'没有访问权限！');
            header("Location: https://".$_SERVER['SERVER_NAME']."/api/caslogin");exit;
        }
        

    }


    //获取用户信息 
    public function caslogin_wxl ()
    {
		// cas服务器登录地址
		$loginServer = "https://sso.nankai.edu.cn/sso/login";
		// cas服务器验证地址
		$validateServer = "https://sso.nankai.edu.cn/sso/serviceValidate";
		// cas服务器回调地址
		$address = "https://iclass.nankai.edu.cn/api/caslogin";
		//cas退出地址,这是注销地址，注销时请先注销自己本地session,service参数就是回调地址
		$casLogoutUrl="https://sso.nankai.edu.cn/sso/logout?service=https://iclass.nankai.edu.cn/web/index.html";
	   // 如果请求带有ticket
		if (isset($_REQUEST["ticket"]) && !empty($_REQUEST["ticket"])) {
			try {
				// url里带上ticket去cas服务验证地址
				$validateurl = $validateServer."?ticket=".$_REQUEST["ticket"]."&service=".$address;
				header("Content-Type:text/html;charset=utf-8");
				//服务端为https，需加以下配置
				$arrContextOptions=array(
				"ssl"=>array(
					"verify_peer"=>false,
					"verify_peer_name"=>false,
						),
				);  
				$abc=urldecode(file_get_contents($validateurl,false, stream_context_create($arrContextOptions)));
				//http使用这种方式
				//$abc=urldecode(file_get_contents($validateurl));
				// 后去验证后的内容
				// 常见一个dom文档
				$dom = new \DOMDocument();
				// 忽略xml命名空间
				$dom->preserveWhiteSpace = false;
				$dom->encoding = "utf-8";
				$dom->loadXML($abc);
				/**
				*获取用户的唯一标识信息
				*由UIA的配置不同可分为两种：
				*(1)学生：学号；教工：身份证号
				*(2)学生：学号；教工：教工号
				**/
				$userid="";
				$extra_attributes = array();
				// CAS服务器只允许utf-8格式的数据
				 if($dom->getElementsByTagName("authenticationSuccess")->length != 0){
					 $success_elements = $dom->getElementsByTagName("authenticationSuccess");
					  if ($success_elements->item(0)->getElementsByTagName("user")->length == 0) {
						 header("Location: " . $loginServer . "?service=" . $address);
					  } else {
						$userid=$success_elements->item(0)->getElementsByTagName("user")->item(0)->nodeValue;
						if ( $success_elements->item(0)->getElementsByTagName("attributes")->length != 0) {
							$attr_nodes = $success_elements->item(0)->getElementsByTagName("attributes");
							if ($attr_nodes->item(0)->hasChildNodes()) {
								foreach ($attr_nodes->item(0)->childNodes as $attr_child) {
									_addAttributeToArray($extra_attributes, $attr_child->localName,$attr_child->nodeValue);
								}
							}
						}
					}
				}else{
				   header("Location: " . $loginServer . "?service=" . $address); 
				}
				
			// 获取登录用户的扩展信息  
			// 用户姓名
			$name =isExistInArray($extra_attributes,"comsys_name");
			// 电话号码
			$phone =isExistInArray($extra_attributes,"comsys_phone");
			// 民族
			$national =isExistInArray($extra_attributes,"comsys_national");
			// 性别
			$genders =isExistInArray($extra_attributes,"comsys_genders");
			// 邮件
			$email = isExistInArray($extra_attributes,"comsys_email");
			// 其它职位
			$other_post = isExistInArray($extra_attributes,"comsys_other_post");
			// 教育度程
			$educationals = isExistInArray($extra_attributes,"comsys_educational");
			// 教工号
			$teaching_number = isExistInArray($extra_attributes,"comsys_teaching_number");
			// 学生号
			$studentNumber =isExistInArray($extra_attributes,"comsys_student_number");
			// 获取用户类型   1-学生  2-教工
			$type =isExistInArray($extra_attributes,"comsys_usertype");
			/**
			*  角色数组
			*  key:ROLECNNAME;value:角色中文名称
			*  key:ROLEIDENTIFY;value:角色代码
			**/
			$role = isExistInArray($extra_attributes,"comsys_role");
			/**
			*  部门数组
			*  key:DEPARTMENTNAME;value:部门中文名称
			*  key:DEPARTMENTIDENTIFY;value:部门代码
			**/
			$department =isExistInArray($extra_attributes,"comsys_department");
			/**
			*  岗位数组
			*  key:POSTNAME;value:岗位中文名称
			*key:POSTIDENTIFY;value:岗位代码
			**/
			$post = isExistInArray($extra_attributes,"comsys_post");
			// 学生院系名称
			$faculetName = isExistInArray($extra_attributes,"comsys_faculetyname");
			// 学生院系代码
			$faculetCode =isExistInArray($extra_attributes,"comsys_faculetycode");
			// 学生年级名称
			$gradName = isExistInArray($extra_attributes,"comsys_gradename");
			// 学生年级代码
			$gradCode = isExistInArray($extra_attributes,"comsys_gradecode");
			// 学生专业名称
			$disciplinName =isExistInArray($extra_attributes,"comsys_disciplinename");
			// 学生专业代码
			$disciplinCode = isExistInArray($extra_attributes,"comsys_disciplinecode");
			// 学生班级名称
			$className =isExistInArray($extra_attributes,"comsys_classname");
			// 学生班级代码
			$classCode = isExistInArray($extra_attributes,"comsys_classcode");
            $user_info = array('name' => $name, 'phone' => $phone, 'national' => $national, 'genders' => $genders, 'email' => $email, 'other_post' => $other_post, 'educationals' => $educationals, 'teaching_number' => $teaching_number, 'studentNumber' => $studentNumber, 'type' => $type, 'role' => $role, 'department' => $department, 'post' => $post, 'faculetName' => $faculetName, 'faculetCode' => $faculetCode, 'gradName' => $gradName, 'gradCode' => $gradCode, 'disciplinName' => $disciplinName, 'disciplinCode' => $disciplinCode, 'className' => $className, 'classCode' => $classCode);
            //print_r($user_info);exit;
            Session::set("user_infocas",$user_info);

            header("Location: https://".$_SERVER['SERVER_NAME']."/web/home.html");exit;

			} catch (Exception $e) {
				echo $e->getMessage();
				header("Location: " . $loginServer . "?service=" . $address);
			}
		// 否则就去cas登录地址
		} else {
			header("Location: " . $loginServer . "?service=" . $address);
			exit;
		} 
			
    }
    /*
    caslogin_wxl()用来在我电脑替代caslogin()
    */
    public function caslogin ()
    {
       
     /*$user_info = [
            "name" => "吴强",
            "phone" => "13820773331",
            "national" => "回族",
            "genders" => "男",
            "email" => "WuQiang@nankai.edu.cn",
            "other_post" => "教授",
            "educationals" => "博士研究生毕业",
            "teaching_number" => "003160",
            "studentNumber" => NULL,
            "type" => "2",
            "role" => "ROLECNNAME:教工,ROLEIDENTIFY:ROLE_TEACHER-ROLECNNAME:0101 事业编 on,ROLEIDENTIFY:00010101",
            "department" => "DEPARTMENTNAME:泰达应用物理研究院,DEPARTMENTIDENTIFY:20180164",
            "post" => "[]",
            "faculetName" => NULL,
            "faculetCode" => NULL,
            "gradName" => NULL,
            "gradCode" => NULL,
            "disciplinName" => NULL,
            "disciplinCode" => NULL,
            "className" => NULL,
            "classCode" => NULL
          ];
        
          $user_info = [
            "name" => "王鸿鹏",
            "phone" => "13920518337",
            "national" => "汉族",
            "genders" => "男",
            "email" => "hpwang@nankai.edu.cn",
            "other_post" => "副教授",
            "educationals" => "博士研究生毕业",
            "teaching_number" => "009060",
            "studentNumber" => NULL,
            "type" => "2",
            "role" => "ROLECNNAME:教工,ROLEIDENTIFY:ROLE_TEACHER-ROLECNNAME:0101 事业编 on,ROLEIDENTIFY:00010101",
            "department" => "DEPARTMENTNAME:人工智能学院,DEPARTMENTIDENTIFY:20180168",
            "post" => "[]",
            "faculetName" => NULL,
            "faculetCode" => NULL,
            "gradName" => NULL,
            "gradCode" => NULL,
            "disciplinName" => NULL,
            "disciplinCode" => NULL,
            "className" => NULL,
            "classCode" => NULL
          ];
              */
         $user_info = [
            "name" => "许丽",
            "phone" => "13902002664",
            "national" => "汉族",
            "genders" => "女",
            "email" => "xuli@nankai.edu.cn",
            "other_post" => "实验师",
            "educationals" => "硕士研究生毕业",
            "teaching_number" => "013023",
            "studentNumber" => NULL,
            "type" => "2",
            "role" => "ROLECNNAME:教工,ROLEIDENTIFY:ROLE_TEACHER-ROLECNNAME:0101 事业编 on,ROLEIDENTIFY:00010101",
            "department" => "[DEPARTMENTNAME:人工智能学院,DEPARTMENTIDENTIFY:20180168]",
            "post" => "[]",
            "faculetName" => NULL,
            "faculetCode" => NULL,
            "gradName" => NULL,
            "gradCode" => NULL,
            "disciplinName" => NULL,
            "disciplinCode" => NULL,
            "className" => NULL,
            "classCode" => NULL
          ]; 
        /*
          $user_info = [
            "name" => "田野",
            "phone" => "18722543443",
            "national" => "汉族",
            "genders" => "男",
            "email" => "nkjrty@126.com",
            "other_post" => "助理研究员（自然科学）",
            "educationals" => "硕士研究生毕业",
            "teaching_number" => "013047",
            "studentNumber" => NULL,
            "type" => "2",
            "role" => "ROLECNNAME:教工,ROLEIDENTIFY:ROLE_TEACHER-ROLECNNAME:0101 事业编 on,ROLEIDENTIFY:00010101",
            "department" => "[DEPARTMENTNAME:教务处,DEPARTMENTIDENTIFY:20180014]",
            "post" => "[]",
            "faculetName" => NULL,
            "faculetCode" => NULL,
            "gradName" => NULL,
            "gradCode" => NULL,
            "disciplinName" => NULL,
            "disciplinCode" => NULL,
            "className" => NULL,
            "classCode" => NULL
          ];*/
          
          Session::set("user_infocas",$user_info);
          header("Location:iclass.com/web/home.html");
    }


    // public function caslogin()
    // {
    //     $platform = input('post.platform');  //接收实验室名称

    //     // $user_info = array('name' => '管理', 'genders' => '男', 'teaching_number' => '111111', 'studentNumber' => '', 'labid' => $platform);
    //     // $user_info = array('name' => '邢树松', 'genders' => '男', 'teaching_number' => '005154', 'studentNumber' => '', 'labid' => $platform);
    //     // $user_info = array('name' => '秦岩丁', 'genders' => '男', 'teaching_number' => '013040', 'studentNumber' => '', 'labid' => $platform);
    //    // $user_info = array('name' => '王瑞山', 'genders' => '男', 'teaching_number' => '10073110', 'studentNumber' => '', 'labid' => $platform);
    //     $user_info = array('name' => '王鸿鹏', 'genders' => '男', 'teaching_number' => '009060', 'studentNumber' => '', 'labid' => $platform);

    //     Session::set("user_infocas",$user_info);
    //     jsonReturn('001');
    // }


    public static function caslogoff()
    {
        vendor('phpCAS.CAS');
        \phpCAS::setDebug();
        \phpCAS::client(CAS_VERSION_2_0, 'authserver.*****.edu.cn', 80, 'authserver', false);
        $param = array('service' => 'http://cas_server_url/home/login/cas');
        \phpCAS::logout($param);
    }

    /**
     * 退出登录
     */
    public function loginOut()
    {
        $logArr = [
            'id' => Session::get('log_id'),
            'logout_time' => date('Y-m-d H:i:s')
        ];
        Db::name('loginLog')->update($logArr);
        Session::delete('log_id');
        Session::delete('user_info.user_uid');
        Session::delete('user_info.user_name');
        Session::delete('user_info.user_id');
        Session::delete('user_infocas');
        Session::delete('user_infocas.labid');

        jsonReturn('001');

    }

    /**
     * 获取权限
     * $lab_admin: 是否是当前实验室的管理员 默认为true(即：是管理员)
     * @return array|bool
     */
    protected static function getAuth($id, $lab_admin=true)
    {
         if($id){
            //根据传入的用户id查找到关联的角色id
            $map = [
                'user_id' => $id
            ];

            $corr = Db::name('corrUserRole')->where($map)->find();

            $corr_role = explode(',',$corr['role_id']);
    
            //如果 $lab_admin为false 则删除$corr_role中值为2的元素（即不去查实验室负责人的权限）
            if ( $lab_admin === false )
            {
                $k = array_search('2', $corr_role);

                if ( false !== $k )
                {
                    array_splice($corr_role, $k, 1);
                }
            }

            $show = self::getShow($corr['role_id']);
          
            //根据角色id查找到权限
            $cond = [
                'id' => array('in',$corr_role)
            ];

            $res = Db::name('role')->where($cond)->select();
            //处理权限，得到一个权限id的数组
            $rules = '';
            $roleMap = []; // 角色关系
            foreach ($res as $k => $v){
                  $roleMap[] = [
                      'id' => $v['id'],
                      'name' => $v['name']
                  ];
                if($k === 0){
                    $rules .= $v['rules'];
                }else{
                    $rules .= ','.$v['rules'];
                }
            }

            $ruleArr = array_unique(explode(',',$rules));

            if(!empty($ruleArr)){
                //获取一级导航
                $rule = implode(',',$ruleArr);
                $col = RuleModel::all($rule);
        
                $primary = [];
                if(null !== $col){
                    foreach ($col as $key => $value){
                        $primary['auth'][] = $value->toArray();
                    }
                }

                //获取二级导航
                $first = [];
                $second = [];
                foreach ($primary['auth'] as $k => $v){
                    if($v['pid'] == 0){
                        $v['child'] = [];
                        $first[] = $v;
                    }else{
                        $second[] = $v;
                    }
                    unset($primary['auth'][$k]);
                }

                //遍历数组得到最终的权限
                $auth = [];

                for ( $i = 0; $i < count($first); $i++) {
                    $auth['auth'][] = $first[$i];

                    for ( $j = 0; $j < count($second); $j++) {
                        if($first[$i]['id'] == $second[$j]['pid']){
                            $auth['auth'][$i]['child'][] = $second[$j];
                        }
                    }
                }
                $auth['role'] = $roleMap;
                $auth['rule'] = $corr['role_id'];
                $auth['show'] = $show;
    
                return $auth;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 页面显示
     * @param $role
     * @return array
     */
    protected static function getShow($role){
        $show = [];

        $roleArr = explode(',',$role);
        if(in_array(3,$roleArr)){
            $show = [
                'notice' => 1,
                'curriculum' => 1,
                'calendar' => 1
            ];
        }else{
            $show = [
                'notice' => 1,
                'curriculum' => 0,
                'calendar' => 1
            ];
        }
        return $show;
    }

    /**
     * 根据一卡通获取用户名
     */
//    public function getUserByUid(){
//        $user_uid = input('post.id');
//        $map = [
//            'user_uid' => $user_uid
//        ];
//        $name = Db::name('user')->where($map)->value('name');
//        if($name){
//            jsonReturn("001",$name);
//        }
//    }

    /**
     * 根据id获取用户名
     */
//    public function getUserByid(){
//        $user_uid = input('post.id');
//        $map = [
//            'id' => $user_uid
//        ];
//        $name = Db::name('user')->where($map)->value('name');
//        if($name){
//            jsonReturn("001",$name);
//        }
//    }

    //PHP与C#端数据交互-登录验证
//    public function loginC(){
//        $ret = file_get_contents("php://input");           //获取c#端数据
//        $rut = desdecrypt($ret);      //解密
//        $result = json_decode(urldecode($rut),true);
//
//        //开始验证ldap（用户是否合法）
//        import("ORG.Util.LDAP");
//        $nla = new \NankaiLDAP($user_uuid,$password);
//        //更改服务器参数后，再次验证
//        $nla->set_ldap_config("222.30.61.220","389","uid= safetyadmin,ou=teacher,dc=nankai,dc=edu,dc=cn","safety@nankai@wiscom","dc=nankai,dc=edu,dc=cn",$user_uuid,$password);
//        $check = $nla->ldap_auth();
//        if($check !== "pass"){
//            json('002',null,"该用户不存在！");
//        }else{
//            //开始验证数据库是否有此用户
//            $user_uid = trim($result['id']);
//            $password = trim($result['password']);
//
//            $salt = Config::get('pwd_salt');
//            $pwd = md5(md5($password).$salt);
//
//            $map = [
//                'user_uid'  => $user_uid,
//                'password'  => $pwd
//            ];
//
//            $user_col = UserModel::get($map);
//
//            if(null == $user_col){
//                jsoned('002',null,'账号或密码错误！');
//            }else{
//                jsoned('001',null,"登陆成功！");
//            }
//        }
//
//    }




















}