<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->post('/', function () use ($router) {
    return $router->app->version();
});
//后台端路由接口
/*****************start**********************/
//后端登录注册接口

//$router->group(['prefix' => 'admin' , 'namespace' => 'Admin', 'middleware'=> 'cors'], function () use ($router) {
$router->group(['prefix' => 'admin' , 'namespace' => 'Admin','middleware'=> 'cors'], function () use ($router) {
    $router->post('login', 'AuthenticateController@postLogin');
    $router->post('bindMobile', 'AuthenticateController@bindMobile');//绑定手机号
    $router->post('doSendSms', 'AuthenticateController@doSendSms');//发送短信
    $router->get('doExcelDatum', 'ExcelController@doExcelDatum');//学员资料导出
    $router->group(['prefix' => 'datum'], function () use ($router) {
        $router->post('doExcelDatum', 'ExcelController@doExcelDatum');//学员资料导出

    });
    $router->post('getRegionList', 'StudentDatumController@getRegionLists');//获取所有地区

    //项目管理部分(dzj)
    $router->group(['prefix' => 'project'], function () use ($router) {
        $router->post('getProjectSubjectList', 'ProjectController@getProjectSubjectList');       //项目筛选学科列表接口
        $router->post('getCourseList', 'ProjectController@getCourseList');                       //课程列表接口
    });
    $router->post('paylist', 'OrderController@paylist');//支付通道
    $router->post('oapay', 'OrderController@oapay');//支付
    $router->get('hjnotify', 'NotifyController@hjnotify');//汇聚 支付回调

});
//后端登录权限认证相关接口
//
$router->group(['prefix' => 'admin' , 'namespace' => 'Admin','middleware'=> ['jwt.auth', 'cors','api'] ], function () use ($router) {

     //订单管理（szw）
    $router->group(['prefix' => 'order'], function () use ($router) {
        //总校&分校
        $router->post('orderlist', 'OrderController@orderList');//订单总览
        $router->post('awaitOrder', 'OrderController@awaitOrder');//总校待确认订单&&分校已提交
        $router->post('handOrder', 'OrderController@handOrder');//手动报单
        $router->post('orderVoucher', 'OrderController@orderVoucher');//订单查看支付凭证
        $router->post('orderDetail', 'OrderController@orderDetail');//订单备注或驳回信息
        $router->post('rejectOrder', 'OrderController@rejectOrder');//分校&总校被驳回订单列表
        $router->post('anewOrder', 'OrderController@anewOrder');//被驳回订单  取消订单 操作
        //总校
        $router->post('sureOrderList', 'OrderController@sureOrderList');//总校确认订单列表
        $router->post('notarizeOrder', 'OrderController@notarizeOrder');//总校确认&取消订单
        $router->post('sureOrder', 'OrderController@sureOrder');//总校确认订单详情
        $router->post('unpaidOrder', 'OrderController@unpaidOrder');//总校未支付订单
        $router->post('DorejectOrder', 'OrderController@DorejectOrder');//总校进行驳回
        //分校
        $router->post('unsubmittedOrder', 'OrderController@unsubmittedOrder');//分校未提交订单
        $router->post('unsubmittedOrderDetail', 'OrderController@unsubmittedOrderDetail');//分校未提交详情
        $router->post('DoSubmitted', 'OrderController@DoSubmitted');//分校未提交订单进行提交
        $router->post('submittedOrderCancel', 'OrderController@submittedOrderCancel');//分校已提交订单进行取消
        //退费订单操作
        $router->post('returnOrder', 'OrderController@returnOrder');//退款订单列表
        $router->post('returnOne', 'OrderController@returnOne');//退款订单单条详情
        $router->post('returnWhereOne', 'OrderController@returnWhereOne');//根据用户名，手机号，项目学科查订单
        $router->post('initOrder', 'OrderController@initOrder');//添加退款订单
        $router->post('seeOrder', 'OrderController@seeOrder');//查看退款凭证
        $router->post('amendOrder', 'OrderController@amendOrder');//修改退费状态
        $router->post('remitOrder', 'OrderController@remitOrder');//修改打款状态


    });
    //项目管理部分(dzj)
    $router->group(['prefix' => 'project'], function () use ($router) {
        $router->post('doInsertProjectSubject', 'ProjectController@doInsertProjectSubject');     //添加项目/学科的方法
        $router->post('doUpdateProjectSubject', 'ProjectController@doUpdateProjectSubject');     //修改项目/学科的方法
        $router->post('getProjectSubjectInfoById', 'ProjectController@getProjectSubjectInfoById'); //项目学科详情接口
        $router->post('doInsertCourse', 'ProjectController@doInsertCourse');                     //添加课程的方法
        $router->post('doUpdateCourse', 'ProjectController@doUpdateCourse');                     //修改课程的方法
        $router->post('getCourseInfoById', 'ProjectController@getCourseInfoById');               //课程详情接口
        $router->post('getCourseAllList', 'ProjectController@getCourseAllList');                 //课程全部列表接口
        $router->post('doInsertRegion', 'ProjectController@doInsertRegion');                     //添加地区的方法
        $router->post('doUpdateRegion', 'ProjectController@doUpdateRegion');                     //修改地区的方法
        $router->post('getRegionInfoById', 'ProjectController@getRegionInfoById');               //地区报名费详情方法
        $router->post('getRegionList', 'ProjectController@getRegionList');                       //地区列表接口
        $router->post('doInsertEducation', 'ProjectController@doInsertEducation');               //添加院校的方法
        $router->post('doUpdateEducation', 'ProjectController@doUpdateEducation');               //修改院校的方法
        $router->post('getEducationList', 'ProjectController@getEducationList');                 //院校列表接口
        $router->post('getSchoolInfoById', 'ProjectController@getSchoolInfoById');               //院校详情接口
        $router->post('doInsertMajor', 'ProjectController@doInsertMajor');                       //添加专业的方法
        $router->post('doUpdateMajor', 'ProjectController@doUpdateMajor');                       //修改专业的方法
        $router->post('getNajorInfoById', 'ProjectController@getNajorInfoById');                 //专业详情接口
        $router->post('getMajorList', 'ProjectController@getMajorList');                         //专业列表接口
    });

    //开课管理部分(dzj)
    $router->group(['prefix' => 'order'], function () use ($router) {
        $router->post('getOpenCourseList', 'OrderController@getOpenCourseList');                 //开课管理列表接口
        $router->post('getOpenCourseInfo', 'OrderController@getOpenCourseInfo');                 //订单详情列表接口
        $router->post('doMakeSureOpenCourse', 'OrderController@doMakeSureOpenCourse');           //确认开课接口
        $router->post('getStudentCourseInfoById', 'OrderController@getStudentCourseInfoById');   //确认开课详情接口
    });

    //财务管理部分(dzj)
    $router->group(['prefix' => 'finance'], function () use ($router) {
        $router->post('getIncomeeList', 'OrderController@getIncomeeList');                       //财务管理-收入详情
    });


    //分校管理部分(dzj)
    $router->group(['prefix' => 'school'], function () use ($router) {
        $router->post('doInsertSchool', 'SchoolController@doInsertSchool');                      //添加分校接口
        $router->post('doUpdateSchool', 'SchoolController@doUpdateSchool');                      //修改分校接口
        $router->post('getSchoolInfoById', 'SchoolController@getSchoolInfoById');                //分校详情接口
        $router->post('getSchoolListByLevel', 'SchoolController@getSchoolListByLevel');          //上级分校列表接口
        $router->post('getSchoolList', 'SchoolController@getSchoolList');                        //分校列表接口
        $router->post('doDelSchool', 'SchoolController@doDelSchool');                            //分校删除接口  （lys）
        $router->post('doOpenSchool', 'SchoolController@doOpenSchool');                          //分校启用禁用接口（lys）
        $router->post('doLookSchool', 'SchoolController@doLookSchool');                          //是否查看下属分校内容接口（lys）
    });
    $router->post('getCommonList', 'CommonController@getCommonList');  //OA项目公共参数接口

    //上传到OSS图片接口
    $router->post('doUploadOssImage', 'CommonController@doUploadOssImage'); 




    $router->post('diff', 'TestController@diff');
    $router->post('test', 'TestController@index');
    $router->post('ArticleLead', 'ArticleController@ArticleLead');//文章导入
    $router->post('ArticleTypeLead', 'ArticletypeController@ArticleTypeLead');//文章分类导入
    $router->post('ArticleToType', 'ArticleController@ArticleToType');//文章关联分类
    $router->get('liveCallBack', 'LiveChildController@listenLive');
    $router->post('liveCallBack', 'LiveChildController@listenLive');//直播回调状态
    $router->post('orderUpOaForId', 'OrderController@orderUpOaForId');//订单修改oa状态
    $router->post('orderUpinvalid', 'OrderController@orderUpinvalid');//订单无效修改
    $router->post('listType', 'ArticleController@listType');//分类列表
    $router->post('schoolLists', 'ArticleController@schoolLists');//学校列表
    $router->post('courseType', 'CourseController@courseType');//根据分类查课程
    $router->post('orderForStudent', 'OrderController@orderForStudent');//订单通过学员查询
    /*** 物料管理start ***/
    //获取物料列表
    $router->post('getMaterialList', 'MaterialController@getMaterialList');
    //创建物料需求
    $router->post('Materialadd', 'MaterialController@Materialadd');
    //更新物料需求
    $router->post('updateMaterialOne', 'MaterialController@updateMaterialOne');
    //获取单条物料
    $router->post('getMaterialOne', 'MaterialController@getMaterialOne');
    //确认物料信息
    $router->post('Materialupdate', 'MaterialController@Materialupdate');
    //获取确认物料信息
    $router->post('getMaterial', 'MaterialController@getMaterial');
    //获取物料提交人信息
    $router->post('getsubmit', 'MaterialController@getsubmit');
    /*** 物料管理end ***/

    /*** 学员管理start ***/
    //业绩查询 班主任自己的业绩
    $router->post('getStudentPerformance', 'StudentController@getStudentPerformance');
    //学员总览 班主任自己的学员
    $router->post('getStudent', 'StudentController@getStudent');
    //学员公海 被放入公海的所有学员订单
    $router->post('getStudentSeas', 'StudentController@getStudentSeas');
    //更新资料收集状态
    $router->post('updateConsigneeStatsu', 'StudentController@updateConsigneeStatsu');
    /*** 学员管理end ***/

    /*** 班主任管理start ***/
    //学员状态
    $router->post('getStudentStatus', 'StudentController@getStudentStatus');
    //跟单
    $router->post('documentary', 'StudentController@documentary');
    //获取跟单记录
    $router->post('getdocumentary', 'StudentController@getdocumentary');
    //转单
    $router->post('transferOrder', 'StudentController@transferOrder');
    //业绩总览
    $router->post('getTeacherPerformance', 'TeacherController@getTeacherPerformance');
    //业绩详情
    $router->post('getTeacherPerformanceOne', 'TeacherController@getTeacherPerformanceOne');

    //创建班主任
    $router->post('createTeacher', 'TeacherController@createTeacher');
    //获取班主任列表
    $router->post('getTeacherList', 'TeacherController@getTeacherList');
    //获取班主任列表
    $router->post('getTeacherListAll', 'TeacherController@getTeacherListAll');

    //更改班主任值班状态
    $router->post('updateTeacherStatus', 'TeacherController@updateTeacherStatus');
    //更改班主任离职状态
    $router->post('updateTeacherSeasStatus', 'TeacherController@updateTeacherSeasStatus');

    /*** 班主任管理end ***/
     $router->group(['prefix' => 'payset'], function () use ($router) {
        $router->post('doUpdateWxState', 'PaySetController@doUpdateWxState');                 //更改微信状态
        $router->post('doUpdateZfbState', 'PaySetController@doUpdateZfbState');               //更改支付宝状态
        $router->post('doUpdateHjState', 'PaySetController@doUpdateHjState');                 //更改汇聚状态
        $router->post('getZfbById', 'PaySetController@getZfbConfig');                       //添加支付宝配置(获取)
        $router->post('getWxById', 'PaySetController@getWxConfig');                         //添加微信配置(获取)
        $router->post('getHjById', 'PaySetController@getHjConfig');                         //添加汇聚配置(获取)
        $router->post('doZfbUpdate', 'PaySetController@doZfbConfig');                       //添加/修改支付宝配置
        $router->post('doWxUpdate', 'PaySetController@doWxConfig');                         //添加/修改微信配置
        $router->post('doHjUpdate', 'PaySetController@doHjConfig');                         //添加/修改汇聚配置
    });



    $router->group(['prefix' => 'channel'], function () use ($router) {
        $router->post('getList', 'ChannelController@getList');                                 //获取通道列表
        $router->post('doChannelInsert', 'ChannelController@doChannelInsert');                 //添加支付通道
        $router->post('getChannelPayById', 'ChannelController@getChannelPayById');             //编辑支付通道（获取）
        $router->post('doUpdateChannelPay', 'ChannelController@doUpdateChannelPay');           //编辑支付通道
        $router->post('doUseChannelPay','ChannelController@doUseChannelPay');                 //选中支付通过
    });

    $router->group(['prefix' => 'adminuser'], function () use ($router) {
        $router->post('getAdminUserList', 'AdminUserController@getAdminUserList');            //获取后台用户列表方法
        $router->post('upUserForbidStatus', 'AdminUserController@upUserForbidStatus');        //更改账号状态方法（启用禁用）
        $router->post('upUserDelStatus', 'AdminUserController@upUserDelStatus');              //更改账号状态方法 (删除)
        $router->post('upUseStatus', 'AdminUserController@upUseStatus');                       //更改账号状态使用方法
        $router->post('getInsertAdminUser', 'AdminUserController@getInsertAdminUser');         //获取添加账号信息（school，roleAuth）方法
        $router->post('doInsertAdminUser', 'AdminUserController@doInsertAdminUser');          //添加账号方法
        $router->post('getAuthList', 'AdminUserController@getAuthList');                      //获取角色列表方法
        $router->post('getAdminUserUpdate', 'AdminUserController@getAdminUserUpdate');        //获取账号信息（编辑）
        $router->post('doAdminUserUpdate', 'AdminUserController@doAdminUserUpdate');          //编辑账号信息
        $router->post('doAdminUserUpdatePwd', 'AdminUserController@doAdminUserUpdatePwd');    //修改用户密码的接口
    });

    $router->group(['prefix' => 'role'], function () use ($router) {
        $router->post('getList', 'RoleController@getList');              //角色列表
        $router->post('doRoleDel', 'RoleController@doRoleDel');          //软删除
        $router->post('getRoleInsert', 'RoleController@getRoleInsert');  //添加角色（获取）
        $router->post('doRoleInsert', 'RoleController@doRoleInsert');    //添加角色
        $router->post('getRoleById', 'RoleController@getRoleUpdate');  //编辑角色（获取）
        $router->post('doRoleUpdate', 'RoleController@doRoleUpdate');  //编辑角色
    });

    $router->group(['prefix' => 'school'], function () use ($router) {
        $router->post('getList', 'SchoolController@getList');              //学校列表（仅限搜索用）
        // $router->post('doSchoolDel', 'SchoolController@doSchoolDel');          //软删除
        // $router->post('doSchoolForbid', 'SchoolController@doSchoolForbid');  //启用禁用
        // $router->post('doSchoolLook', 'SchoolController@doSchoolLook');  //是否观看其他网校数据
        // $router->post('getSchoolList', 'SchoolController@schoolList');    //网校列表（添加、修改）
        // $router->post('doInsertSchool', 'SchoolController@doInsertSchool');    //添加网校
        // $router->post('getSchoolById', 'SchoolController@getSchoolUpdate');  //编辑网校（获取）
        // $router->post('doSchoolUpdate', 'SchoolController@doSchoolUpdate');  //编辑网校
    });
    $router->group(['prefix' => 'datum'], function () use ($router) {
        $router->post('getList', 'StudentDatumController@getList');              //资料列表
        $router->post('doDatumInsert', 'StudentDatumController@doDatumInsert');        //资料添加
        $router->post('getDatumById', 'StudentDatumController@getDatumById');  //资料查看
        $router->post('doUpdateAudit', 'StudentDatumController@doUpdateAudit');  //审核状态
        $router->post('getInitiatorById', 'StudentDatumController@getInitiatorById');  //获取发起人信息
        $router->post('getRegionList', 'StudentDatumController@getRegionList');  //获取户籍地区地址
        $router->post('getDatumCount', 'StudentDatumController@getDatumCount');  //获取资料数量
    });

});

/*****************end**********************/

