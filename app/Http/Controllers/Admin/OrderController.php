<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Channel;
use App\Models\Course;
use App\Models\Order;
use App\Models\Pay_order_external;
use App\Models\Pay_order_inside;
use App\Models\PaySet;
use App\Models\Refund_order;

class OrderController extends Controller {
    //总校&分校
    public function orderList(){
        $schoolarr = $this->underlingLook(AdminLog::getAdminInfo()->admin_user->school_id);
        $list = Pay_order_inside::orderList(self::$accept_data,$schoolarr['data']);
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
        $schoolarr = $this->underlingLook(AdminLog::getAdminInfo()->admin_user->school_id);
        $list = Pay_order_inside::rejectOrder(self::$accept_data,$schoolarr['data']);
        return response()->json($list);
    }
    //驳回订单进行操作
    public function anewOrder(){
        $list = Pay_order_inside::anewOrder(self::$accept_data);
        return response()->json($list);
    }

    //总校待确认订单*******************************************************
    public function awaitOrder(){
        $schoolarr = $this->underlingLook(AdminLog::getAdminInfo()->admin_user->school_id);
        $list = Pay_order_inside::awaitOrder(self::$accept_data,$schoolarr['data']);
        return response()->json($list);
    }
    //订单详情
    public function sureOrder(){
        $list = Pay_order_inside::sureOrder(self::$accept_data);
        return response()->json($list);
    }
    //总校确认订单列表
    public function sureOrderList(){
        $schoolarr = $this->underlingLook(AdminLog::getAdminInfo()->admin_user->school_id);
        $list = Pay_order_inside::sureOrderList(self::$accept_data,$schoolarr['data']);
        return response()->json($list);
    }
    //总校确认订单
    public function notarizeOrder(){
        $list = Pay_order_inside::notarizeOrder(self::$accept_data);
        return response()->json($list);
    }
    //总校未支付订单列表
    public function unpaidOrder(){
        $list = Pay_order_external::unpaidOrder(self::$accept_data);
        return response()->json($list);
    }
    //总校订单进行驳回
    public function DorejectOrder(){
        $list = Pay_order_inside::DorejectOrder(self::$accept_data);
        return response()->json($list);
    }

