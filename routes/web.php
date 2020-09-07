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
//无需任何验证 操作接口
$router->group(['prefix' => 'admin' , 'namespace' => 'Admin'], function () use ($router) {
    $router->get('orderForExceil', 'OrderController@orderForExceil');//导出订单exceil
    $router->post('orderlist', 'OrderController@orderList');//ceshijiekou
    $router->post('handOrder', 'OrderController@handOrder');//手动报单

});
//后端登录注册接口
$router->group(['prefix' => 'admin' , 'namespace' => 'Admin', 'middleware'=> 'cors'], function () use ($router) {
    $router->post('login', 'AuthenticateController@postLogin');

    //项目管理部分(dzj)
    $router->group(['prefix' => 'project'], function () use ($router) {
        $router->post('doInsertProjectSubject', 'ProjectController@doInsertProjectSubject');     //添加项目/学科的方法
        $router->post('doUpdateProjectSubject', 'ProjectController@doUpdateProjectSubject');     //修改项目/学科的方法
        $router->post('doInsertCourse', 'ProjectController@doInsertCourse');                     //添加课程的方法
        $router->post('doUpdateCourse', 'ProjectController@doUpdateCourse');                     //修改课程的方法
        $router->post('getProjectSubjectList', 'ProjectController@getProjectSubjectList');       //项目筛选学科列表接口
        $router->post('getCourseList', 'ProjectController@getCourseList');                       //课程列表接口
        $router->post('doInsertRegion', 'ProjectController@doInsertRegion');                     //添加地区的方法
        $router->post('doUpdateRegion', 'ProjectController@doUpdateRegion');                     //修改地区的方法
        $router->post('getRegionList', 'ProjectController@getRegionList');                       //地区列表接口
        $router->post('doInsertEducation', 'ProjectController@doInsertEducation');               //添加院校的方法
        $router->post('doUpdateEducation', 'ProjectController@doUpdateEducation');               //修改院校的方法
        $router->post('getEducationList', 'ProjectController@getEducationList');                 //院校列表接口
    });
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
    //确认物料信息
    $router->post('Materialupdate', 'MaterialController@Materialupdate');
    //获取确认物料信息
    $router->post('getMaterial', 'MaterialController@getMaterial');
    //获取物料提交人信息
    $router->post('getsubmit', 'MaterialController@getsubmit');

    /*** 学员管理start ***/

    //业绩查询 班主任自己的业绩
    $router->post('getStudentPerformance', 'StudentController@getStudentPerformance');
    //学员总览 班主任自己的学员
    $router->post('getStudent', 'StudentController@getStudent');
    //学员公海 被放入公海的所有学员订单
    $router->post('getStudentSeas', 'StudentController@getStudentSeas');
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
    //更改班主任值班状态
    $router->post('updateTeacherStatus', 'TeacherController@updateTeacherStatus');

    /*** 班主任管理end ***/

});
//后端登录权限认证相关接口
//
$router->group(['prefix' => 'admin' , 'namespace' => 'Admin' ], function () use ($router) {
    $router->post('bindMobile', 'AdminController@bindMobile');//绑定手机号
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


});
/*****************end**********************/

