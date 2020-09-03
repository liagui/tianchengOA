<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Pay_order_inside;

class OrderController extends Controller {
    //订单总览
    public function orderList(){
        $list = Pay_order_inside::orderList(self::$accept_data);
        return response()->json($list);
    }
    //手动报单
    public function handOrder(){
        $list = Pay_order_inside::handOrder(self::$accept_data);
        return response()->json($list);
    }
}
