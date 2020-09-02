<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Pay_order_inside;

class OrderController extends Controller {
    //订单总览
    public function orderList(){
        return $this->response('123456789');
        $list = Pay_order_inside::orderList(self::$accept_data);
        return $this->response($list);
    }
}
