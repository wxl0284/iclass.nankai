<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\Route;


/**
 * Role
 */
Route::rule('api/listRole','index/Role/listRole'); //添加角色
Route::rule('api/addRole','index/Role/addRole'); //添加角色
Route::rule('api/removeRole','index/Role/removeRole'); //删除角色
 /**
  * User
  */
 Route::rule('api/deleteUser','index/User/deleteUser');  //删除用户
 Route::rule('api/loadUserListData','index/User/loadUserListData');  //加载用户列表数据
 Route::rule('api/getOneUserInfo','index/User/getOneUserInfo');  //用户列表修改权限详情
 Route::rule('api/alterRole','index/User/alterRole');  //修改权限
 Route::rule('api/roleList','index/User/roleList');  //角色信息列表
 Route::rule('api/selectSelfInfo','index/User/selectSelfInfo');  //查看个人信息
 Route::rule('api/modifySelfInfo','index/User/modifySelfInfo');  //修改个人信息
// Route::rule('api/upUserPortrait','index/User/upUserPortrait');  //上传头像
// Route::rule('api/downloadExcelUser','index/User/downloadExcelUser');  //用户excel表格模板下载
 Route::rule('api/uploadUser','index/User/uploadUser');  //用户excel表格上传
 Route::rule('api/inserExcelUser','index/User/inserExcelUser');  //用户excel表数据入库

/*学生用户批量导入*/
 Route::rule('api/uploadStudentUser','index/User/uploadStudentUser');  //学生用户excel表格上传
 Route::rule('api/inserExcelstudentUser','index/User/inserExcelstudentUser');  //批量导入学生用户信息-写入数据库
/*学生用户管理*/
Route::rule('api/loadStudentUserList','index/User/loadStudentUserList');  //加载学生用户列表数据
Route::rule('api/deleteStudentUser','index/User/deleteStudentUser');  //删除学生信息




 /**
  * Login
  */
 Route::rule('api/AdminLogin','index/Login/AdminLogin');  //用户登录检测(后台)
 Route::rule('api/checkLogin','index/Login/checkLogin');  //用户登录检测
 Route::rule('api/loginOut','index/Login/loginOut');  //用户退出

 Route::rule('api/caslogin','index/Login/caslogin');  //获取用户信息

/**
 * Resmanage
 */

 Route::rule('api/upSource','index/Resmanage/upSource');   //资源中心，上传资源
 Route::rule('api/resourceQuery','index/Resmanage/resourceQuery');   //资源中心，资源列表查询
 Route::rule('api/resourceCheckList','index/Resmanage/resourceCheckList');   //资源审批列表
 Route::rule('api/particulars','index/Resmanage/particulars');   //资源详情查询
 Route::rule('api/delete','index/Resmanage/delete');   //删除资源
 Route::rule('api/examine','index/Resmanage/examine');   //资源审核
 Route::rule('api/search','index/Resmanage/search');   //公共资源搜索查找
// Route::rule('api/download_file','index/Resmanage/download_file');   //下载资源
Route::rule('api/resourceStatisticPieData','index/Resmanage/resourceStatisticPieData');   //资源统计饼图、柱状图（通用）


/**
 * Student
 */
//试验考核
Route::rule('api/AssessmentList','index/Student/AssessmentList');     			//实验列表查询
Route::rule('api/AssessmentSelect','index/Student/AssessmentSelect');     		//查看详情
Route::rule('api/uploadReport','index/Student/uploadReport');  				    //提交实验报告
Route::rule('api/upExperimentReport','index/Student/upExperimentReport');   	//上传实验报告


/**
 * Notice
 */
//实验室负责人-通知公告（增删改查）
Route::rule('api/indexNotice','index/Notice/index');   			//通知公告列表
Route::rule('api/saveNotice','index/Notice/save');   			//新增通知公告
Route::rule('api/readNotice','index/Notice/read');   			//查看单个通知公告
Route::rule('api/editNotice','index/Notice/edit');   			//修改通知公告-修改显示
Route::rule('api/updateNotice','index/Notice/update');   		//修改通知公告-修改更新
Route::rule('api/deleteNotice','index/Notice/delete');   		//删除通知公告

