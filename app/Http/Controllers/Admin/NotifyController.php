<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Pay_order_external;


class NotifyController extends Controller{

    //汇聚支付 回调
    public function hjnotify(){
        $order = Pay_order_external::where(['order_no' => $_GET['r2_OrderNo']])->first()->toArray();
        if($order['pay_status'] > 0){
            return "success";
        }
        file_put_contents('hjnotify.txt', '时间:'.date('Y-m-d H:i:s').print_r($_GET,true),FILE_APPEND);
        if($_GET['r6_Status'] == '100'){
            //只修改订单号
            $up = Pay_order_external::where(['id'=>$order['id']])->update(['pay_status'=>1,'pay_time'=>date('Y-m-d H:i:s')]);
            if($up){
                return "success";
            }
        }
        if($_GET['r6_Status'] == '101'){
            $up = Pay_order_external::where(['id'=>$order['id']])->update(['status'=>2]);
            if($up){
                return "success";
            }
        }
    }


}
