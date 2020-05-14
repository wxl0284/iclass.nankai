<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\Db;

// 应用公共文件
/**
 * 直接输出Json信息，支持JsonP跨域
 * json响应信息输出，只给$code即可。
 * 可根据需要提供$result返回附加信息，如需自定义message信息，可以提供$message。
 * 调用本函数后，建议不要再使用任何输出语句，否直则会接跟在json消息后输出。
 *
 * 对于前端：如果需要跨域，只要在jQuery提交时将dataType设置为jsonP即可
 * 对于前端：如果采用iframe框架提交内容，需要在iframe框架外取得参数，需要在框架外定义一个js函数，然后将函数名作为get参数iframe_upload即可
 *
 * @param string $code    信息码
 * @param mixed  $result  [返回数据]
 * @param string $message [自定义信息文本]
 *
 * @return void
 *
 * Created by Notepad++;
 * User: 黎明;
 * Date: 2016/1/24
 * Last Modified: 2016/3/7
 * Last Change: 识别参数：@get_param String [$iframe_upload] 在子iframe中调用父窗口函数
 */
function jsonReturn($code, $result = null, $message = null) {
//    header('Author: Jin Liming, jinliming2@gmail.com');
    //消息信息
    if($message == null) {
        switch($code) {
            case "001":
                $message = "Success";  //成功
                break;
            case "002":
                $message = "Missing Parameter";  //缺少参数
                break;
            case "003":
                $message = "Invalid Token";  //无效Token
                break;
            case "004":
                $message = "Server Authentication Failed";  //服务器认证失败
                break;
            case "005":
                $message = "Inadequate Permissions";  //权限不够
                break;
            case "006":
                $message = "Unknown Reason";  //未知原因
                break;
            case "007":
                $message = "Database Error";  //数据库错误
                break;
            case "008":
                $message = "Server Error";  //服务器错误
                break;
            case "009":
                $message = "Parameter Error";  //参数错误
                break;
            default:
                $message = "程序猿君开小差了~";
                break;
        }
    }
    //返回信息拼接
    $ret = json_encode(array(
        "code"    => $code,
        "message" => $message,
        "result"  => $result
    ));
    if(isset($_GET['callback'])) {
        //跨域JsonP设置
        $ret = $_GET['callback'].'('.$ret.')';
        header('Content-type: application/javascript');
    } else if(isset($_GET['iframe_upload'])) {
        //iframe中调用父窗口函数
        $ret = "<script>parent.".$_GET['iframe_upload']."(".$ret.");</script>";
        header('Content-Type: text/html;charset=utf-8');
    } else {  //普通json
//        header("Access-Control-Allow-Origin:*");
//        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        header('Content-type: application/json');
    }
    echo $ret;
}

/**
 * 保存base64图片
 * @param $img_data
 * @param string $prefix 最终保存图片的前缀
 * @param string $dir
 * @return bool|string
 */
function saveBase64Img($img_data,$prefix="",$dir="common"){
    if(preg_match('/^(data:\s*image\/(\w+);base64,)/', $img_data, $result)){
        $type = $result[2];    //获取图片的类型jpg png等
        $arr = array($result[1] => "");
        $base_body = strtr($img_data,$arr);
        $img = base64_decode($base_body);

        $base_path = ROOT_PATH;
        $stroe_path = 'uploads/'.$dir.'/'.$prefix.md5(time() + rand(1,999999)).'.'.$type;
        $file_path = $base_path.$stroe_path;
        $re = file_put_contents($file_path,$img); //对图片进行解析并保存
        if($re){
            return $stroe_path;
        }else{
            return false;
        }
    }else{
        return false;
    }
}


/**
 * 下载excel模板表
 * @param $file
 * @param string $name
 */
function BaseTemplate($file,$name = 'download'){
    $template = "uploads/exceltable/".$file;
    if(is_file($template)){
        $length = filesize($template);
        $type = mime_content_type($template);
        $showname = ltrim(strrchr($template,'/'),'/');
        $filename = $name . strrchr($showname,'.');
        header("Content-Description: File Transfer");
        header('Content-type: ' . $type);
        header('Content-Length:' . $length);
        if(preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])){ //for IE
            header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
        }else{
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        }
        readfile($template);
    }else{
        $this->error('下载失败！源文件地址有误！请联系管理员!');
    }
}




/**
 * excel表格批量导出
 * @param $filename   $data
 * @param string $name
 */
