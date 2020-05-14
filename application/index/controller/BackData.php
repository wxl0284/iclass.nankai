<?php
/**
 * Created by PhpStorm.
 * User: 高笛淳
 * Date: 2017/11/29
 * Time: 11:19
 */

namespace app\index\controller;

use think\Controller;
use think\Loader;
use think\Db;
class BackData extends Controller
{
    /**
     * 备份数据
     */
    public function backData(){
        Loader::import('lotofbadcode.phpextend.databackup.mysql.Backup');
        //自行判断文件夹

        $datetime = date("YmdHis",time());
        $backupdir = './back' . DIRECTORY_SEPARATOR . $datetime;

        if (!is_dir($backupdir))
        {
            mkdir($backupdir, 0777, true);
        }
        $host = config('database.hostname');
        $port = config('database.hostport');
        $database = config('database.database');
        $dbname = config('database.username');
        $dbpwd = config('database.password');

        $server = $host.':'.$port;

        $backup = new \Backup($server, $database, $dbname, $dbpwd);
        $backup->setbackdir($backupdir)
            ->setvolsize(0.2);

        $percent = '';
        do
        {
            $result = $backup->backup();
            if ($result['totalpercentage'] > 0)
            {
                $percent = $result['totalpercentage'];
            }
        } while ($result['totalpercentage'] < 100);

        if(100==$percent){
            $size = self::getDirSize($backupdir);
            $size = self::getsize($size, 'kb');

            $insert = [
                'name' => $datetime.'.sql',
                'path' => str_replace('\\','/',substr($backupdir,2)),
                'size' => $size,
                'create_time' => date('Y-m-d H:i:s',time())
            ];

            $re = Db::name('dataBack')->insert($insert);
            if($re){
                jsonReturn('001',$re,'数据备份完成！');
            }

        }else{
            jsonReturn('002',null, '数据备份失败！');
        }
    }

    /*
     * 计算大小
     */
    protected function getDirSize($dir)
    {
        $size = 0;

        $fd = opendir($dir);
        while($file=readdir($fd)){
            if($file=='.' && $file=='..'){
                continue;
            }
            $file = $dir.DIRECTORY_SEPARATOR.$file;
            $size += filesize($file);
        }
        closedir($fd);

        return $size;
    }

    /**
     * 转换数据单位
     * @param $size
     * @param $format
     * @return string
     */
    protected static function getsize($size, $format) {
        $p = 0;
        if ($format == 'kb') {
            $p = 1;
        } elseif ($format == 'mb') {
            $p = 2;
        } elseif ($format == 'gb') {
            $p = 3;
        }
        $size /= pow(1024, $p);
        return number_format($size, 3).$format;
    }

    /**
     * 备份数据列表
     */
    public function dataList(){
        $res = [];
        $res = Db::name('dataBack')->select();
        jsonReturn('001',$res);
    }

    /**
     * 删除数据列表
     */
    public function delList(){
        $id = input('post.id');
        $map = [
            'id' => $id
        ];
        $path = Db::name('dataBack')->where($map)->value('path');

        $re = Db::name('dataBack')->where($map)->delete();

        if($re){
            self::del_DirAndFile($path);
            jsonReturn('001',$re,'删除成功！');
        }elseif (0 ===  $re){
            jsonReturn('001',null,'没有删除数据！');
        }else{
            jsonReturn('001',null,'删除失败！');
        }
    }

    /**
     * 删除目录
     * @param $dirName
     */
    protected function del_DirAndFile($dirName){

        if(is_dir($dirName)){
            if ( $handle = opendir( "$dirName" ) ) {
                while ( false !== ( $item = readdir( $handle ) ) ) {
                    if ( $item != "." && $item != ".." ) {
                        if ( is_dir( "$dirName/$item" ) ) {
                            del_DirAndFile( "$dirName/$item" );
                        } else {
                            unlink( "$dirName/$item" );
                        }
                    }
                }
                closedir( $handle );
                rmdir( $dirName );
            }
        }
    }

    /**
     * 还原
     */
    public function restoreBackData() {
        Loader::import('lotofbadcode.phpextend.databackup.mysql.Recovery');

        $host = config('database.hostname');
        $port = config('database.hostport');
        $database = config('database.database');
        $dbname = config('database.username');
        $dbpwd = config('database.password');

        $server = $host.':'.$port;
        $recovery = new \Recovery($server, $database, $dbname, $dbpwd);

        $id = input('post.id');
        $map = [
            'id' => $id
        ];
        $path = Db::name('dataBack')->where($map)->value('path');

        $recovery->setSqlfiledir($path);

        $percent = '';
        do
        {
            $result = $recovery->recovery();

            if ($result['totalpercentage'] > 0)
            {
                $percent = $result['totalpercentage'];
            }
        } while ($result['totalpercentage'] < 100);


        if(100==$percent){
            jsonReturn('001',null,'数据还原完成！');
        }else{
            jsonReturn('002',null, '数据还原失败！');
        }
    }
}