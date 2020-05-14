<?php
/**
 * Created by Sublime Text
 * User: 愿得一人行
 * Date: 2018/9/8
 * Time: 16:51
 */
ob_start();
include 'tcpdf/tcpdf.php'; 
/*下载实验报告*/
header("Content-type:text/html;charset=utf-8");
ini_set('date.timezone','Asia/Shanghai');

$session_id = $_POST['session'];
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
//更新用户表
$time = time();
$random = mt_rand(10000000,99999999);
$sql = "UPDATE nk_huanke_user SET time= '$time',random = '$random' WHERE session_id='$session_id'";
$result = $conn->query($sql);
if(empty($result)){
	header('Location: http://vr.nankai.edu.cn/env/login.html');exit;
}

//查询用户成绩
$sql1 = "SELECT * FROM nk_huanke_score WHERE session_id='$session_id'";
$result1 = $conn->query($sql1);
$row = mysqli_fetch_assoc($result1);


//生成PDF数据


//实例化 
$pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false); 
 
// 设置文档信息 
$pdf->SetCreator('Helloweba'); 
$pdf->SetAuthor('yueguangguang');  
$pdf->SetTitle('垃圾焚烧发电资源化利用技术虚拟仿真实验报告'); 
$pdf->SetSubject('TCPDF Tutorial'); 
$pdf->SetKeywords('TCPDF, PDF, PHP'); 
 
// 设置页眉和页脚信息 
$pdf->SetHeaderData('', 5, '', '垃圾焚烧发电资源化利用技术虚拟仿真实验报告',  
      array(0,64,255), array(0,64,128)); 
$pdf->setFooterData(array(0,64,0), array(0,64,128)); 
 
// 设置页眉和页脚字体 
$pdf->setHeaderFont(Array('stsongstdlight', '', '10')); 
$pdf->setFooterFont(Array('helvetica', '', '8')); 
 
// 设置默认等宽字体 
$pdf->SetDefaultMonospacedFont('courier'); 
 
// 设置间距 
$pdf->SetMargins(15, 27, 15); 
$pdf->SetHeaderMargin(5); 
$pdf->SetFooterMargin(10); 
 
// 设置分页 
$pdf->SetAutoPageBreak(TRUE, 25); 
 
// set image scale factor 
$pdf->setImageScale(1.25); 
 
// set default font subsetting mode 
$pdf->setFontSubsetting(true); 
 
//设置字体 
$pdf->SetFont('stsongstdlight', '', 14); 
 
$pdf->AddPage(); 

$str1 = '
<h2 style="text-align: center;">垃圾焚烧发电资源化利用技术虚拟仿真实验报告</h2>
<div><h3>实验目的：</h3>
<p>（1）通过虚拟仿真实验场景，掌握垃圾焚烧发电资源化利用原理和工艺流程，学习关键知识点和设备运行原理，锻炼学生理论联系实际的能力；</p>
<p>（2）通过实验参数设置和DCS系统操作训练，考察不同实验参数与焚烧工况对发电量输出及污染物（二噁英、HCl、SO2、NOx、CO、颗粒物）生成的影响规律，培养学生自主学习能力和工程设计的能力。</p>
</div>
<div><h3>实验要求：</h3>
<p>依托虚拟仿真实验教学系统，完成以下任务：</p>
<p>1. 自主进行单因素影响实验操作和分析练习：要求改变一个参数值，固定其他参数，系统模拟计算得到发电量、污染物生成量，并根据模拟结果作出曲线图，分析改变量对发电量的影响规律。</p>
<p>2. 自主进行多因素影响实验操作和分析练习。要求同时改变两个或两个以上实验参数，并根据模拟结果作出曲线图，分析参数之间关系及对发电量的影响，考察影响的关键因素。</p>
<p>3. 根据老师限定参数，得到其他参数因素对发电量、污染物生成量的影响规律。</p>
<p>4.根据老师限定的参数条件，为实现较高的发电量输出，较低的污染物产生量，通过反复实验，得到其他参数的设定范围，进一步得到最优的工艺参数组合。</p>
</div>
<div><h3>实验步骤：</h3>
<p>一、仔细阅读实验目的和实验原理</p>
<p>二、熟悉虚拟仿真实验场景，掌握工艺流程，学习关键知识点和设备运行原理</p>
<p>三、完成垃圾焚烧发电工作安全测试</p>
<p>四、完成垃圾分类测试</p>
<p>五、设置实验参数，通过DCS操作系统，完成升温点火操作</p>
	<p>1.打开引风机</p>
	<p>2.打开一次风，根据步骤五中已设置的进风模式启闭一次风进风阀门，选择正确风路</p>
	<p>3.打开一次风炉膛进风分配阀门，进行风量分配</p>
	<p>4.启动抓斗，投料进入焚烧炉</p>
	<p>5.燃烧器点火</p>
	<p>6.打开碱液喷淋</p>
	<p>7.打开活性炭喷淋</p>
	<p>8.打开二次风</p>
<p>六、在实验结果窗口，查看试验结果</p>
<p>七、根据实验要求，开展多次模拟实验：重新进入步骤五，改变各个实验参数的设置及选择不同进风模式，在DCS操作界面改变风量分配和选择正确风路，完成不同的模拟实验。</p>
<p>八. 对多次实验结果进行总结分析，并递交分析报告</p>
</div>
<div><h3>实验成绩：</h3>总分数：' . $row['score'] . '分</div>
<div><h3>实验分析：</h3>' . $row['count'] . '</div>';


// $pdf->writeHTML(0,$str1,'', 0, 'L', true, 0, false, false, 0);
$pdf->writeHTMLCell(0, 0, '', '', $str1, 0, 1, 0, true, '', true); //使用writeHTMLCell打印文本 
 
//输出PDF 
ob_end_clean();
$pdf->Output('report.pdf', 'D');


$conn->autocommit(true);  //设置为非自动提交——事务处理
$conn->close(); 


?>

