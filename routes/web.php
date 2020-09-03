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

    /*** 学员管理end ***/

    /*** 班主任管理start ***/
    //创建班主任
    $router->post('createTeacher', 'TeacherController@createTeacher');
    //获取班主任列表
    $router->post('getTeacherList', 'TeacherController@getTeacherList');
    //更改班主任值班状态
    $router->post('updateTeacherStatus', 'TeacherController@updateTeacherStatus');

    /*** 班主任管理end ***/

});
//后端登录权限认证相关接口
$router->group(['prefix' => 'admin' , 'namespace' => 'Admin' , 'middleware'=> ['jwt.auth', 'cors','api']], function () use ($router) {




});
/*****************end**********************/

