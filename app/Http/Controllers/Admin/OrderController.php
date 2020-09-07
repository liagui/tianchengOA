<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Pay_order_inside;

class OrderController extends Controller {
    //总校&分校
    public function orderList(){
        $list = Pay_order_inside::orderList(self::$accept_data);
        return response()->json($list);
    }
    //未提交订单详情
    public function unsubmittedOrderDetail(){
        $list = Pay_order_inside::unsubmittedOrderDetail(self::$accept_data);
        return response()->json($list);
    }
    //手动报单
    public function handOrder(){
        $list = Pay_order_inside::handOrder(self::$accept_data);
        return response()->json($list);
    }
    //订单查看支付凭证
    public function orderVoucher(){
        $list = Pay_order_inside::orderVoucher(self::$accept_data);
        return response()->json($list);
    }
    //订单备注或驳回信息
    public function orderDetail(){
        $list = Pay_order_inside::orderDetail(self::$accept_data);
        return response()->json($list);
    }
    //被驳回订单
    public function rejectOrder(){
        $list = Pay_order_inside::rejectOrder(self::$accept_data);
        return response()->json($list);
    }
    //驳回订单进行操作
    public function anewOrder(){
        $list = Pay_order_inside::anewOrder(self::$accept_data);
        return response()->json($list);
    }

    //总校待确认订单*******************************************************
    public function awaitOrder(){
        $list = Pay_order_inside::awaitOrder(self::$accept_data);
        return response()->json($list);
    }
    //总校确认订单
    public function notarizeOrder(){
        $list = Pay_order_inside::notarizeOrder(self::$accept_data);
        return response()->json($list);
    }


    //分校订单************************************************************
    //分校未提交订单查询
    public function unsubmittedOrder(){
        $list = Pay_order_inside::unsubmittedOrder(self::$accept_data);
        return response()->json($list);
    }

    //分校进行提交
    public function DoSubmitted(){
        $list = Pay_order_inside::DoSubmitted(self::$accept_data);
        return response()->json($list);
    }
    //分校已提交订单进行取消提交
    public function submittedOrderCancel(){
        $list = Pay_order_inside::submittedOrderCancel(self::$accept_data);
        return response()->json($list);
    }

}
