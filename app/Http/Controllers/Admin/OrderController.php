<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Pay_order_external;
use App\Models\Pay_order_inside;

class OrderController extends Controller {
    //总校&分校
    public function orderList(){
        $list = Pay_order_inside::orderList(self::$accept_data);
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
    //订单详情
    public function sureOrder(){
        $list = Pay_order_inside::sureOrder(self::$accept_data);
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
    //oa支付
    public function oapay(){

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
}
