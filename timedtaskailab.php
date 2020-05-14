<?php
/**
 * Created by Sublime Text
 * User: 愿得一人行
 * Date: 2018/12/12
 * Time: 16:51
 */
/*用户登录定时任务*/
header("Content-type:text/html;charset=utf-8");
ini_set('date.timezone','Asia/Shanghai');
//连接数据库
$servername = "localhost";
$username = "root";
$password = "Nklab2018";
$dbname = "nklb";



// 创建连接
$conn = new mysqli($servername, $username, $password, $dbname);
mysqli_query($conn, "set names 'utf8'"); 
// 检测连接
if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
} 

//开始一个事务
$conn->autocommit(false);  //设置为非自动提交——事务处理

//查询数据表
$sql = "SELECT * FROM nk_ailab_user";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
	//查询当前时间
	$time = time();
	$timeconfig = '1800'; 
	$sel = [];
	$id = [];
	for($i=0;$i<$result->num_rows;$i++){
		$sel[$i] = $result->fetch_assoc(); 
		$timenum[$i] = $time - $sel[$i]['time'];
		//判断当前时间减去用户表时间是否小于30分钟
		if($timenum[$i] > $timeconfig){
			$id[$i] = $sel[$i]['id'];   //获取当前记录id
			//删除当前记录
			$del[$i] = "DELETE FROM nk_ailab_user WHERE id = " . $id[$i];
			$delete[$i] = $conn->query($del[$i]);
			//删除对应的错题表记录
			if($delete[$i]){
				$conn->commit();   		//提交成功。
			}else{
				$conn->rollback();  	//数据回滚。
			}			
		}
	}
}

$conn->autocommit(true);  //设置为非自动提交——事务处理
$conn->close(); 




?>