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
});
//后端登录注册接口
$router->group(['prefix' => 'admin' , 'namespace' => 'Admin', 'middleware'=> 'cors'], function () use ($router) {
    $router->post('register', 'AuthenticateController@register');
    $router->post('login', 'AuthenticateController@postLogin');
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