    //分校订单************************************************************
    //分校未提交订单查询
    public function unsubmittedOrder(){
        $list = Pay_order_inside::unsubmittedOrder(self::$accept_data);
        return response()->json($list);
    }
    //未提交订单详情
    public function unsubmittedOrderDetail(){
        $list = Pay_order_inside::unsubmittedOrderDetail(self::$accept_data);
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

    /*=============================================*/
    //退费订单list
    public function returnOrder(){
        $list = Refund_order::returnOrder(self::$accept_data);
        return response()->json($list);
    }
    //单条详情
    public function returnOne(){
        $list = Refund_order::returnOne(self::$accept_data);
        return response()->json($list);
    }
    //根据条件差关联订单
    public function returnWhereOne(){
        $list = Refund_order::returnWhereOne(self::$accept_data);
        return response()->json($list);
    }
    //add
    public function initOrder(){
        $list = Refund_order::initOrder(self::$accept_data);
        return response()->json($list);
    }
    //退款凭证
    public function seeOrder(){
        $list = Refund_order::seeOrder(self::$accept_data);
        return response()->json($list);
    }
    //修改退费状态
    public function amendOrder(){
        $list = Refund_order::amendOrder(self::$accept_data);
        return response()->json($list);
    }
    //修改打款状态
    public function remitOrder(){
        $list = Refund_order::remitOrder(self::$accept_data);
        return response()->json($list);
    }

    /*
     * @param  description   开课管理列表接口
     * @param  参数说明       body包含以下参数[
     *     category_id       项目-学科大小类(例如:[1,2])
     *     school_id         分校id
     *     order_type        订单类型(1.课程订单2.报名订单3.课程+报名订单)
     *     open_class_status 开课状态(0不开课 1开课)
     *     keywords          订单号/手机号/姓名
     * ]
     * @param author    dzj
     * @param ctime     2020-09-07
     * return string
     */
    public function getOpenCourseList(){
        //获取提交的参数
        try{
            //获取专业列表
            $data = Pay_order_inside::getOpenCourseList(self::$accept_data);
            if($data['code'] == 200){
                return response()->json(['code' => 200 , 'msg' => '获取列表成功' , 'data' => $data['data']]);
            } else {
                return response()->json(['code' => $data['code'] , 'msg' => $data['msg']]);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }

    /*
     * @param  description   开课管理订单详情接口
     * @param  参数说明       body包含以下参数[
     *      open_id        开课得管理id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-08
     * return string
     */
    public function getOpenCourseInfo(){
        //获取提交的参数
        try{
            //获取专业列表
            $data = Pay_order_inside::getOpenCourseInfo(self::$accept_data);
            if($data['code'] == 200){
                return response()->json(['code' => 200 , 'msg' => '获取详情成功' , 'data' => $data['data']]);
            } else {
                return response()->json(['code' => $data['code'] , 'msg' => $data['msg']]);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }

    /*
     * @param  description   开课管理-确认开课方法
     * @param  参数说明       body包含以下参数[
     *     open_id           开课id
     *     project_id        项目id
     *     subject_id        学科id
     *     course_id         课程id
     *     student_name      学员名称
     *     phone             手机号
     * ]
     * @param author    dzj
     * @param ctime     2020-09-07
     * return string
     */
    public function doMakeSureOpenCourse() {
        //获取提交的参数
        try{
            $data = Pay_order_inside::doMakeSureOpenCourse(self::$accept_data);
            if($data['code'] == 200){
                return response()->json(['code' => 200 , 'msg' => '操作成功']);
            } else {
                return response()->json(['code' => $data['code'] , 'msg' => $data['msg']]);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }

    /*
     * @param  description   确认开课详情接口
     * @param  参数说明       body包含以下参数[
     *       open_id         开课得管理id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-08
     * return string
     */
    public function getStudentCourseInfoById(){
        //获取提交的参数
        try{
            $data = Pay_order_inside::getStudentCourseInfoById(self::$accept_data);
            if($data['code'] == 200){
                return response()->json(['code' => 200 , 'msg' => '获取详情成功' , 'data' => $data['data']]);
            } else {
                return response()->json(['code' => $data['code'] , 'msg' => $data['msg']]);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }


    //支付信息
    public function paylist(){
        $list = Channel::where(['is_use'=>0])->first();
        $paylist = PaySet::where(['channel_id'=>$list['id']])->first();
        $status=[];
        if($paylist['zfb_pay_state'] == 1){
            array_push($status,'1');
        }
        if($paylist['wx_pay_state'] == 1){
            array_push($status,'2');
        }
        if($paylist['hj_zfb_pay_state'] == 1){
            array_push($status,'3');
        }
        if($paylist['hj_wx_pay_state'] == 1){
            array_push($status,'4');
        }
        return response()->json(['code' => 200 , 'msg' => '获取成功' , 'data' =>$list]);
    }
    //支付   入库   并生成二维码
    public function oapay(){
        $data = self::$accept_data;
        if(!isset($data['name']) || empty($data['name'])){
            return response()->json(['code' => 201 , 'msg' => '姓名不能为空']);
        }
        if(!isset($data['mobile']) || empty($data['mobile'])){
            return response()->json(['code' => 201 , 'msg' => '手机号不能为空']);
        }
        if(!isset($data['pay_price']) || empty($data['pay_price']) || $data['pay_price'] <=0){
            return response()->json(['code' => 201 , 'msg' => '金额不正确']);
        }
        if(!isset($data['pay_type']) || empty($data['pay_type'])){
            return response()->json(['code' => 201 , 'msg' => '支付方式不正确']);
        }
        //生成订单
        $insert['name'] = $data['name'];
        $insert['mobile'] = $data['mobile'];
        $insert['order_no'] = date('YmdHis', time()) . rand(1111, 9999);
        $insert['create_time'] = date('Y-m-d H:i:s');
        $insert['pay_price'] = $data['pay_price'];
        $insert['course_id'] = $data['course_id'];
        $insert['project_id'] = $data['project_id'];
        $insert['subject_id'] = $data['subject_id'];
        $insert['pay_status'] = 0;  //支付状态
        $insert['pay_type'] = $data['pay_type'];
        $insert['confirm_status'] = 0;
        $insert['school_id'] = 0;
        $insert['begin_class'] = 0;
        $insert['collecting_data'] = 0;
        $insert['del_flag'] = 0;
        $insert['status'] = 0;
        $add = Pay_order_external::insertGetId($insert);
        if($add){
            $course = Course::where(['id'=>$data['course_id']])->first();
            //支付宝
            if($data['pay_type'] == 1){

            }
            //微信
            if($data['pay_type'] == 2){

            }
            //汇聚支付宝
            if($data['pay_type'] == 3){
                $list = Channel::where(['is_ise'=>0])->firts();
                $paylist = PaySet::where(['channel_id'=>$list['id']])->first();
                $notify = 'AB|'."http://".$_SERVER['HTTP_HOST']."/admin/notify/hjnotify";
                $pay=[
                    'p0_Version'=>'1.0',
                    'p1_MerchantNo'=> $paylist['hj_commercial_tenant_number'],
                    'p2_OrderNo'=>$insert['order_no'],
                    'p3_Amount'=>$data['pay_price'],
                    'p4_Cur'=>1,
                    'p5_ProductName'=>$course['title'],
                    'p9_NotifyUrl'=>$notify,
                    'q1_FrpCode'=>'ALIPAY_NATIVE',
                    'q4_IsShowPic'=>1,
                    'qa_TradeMerchantNo'=>$paylist['hj_zfb_commercial_tenant_deal_number']
                ];
                $str = $paylist['hj_md_key'];
                $token = $this->hjHmac($pay,$str);
                $pay['hmac'] = $token;
                $zfbpay = $this->hjpost($pay);
                $zfbpayarr = json_decode($zfbpay,true);
                file_put_contents('zfbhjpay.txt', '时间:'.date('Y-m-d H:i:s').print_r($zfbpayarr,true),FILE_APPEND);
                if($zfbpayarr['ra_Code'] == 100){
                    return response()->json(['code' => 200, 'msg' => '预支付订单生成成功','data'=>$zfbpayarr['rd_Pic']]);
                }else{
                    return response()->json(['code' => 202, 'msg' => '暂未开通']);
                }
            }
            //汇聚微信
            if($data['pay_type'] == 4){
                $list = Channel::where(['is_ise'=>0])->firts();
                $paylist = PaySet::where(['channel_id'=>$list['id']])->first();
                $notify = 'AB|'."http://".$_SERVER['HTTP_HOST']."/admin/notify/hjnotify";
                $pay=[
                    'p0_Version'=>'1.0',
                    'p1_MerchantNo'=> $paylist['hj_commercial_tenant_number'],
                    'p2_OrderNo'=>$insert['order_no'],
                    'p3_Amount'=>$data['pay_price'],
                    'p4_Cur'=>1,
                    'p5_ProductName'=>$course['title'],
                    'p9_NotifyUrl'=>$notify,
                    'q1_FrpCode'=>'WEIXIN_NATIVE',
                    'q4_IsShowPic'=>1,
                    'qa_TradeMerchantNo'=>$paylist['hj_wx_commercial_tenant_deal_number']
                ];
                $str = $paylist['hj_md_key'];
                $token = $this->hjHmac($pay,$str);
                $pay['hmac'] = $token;
                $wxpay = $this->hjpost($pay);
                $wxpayarr = json_decode($wxpay,true);
                file_put_contents('wxhjpay.txt', '时间:'.date('Y-m-d H:i:s').print_r($wxpayarr,true),FILE_APPEND);
                if($wxpayarr['ra_Code'] == 100){
                    return response()->json(['code' => 200, 'msg' => '预支付订单生成成功','data'=>$wxpayarr['rd_Pic']]);
                }else{
                    return response()->json(['code' => 202, 'msg' => '暂未开通']);
                }
            }
        }
    }
    //汇聚签名
    public function hjHmac($arr,$str){
        $newarr = '';
        foreach ($arr as $k=>$v){
            $newarr =$newarr.$v;
        }
        return md5($newarr.$str);
    }
    public function hjpost($data){
        //简单的curl
        $ch = curl_init("https://www.joinpay.com/trade/uniPayApi.action");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
