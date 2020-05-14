<?php
/**
 * Created by PhpStorm.
 * User: 高笛淳
 * Date: 2017/10/29
 * Time: 10:27
 */

namespace app\index\controller;

use think\Controller;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use think\Db;

class Until extends Controller
{
    public function generatorQrcode($url = '')
    {
        $url = $url ? $url : input('param.url');

        $arr = explode('=',$url);
        $id = $arr[1];
        $map = [
            'id' => $id
        ];
        $name = Db::name('base_laboratory')->where($map)->value('laboratory_name');
        $content = iconv("utf-8","gb2312",$name);

        $qrCode = new QrCode();

        $qrCode
            ->setText($url)
            ->setSize(300)
            ->setWriterByName('png')
            ->setMargin(10)
            ->setEncoding('UTF-8')
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH)
            ->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0])
            ->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255])
            ->setLabel('智慧实验室', 16, "./assets/noto_sans.otf", LabelAlignment::CENTER)
            ->setLogoPath("./assets/logow-3d.png")
            ->setLogoWidth(150)
            ->setValidateResult(false)
        ;


        $qrCode->writeFile("./public/{$content}.png");
        $data = [
            'qrcode' => "/public/{$name}.png"
        ];
        $re = Db::name('base_laboratory')->where($map)->update($data);

        if($re){
            jsonReturn('001',$re,'已生成二维码!');
        }elseif($re === 0){
            jsonReturn('001',null,'已生成二维码!');
        }else{
            jsonReturn('002',null,'生成二维码失败!');
        }
    }

    public function getCSVData(){
        $file_path = ROOT_PATH."uploads/list.csv";
        $file = fopen($file_path,"r");
        while ($data = fgetcsv($file)) { //每次读取CSV里面的一行内容

            $data_list[] = $data;
        }

        var_dump($data_list);
    }
}