/**
 * Apply
 */

Route::rule('api/apply','index/Apply/apply'); //开课申请
Route::rule('api/applyList','index/Apply/applyList'); //开课申请列表
Route::rule('api/check','index/Apply/check'); //开课申请审核
Route::rule('api/detail','index/Apply/detail'); //开课申请审核查看详情
Route::rule('api/remove','index/Apply/remove'); //删除开课申请信息
Route::rule('api/remove_use','index/Apply/remove_use'); //删除课程申请使用信息
Route::rule('api/applyTeacherCheckedList','index/Apply/applyTeacherCheckedList'); //教师端开课申请已通过审核列表
Route::rule('api/applyTeacherWaitList','index/Apply/applyTeacherWaitList'); //教师端开课申请待审核审核列表
Route::get('api/reject_lessons', 'index/Apply/reject_lessons'); //查询被驳回的开课申请
Route::rule('api/listCheckedCourse','index/Apply/listCheckedCourse'); //已经审核通过可以选为上课的课程
Route::rule('api/teachApplyInfo','index/Apply/teachApplyInfo'); //上课申请详情
Route::rule('api/reject_use_info','index/Apply/reject_use_info'); //驳回的使用申请信息
Route::rule('api/teachApply','index/Apply/teachApply'); //新增上课申请
//Route::rule('api/apply_again','index/Apply/apply_again'); //重新提交  上课申请
Route::rule('api/teachApplyList','index/Apply/teachApplyList'); //上课申请审批列表
Route::rule('api/teachCheck','index/Apply/teachCheck'); //上课申请审核
Route::rule('api/teachRemove','index/Apply/teachRemove'); //删除上课申请信息
Route::rule('api/teachApplyCheckedList','index/Apply/teachApplyCheckedList'); //我的课程已通过课程
Route::rule('api/teachSchoolCheckedList','index/Apply/teachSchoolCheckedList'); //我的课程已通过课程
Route::rule('api/teachApplyWaitCheckList','index/Apply/teachApplyWaitCheckList'); //我的课程待审核课程
Route::rule('api/teachApplyUnAccessCheckList','index/Apply/teachApplyUnAccessCheckList'); //我的课程审核未通过课程
Route::rule('api/courseIntro','index/Apply/courseIntro'); //课程介绍显示
Route::rule('api/editCourseIntro','index/Apply/editCourseIntro'); //修改课程介绍
Route::rule('api/courseGuide','index/Apply/courseGuide'); //教学大纲显示
Route::rule('api/editCourseGuide','index/Apply/editCourseGuide'); //修改教学大纲
Route::rule('api/teachCalendar','index/Apply/teachCalendar'); //教学日历显示
//Route::rule('api/editTeachCalendar','index/Apply/editTeachCalendar'); //修改教学日历
Route::rule('api/upTeachSource','index/Apply/upTeachSource'); //上传教学资源
Route::rule('api/listTeachSource','index/Apply/listTeachSource'); //课程管理教学资源显示
Route::rule('api/deleteTeachSource','index/Apply/deleteTeachSource'); //删除教学资源
Route::rule('api/getCalendarCourse','index/Apply/getCalendarCourse'); //删除教学资源
Route::rule('api/getInfo','index/Apply/getInfo'); //课程介绍，----简介

/**
 * Collegee
 */
Route::rule('api/listCollege','index/College/listCollege'); //学院信息

/**
 * Permission
 */

Route::rule('api/ruleList','index/Permission/ruleList'); //角色权限poptip列表
Route::rule('api/editRuleList','index/Permission/editRuleList'); //角色权限编辑时，当前角色的权限
Route::rule('api/alterPermission','index/Permission/alterPermission'); //修改权限


/**
 * ScoreQuery
 */
