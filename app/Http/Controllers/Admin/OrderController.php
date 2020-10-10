<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Channel;
use App\Models\Course;
use App\Models\Pay_order_external;
use App\Models\Pay_order_inside;
use App\Models\PaySet;
use App\Models\Refund_order;
use App\Tools\AlipayFactory;
use App\Tools\Hfcfcademo;
use App\Tools\Hfpay;
use App\Tools\HuifuCFCA;
use App\Tools\QRcode;
use App\Tools\Ylpay;

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
    //分校被驳回订单重新提交
    public function branchsubmittedOrderCancel(){
        $list = Pay_order_inside::branchsubmittedOrderCancel(self::$accept_data);
        return response()->json($list);
    }

    /*=============================================*/
    //退费订单list
    public function returnOrder(){
        $schoolarr = $this->underlingLook(AdminLog::getAdminInfo()->admin_user->school_id);
        $list = Refund_order::returnOrder(self::$accept_data,$schoolarr['data']);
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
    //关联退费订单
    public function relevanceOrder(){
        $list = Refund_order::relevanceOrder(self::$accept_data);
        return response()->json($list);
    }
    //关联支付凭证
    public function relevanceVoucher(){
        $list = Refund_order::relevanceVoucher(self::$accept_data);
        return response()->json($list);
    }
    /*=================核对订单====================================*/
    //核对订单列表
    public function auditOrder(){
        $list = Pay_order_inside::auditOrder(self::$accept_data);
        return response()->json($list);
    }
    //根据类型查询账户号
    public function offlinepay(){
        $list = Pay_order_inside::offlinepay(self::$accept_data);
        return response()->json($list);
    }
    //修改
    public function offlineing(){
        $list = Pay_order_inside::offlineing(self::$accept_data);
        return response()->json($list);
    }
    //获取支付列表数组
    public function paylistarr(){
      $list = Pay_order_inside::paylistarr();
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
    /*
     * @param  description   财务管理-总校收入详情
     * @param  参数说明       body包含以下参数[
     *     education_id      院校id
     *     project_id        项目id
     *     subject_id        学科id
     *     course_id         课程id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-18
     * return string
     */
    public function getIncomeeList(){
        //获取提交的参数
        try{
            //获取院校id(1,2,3)
            $school_id  = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0;
            $school_arr = parent::underlingLook($school_id);

            //分校的id传递
            self::$accept_data['schoolId'] = $school_arr['data'];

            //获取专业列表
            $data = Pay_order_inside::getIncomeeList(self::$accept_data);
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
     * @param  description   财务管理-分校收入详情
     * @param  参数说明       body包含以下参数[
     *     education_id      院校id
     *     category_id       项目-学科大小类(例如:[1,2])
     *     course_id         课程id
     *     search_time       搜索时间(例如:2020-09-01至2020-09-20)
     * ]
     * @param author    dzj
     * @param ctime     2020-09-21
     * return string
     */
    public function getBranchSchoolIncomeeList(){
        //获取提交的参数
        try{
            //获取院校id(1,2,3)
            $school_id  = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0;
            $school_arr = parent::underlingLook($school_id);

            //分校的id传递
            self::$accept_data['schoolId'] = $school_arr['data'];

            //获取专业列表
            $data = Pay_order_inside::getBranchSchoolIncomeeList(self::$accept_data);
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
     * @param  description   财务管理-分校收入详情-已确认订单
     * @param  参数说明       body包含以下参数[
     *     school_id         分校id
     *     order_time        订单时间
     * ]
     * @param author    dzj
     * @param ctime     2020-09-21
     * return string
     */
    public function getBranchSchoolConfirmOrderList(){
        //获取提交的参数
        try{
            //获取院校id(1,2,3)
            $school_id  = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0;
            $school_arr = parent::underlingLook($school_id);

            //分校的id传递
            self::$accept_data['schoolId'] = $school_arr['data'];

            //获取专业列表
            $data = Pay_order_inside::getBranchSchoolConfirmOrderList(self::$accept_data);
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
     * @param  description   财务管理-分校收入详情-已退费订单
     * @param  参数说明       body包含以下参数[
     *     school_id         分校id
     *     order_time        订单时间
     * ]
     * @param author    dzj
     * @param ctime     2020-09-21
     * return string
     */
    public function getBranchSchoolRefundOrderList(){
        //获取提交的参数
        try{
            //获取院校id(1,2,3)
            $school_id  = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0;
            $school_arr = parent::underlingLook($school_id);

            //分校的id传递
            self::$accept_data['schoolId'] = $school_arr['data'];

            //获取专业列表
            $data = Pay_order_inside::getBranchSchoolRefundOrderList(self::$accept_data);
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
     * @param  description   财务管理-分校订单明细公共接口
     * @param  参数说明       body包含以下参数[
     *     school_id         分校id
     *     order_time        订单时间
     * ]
     * @param author    dzj
     * @param ctime     2020-09-21
     * return string
     */
    public function getBranchSchoolOrderInfo(){
        //获取提交的参数
        try{
            //获取院校id(1,2,3)
            $school_id  = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0;
            $school_arr = parent::underlingLook($school_id);

            //分校的id传递
            self::$accept_data['schoolId'] = $school_arr['data'];

            //获取专业列表
            $data = Pay_order_inside::getBranchSchoolOrderInfo(self::$accept_data);
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
     * @param  description   财务管理-分校业绩列表
     * @param  参数说明       body包含以下参数[
     *     school_id         分校id
     *     search_time       搜索时间
     * ]
     * @param author    dzj
     * @param ctime     2020-09-19
     * return string
     */
    public function getAchievementSchoolList(){
        //获取提交的参数
        try{
            //获取专业列表
            $data = Pay_order_inside::getAchievementSchoolList(self::$accept_data);
            if($data['code'] == 200){
                return response()->json(['code' => 200 , 'msg' => '获取列表成功' , 'data' => $data['data']]);
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
            $paystatus=[
                'paytype' => 1,
                'payname' => '微信支付',
                'payimg' => 'https://longdeapi.oss-cn-beijing.aliyuncs.com/wx2xtb.png',
            ];
            $status[] = $paystatus;
        }
        if($paylist['wx_pay_state'] == 1){
            $paystatus=[
                'paytype' => 2,
                'payname' => '支付宝支付',
                'payimg' => 'https://longdeapi.oss-cn-beijing.aliyuncs.com/zfb2xtb.png',
            ];
            $status[] = $paystatus;
        }
        if($paylist['hj_wx_pay_state'] == 1){
            $paystatus=[
                'paytype' => 3,
                'payname' => '微信支付',
                'payimg' => 'https://longdeapi.oss-cn-beijing.aliyuncs.com/wx2xtb.png',
            ];
            $status[] = $paystatus;
        }
        if($paylist['hj_zfb_pay_state'] == 1){
            $paystatus=[
                'paytype' => 4,
                'payname' => '支付宝支付',
                'payimg' => 'https://longdeapi.oss-cn-beijing.aliyuncs.com/zfb2xtb.png',
            ];
            $status[] = $paystatus;
        }
        return response()->json(['code' => 200 , 'msg' => '获取成功' , 'data' =>$status]);
    }
    //汇聚支付   入库   并生成二维码
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
        $list = Channel::where(['is_use'=>0,'is_del'=>0,'is_forbid'=>0])->first();
        $paylist = PaySet::where(['channel_id'=>$list['id']])->first();
        $insert['offline_id'] = $list['id'];
        $add = Pay_order_external::insertGetId($insert);
        if($add){
            $course = Course::where(['id'=>$data['course_id']])->first();
            //微信
            if($data['pay_type'] == 1){
//                $wxpay = new WxpayFactory();
//                $number = $insert['order_no'];
//                $price = $insert['price'];
//                $return = $wxpay->getPcPayOrder($number, $price,$course['title']);
                return response()->json(['code' => 202, 'msg' => '生成二维码失败']);
            }
            //支付宝
            if($data['pay_type'] == 2){
                $alipay = new AlipayFactory($paylist);
                $return = $alipay->convergecreatePcPay($insert['order_no'],$insert['price'],$course['title']);
                if($return['alipay_trade_precreate_response']['code'] == 10000){
                    require_once realpath(dirname(__FILE__).'/../../../Tools/phpqrcode/QRcode.php');
                    $code = new QRcode();
                    ob_start();//开启缓冲区
                    $returnData  = $code->pngString($return['alipay_trade_precreate_response']['qr_code'], false, 'L', 10, 1);//生成二维码
                    $imageString = base64_encode(ob_get_contents());
                    ob_end_clean();
                    $str = "data:image/png;base64," . $imageString;
                    return response()->json(['code' => 200, 'msg' => '预支付订单生成成功', 'data' => $str]);
                } else {
                    return response()->json(['code' => 202, 'msg' => '生成二维码失败']);
                }
            }
            //汇聚微信
            if($data['pay_type'] == 3){
                $notify = 'AB|'."http://".$_SERVER['HTTP_HOST']."/admin/hjnotify";
                $pay=[
                    'p0_Version'=>'1.0',
                    'p1_MerchantNo'=> $paylist['hj_commercial_tenant_number'],
                    'p2_OrderNo'=>$insert['order_no'],
                    'p3_Amount'=>$data['pay_price'],
                    'p4_Cur'=>1,
                    'p5_ProductName'=> $course['course_name'],
                    'p9_NotifyUrl'=>$notify,
                    'q1_FrpCode'=>'WEIXIN_NATIVE',
                    'q4_IsShowPic'=>1,
                    'qa_TradeMerchantNo'=>$paylist['hj_wx_commercial_tenant_deal_number']
                ];
                $str = $paylist['hj_md_key'];
                $token = $this->hjHmac($pay,$str);
                $pay['hmac'] = $token;
                $url="https://www.joinpay.com/trade/uniPayApi.action";
                $wxpay = $this->hjpost($url,$pay);
                $wxpayarr = json_decode($wxpay,true);
                file_put_contents('wxhjpay.txt', '时间:'.date('Y-m-d H:i:s').print_r($wxpayarr,true),FILE_APPEND);
                if($wxpayarr['ra_Code'] == 100){
                    return response()->json(['code' => 200, 'msg' => '预支付订单生成成功','data'=>$wxpayarr['rd_Pic']]);
                }else{
                    return response()->json(['code' => 202, 'msg' => '暂未开通']);
                }
            }
            //汇聚支付宝
            if($data['pay_type'] == 4){
                $notify = 'AB|'."http://".$_SERVER['HTTP_HOST']."/admin/hjnotify";
                $pay=[
                    'p0_Version'=>'1.0',
                    'p1_MerchantNo'=> $paylist['hj_commercial_tenant_number'],
                    'p2_OrderNo'=>$insert['order_no'],
                    'p3_Amount'=>$data['pay_price'],
                    'p4_Cur'=>1,
                    'p5_ProductName'=>$course['course_name'],
                    'p9_NotifyUrl'=>$notify,
                    'q1_FrpCode'=>'ALIPAY_NATIVE',
                    'q4_IsShowPic'=>1,
                    'qa_TradeMerchantNo'=>$paylist['hj_zfb_commercial_tenant_deal_number']
                ];
                $str = $paylist['hj_md_key'];
                $token = $this->hjHmac($pay,$str);
                $pay['hmac'] = $token;
                $url="https://www.joinpay.com/trade/uniPayApi.action";
                $zfbpay = $this->hjpost($url,$pay);
                $zfbpayarr = json_decode($zfbpay,true);
                file_put_contents('zfbhjpay.txt', '时间:'.date('Y-m-d H:i:s').print_r($zfbpayarr,true),FILE_APPEND);
                if($zfbpayarr['ra_Code'] == 100){
                    return response()->json(['code' => 200, 'msg' => '预支付订单生成成功','data'=>$zfbpayarr['rd_Pic']]);
                }else{
                    return response()->json(['code' => 202, 'msg' => '暂未开通']);
                }
            }

        }
    }
    //汇付支付
    public function hfpay(){
        $hf = new \App\Tools\Hf\HuifuCFCA();
        $aaa = $hf->apiRequest();
        return $aaa;
    }
    //汇聚签名
    public function hjHmac($arr,$str){
        $newarr = '';
        foreach ($arr as $k=>$v){
            $newarr =$newarr.$v;
        }
        return md5($newarr.$str);
    }
    public function hjpost($url,$data){
        //简单的curl
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }


    //银联
    public function ylpay(){
     $ylpay = New Ylpay();
     //商品名  订单号  钱
     $res = $ylpay->getPrePayOrder('龙德测试',date('YmdHis', time()) . rand(1111, 9999),0.01);
     print_r($res);
    }
    public function ylnotify_url(){
        $data = $_POST;
        $xml = $this->xmlstr_to_array($data);
        file_put_contents('yinlianzhifu.txt', '时间:' . date('Y-m-d H:i:s') . print_r($xml, true), FILE_APPEND);
    }
}
