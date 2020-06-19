<?php
namespace app\index\controller;

use think\Cache;
use think\Controller;
use think\Session;
class Index extends Controller
{

	/**
	 *登录
	 */
	public function index(){
        if(!Session::has('user_info.session_id')){
            $this->redirect("https://".$_SERVER['SERVER_NAME']."/api/caslogin");
            //$this->redirect("http://".$_SERVER['SERVER_NAME']."/web/home.html");
        }
    }

    /*public function index(){
        if(!Session::has('user_info.session_id')){
            $this->redirect("http://".$_SERVER['SERVER_NAME']."/api/caslogin");
            //$this->redirect("http://".$_SERVER['SERVER_NAME']."/web/home.html");
        }
    }*/

    /**
     * 清除缓存
     */
    public function clearCache(){
        $re = Cache::clear();
        jsonReturn("001",null,"缓存清除成功！");
    }
}