function exportExcel($filename,$data){

    $path = dirname(__FILE__); //找到当前脚本所在路径
    import('PHPExcel.Classes.PHPExcel');
    import('PHPExcel.Classes.PHPExcel.IOFactory.PHPExcel_IOFactory');
    $PHPExcel = new \PHPExcel(); 
    $PHPSheet = $PHPExcel->getActiveSheet();
    $PHPSheet->setTitle("实验成绩表"); //给当前活动sheet设置名称
    $PHPSheet->setCellValue("A1", "姓名")
        ->setCellValue("B1", "学号")
        ->setCellValue("C1", "班级")
        ->setCellValue("D1", "手机号")
        ->setCellValue("E1", "所属课程")
        ->setCellValue("F1", "所属实验")
        ->setCellValue("G1", "实验成绩")
        ->setCellValue("H1", "报告成绩")
        ->setCellValue("I1", "总成绩")
        ->setCellValue("J1", "评语");

    $i = 2;
    foreach($data as $k => $v){
        $PHPSheet->setCellValue("A" . $i, $v['user_name'])
            ->setCellValue("B" . $i, $v['user_uid'])
            // ->setCellValue("C" . $i, $v['class'])
            ->setCellValue("D" . $i, $v['phone'])
            ->setCellValue("E" . $i, $v['curriculum_name'])
            ->setCellValue("F" . $i, $v['experiment_name'])
            ->setCellValue("G" . $i, $v['experiment_score'])
            ->setCellValue("H" . $i, $v['presentation_score'])
            ->setCellValue("I" . $i, $v['total_score'])
            ->setCellValue("J" . $i, $v['comment']);
        $i++;
    }

    $PHPWriter = \PHPExcel_IOFactory::createWriter($PHPExcel, "Excel2007");
    header('Content-Disposition: attachment;filename="' . $filename . ' .xlsx"');//告诉浏览器输出浏览器名称
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');//告诉浏览器输出07Excel文件
    header('Cache-Control: max-age=0');//禁止缓存
    $PHPWriter->save("php://output"); //调取浏览器下载表格
    exit;
}







    /**
     * PHP与C#端接口数据的加解密
     */

    //加密
    function desencrypt($str){
        $key = 'szhTkj!@';
        import("DES");
        $des = new \DES($key);

        return $des->encrypt($str);
    }


    //解密
    function desdecrypt($str){
        $key = 'szhTkj!@';
        import("DES");
        $des = new \DES($key);

        return $des->decrypt($str);
    }



    /**
     * PHP与C#端接口数据传输json数据
     */
    function jsoned($code, $result = null, $message = null){

         //消息信息
        if($message == null) {
            switch($code) {
                case "001":
                    $message = true;  //成功
                    break;
                case "002":
                    $message = false;  //失败
                    break;
                default:
                    $message = "程序猿君开小差了~";
                    break;
            }
        }


        //返回信息拼接
        $ret = json_encode(array(
            "isSuccess" => $message,
            'result' => $result
        ));
        if(isset($_GET['callback'])) {
            //跨域JsonP设置
            $ret = $_GET['callback'].'('.$ret.')';
            header('Content-type: aplication/javascript');
        } else if(isset($_GET['iframe_upload'])) {
            //iframe中调用父窗口函数
            $ret = "<script>parent.".$_GET['iframe_upload']."(".$ret.");</script>";
            header('Content-Type: text/html;charset=utf-8');
        } else {  //普通json
            header('Content-type: aplication/json');
        }
        $rut = desencrypt($ret);
        echo $rut;
    }


/**
 * 上传
 * @param string $sign
 */
    function upload($sign='')
    {
        $file = request()->file('file');

//        $fileInfo = $file->getInfo();

        $fileInfo = request()->file('file')->getInfo();

        $ext = substr($fileInfo['name'],strpos($fileInfo['name'],'.'));

        if(in_array($ext,['.xlsx','.xls'])){
            $store_path = "uploads/phpexcel/";
            $checkExt = ['xlsx','xls'];

        }elseif (in_array($ext,['.doc','.docx','.pdf','.txt'])){
            $store_path = "uploads/document/";
            $checkExt = ['doc','docx','pdf','txt'];

        }elseif (in_array($ext,['.mp4'])){
            $store_path = "uploads/video/";
            $checkExt = ['mp4'];

        }elseif (in_array($ext,['.exe'])){
            $store_path = "uploads/software/";
            $checkExt = ['exe'];

        }elseif (in_array($ext,['.zip'])){
            $store_path = "uploads/static/";
            $checkExt = ['zip'];

        }else{
            $checkExt = ['jpg','png','jpeg'];

            if (in_array($ext,['.jpg','.png','.jpeg']) && $sign == 'resource'){
                $store_path = "uploads/images/";
            }else{
                $store_path = "uploads/portrait/";
            }
        }

        $config = array(
            'size'    =>    16384000000,
            'ext'     =>    $checkExt,
        );

        $info = $file->validate($config)->move($store_path);
        if($info){
            $url = $store_path . str_replace('\\','/',$info->getSaveName());
            $res = [
                'name'  =>  $info->getFilename(),
                'url'   =>  str_replace('public/','',$url)
            ];
            jsonReturn("001",$res);
        }else{
            jsonReturn('002',null,$file->getError());
        }

    }


	//给数组赋值
	function _addAttributeToArray(array &$attributeArray, $name, $value)
    {
        if (isset($attributeArray[$name])) {
             if (!is_array($attributeArray[$name])) {
                $existingValue = $attributeArray[$name];
                $attributeArray[$name] = array($existingValue);
            }
			$attributeArray[$name][] = trim($value);
        } else {
            $attributeArray[$name] = trim($value);
        }
    }

	//判断当前键值是否存在数组中
	function isExistInArray(array &$abc,$key){
	   if(isSet($abc[$key])){
	     return $abc[$key];
	   }
	   return null;
	}