Route::rule('api/ScoreQueryStudent','index/ScoreQuery/ScoreQueryStudent');   //成绩查询-学生页面
Route::rule('api/ScoreQueryTeacher','index/ScoreQuery/ScoreQueryTeacher');   //成绩查询-教师页面
Route::rule('api/CorrectionsScore','index/ScoreQuery/CorrectionsScore');     //成绩批改-教师页面
Route::rule('api/CorrectionsUpdate','index/ScoreQuery/CorrectionsUpdate');   //成绩批改-批改提交
Route::rule('api/experimentCorrScore','index/ScoreQuery/experimentCorrScore'); //课程统计分析图


/**
 * SchoolCalendar
 */ 

Route::rule('api/createCalendar','index/SchoolCalendar/createCalendar'); //创建校历
Route::rule('api/getCalendarList','index/SchoolCalendar/getCalendarList'); //校历列表
Route::rule('api/DelCalendarList','index/SchoolCalendar/DelCalendarList'); //校历列表-删除校历信息
Route::rule('api/getCalendarRangeTime','index/SchoolCalendar/getCalendarRangeTime'); //校历时间范围
Route::rule('api/setCalendarInfo','index/SchoolCalendar/setCalendarInfo'); //设置校历详情信息
Route::rule('api/getOneCalendarInfo','index/SchoolCalendar/getOneCalendarInfo'); //修改校历查询详情
Route::rule('api/getCurrentCalendarInfo','index/SchoolCalendar/getCurrentCalendarInfo'); //当前校历
Route::rule('api/modifyCalendarInfo','index/SchoolCalendar/modifyCalendarInfo'); //修改校历信息
Route::rule('api/manageSchedule','index/SchoolCalendar/manageSchedule'); //实验室开放情况管理
Route::rule('api/getTeachers','index/SchoolCalendar/getTeachers'); //获取老师信息
Route::rule('api/getData','index/SchoolCalendar/getData'); //校历中数据
Route::rule('api/teacherOrder','index/SchoolCalendar/teacherOrder'); //教师预约
Route::rule('api/getTeacherOrderData','index/SchoolCalendar/getTeacherOrderData'); //教师教学日历显示
Route::rule('api/updateOrder','index/SchoolCalendar/updateOrder'); //修改教学日历中的每一次安排
Route::rule('api/updateOrderList','index/SchoolCalendar/updateOrderList'); //教学日历修改记录列表
Route::rule('api/updateOrderInfo','index/SchoolCalendar/updateOrderInfo'); //教学日历修改记录详情
Route::rule('api/updateOrderCheck','index/SchoolCalendar/updateOrderCheck'); //教学日历修改审批
Route::rule('api/updateOrderDelete','index/SchoolCalendar/updateOrderDelete'); //教学日历修改删除



/**
 * Lab
 */ 

Route::rule('api/labList','index/Lab/labList'); //实验室列表
Route::rule('api/labQuery','index/Lab/labQuery'); //所有实验室列表
Route::rule('api/labAdd','index/Lab/labAdd'); //添加实验室
Route::rule('api/labEdit','index/Lab/labEdit'); //实验室详情查询
Route::rule('api/labUpdate','index/Lab/labUpdate'); //实验室信息修改
Route::rule('api/labDel','index/Lab/labDel'); //删除实验室




/*huanke模块*/

/*Login*/
Route::rule('api/checkLoginHuanke','huanke/Login/checkLoginHuanke'); //登录验证
Route::rule('api/lineUp','huanke/Login/lineUp'); //查询当前用户前面还有多少人在排队
Route::rule('api/cancelLineUp','huanke/Login/cancelLineUp'); //取消排队

/*User*/
Route::rule('api/huankeUserNumber','huanke/User/huankeUserNumber'); //查询使用次数和当前在线人数

/*Presentation*/
Route::rule('api/generationReport','huanke/Presentation/generationReport'); //生成实验报告
Route::rule('api/huankeScoreList','huanke/Presentation/huankeScoreList'); //查询成绩
Route::rule('api/downloadReport','huanke/Presentation/downloadReport'); //点击在线预览实验报告

/*Experimental*/
Route::rule('api/experimentalData','huanke/Experimental/experimentalData'); //接收实验数据并写入数据库



Route::rule('api/text','index/Text/index'); 
