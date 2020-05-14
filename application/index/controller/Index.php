<?php
namespace app\index\controller;

use think\Cache;
use think\Controller;
use think\Session;
class Index extends Controller
{

	/**
	 *登录
	 *///原方法index()
	public function index_wxl(){
        if(!Session::has('user_info.session_id')){
            $this->redirect("https://".$_SERVER['SERVER_NAME']."/api/caslogin");
            //$this->redirect("http://".$_SERVER['SERVER_NAME']."/web/home.html");
        }
    }

    public function index(){//修改后的index()
        //if(!Session::has('user_info.session_id')){
            $this->redirect("http://".$_SERVER['SERVER_NAME']."/api/caslogin");
            //$this->redirect("http://".$_SERVER['SERVER_NAME']."/web/home.html");
       // }
    }


    /**
     * 清除缓存
     */
    public function clearCache(){
        $re = Cache::clear();
        jsonReturn("001",null,"缓存清除成功！");
    }
}
