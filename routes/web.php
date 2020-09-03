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
});
/*****************end**********************/

