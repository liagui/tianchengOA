<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pay_order_inside extends Model
{
    //指定别的表名
    public $table = 'pay_order_inside';
    //时间戳设置
    public $timestamps = false;
    /*
         * @param  订单总览
         * @param  pay_type    支付方式 1支付宝扫码2微信扫码3银联快捷支付4微信小程序5线下录入
         * @param  pay_status  支付状态 0未支付1已支付2支付失败
         * @param  confirm_order_type   1课程订单 2报名订单3课程+报名订单
         * @param  return_visit   0未回访 1 已回访
         * @param  classes   0不开课 1开课
         * @param  confirm_status   订单确认状态码 0未确认 1确认  2驳回
         * @param  project_id   科目id
         * @param  school_id   学校id
         * @param  state_time   创建时间
         * @param  end_time    结束时间
         * @param  order_no    订单号/手机号/姓名
         * @param  pageSize    每页显示条数
         * @param  page    第几页
         * @param  苏振文
         * @param  2020/9/2 15:52
         * return  array
         */
    public static function orderList($data){
        $where=[];
        //判断是否是分校
        $school_id = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0;
        if($school_id != 0){
            //只查询分校订单
            $where['school_id'] = $school_id;
        }else{
            //判断总校传来的学校id
            if(!empty($data['school_id'])){
                $where['school_id'] = $data['school_id'];
            }
        }
        //判断时间
        $begindata="2020-03-04";
        $enddate = date('Y-m-d');
        $statetime = !empty($data['state_time'])?$data['state_time']:$begindata;
        $endtime = !empty($data['end_time'])?$data['end_time']:$enddate;
        $state_time = $statetime." 00:00:00";
        $end_time = $endtime." 23:59:59";
        //支付方式
        if(!empty($data['pay_type'])){
            $where['pay_type'] = $data['pay_type'];
        }
        //支付状态
        if(!empty($data['pay_status'])){
            $where['pay_status'] = $data['pay_status'];
        }
        //订单类型
        if(!empty($data['confirm_order_type'])){
            $where['confirm_order_type'] = $data['confirm_order_type'];
        }
        //订单是否回访
        if(!empty($data['return_visit'])){
            $where['return_visit'] = $data['return_visit'];
        }
        //订单是否开课
        if(!empty($data['classes'])){
            $where['classes'] = $data['classes'];
        }
        //订单状态
        if(!empty($data['confirm_status'])){
            $where['confirm_status'] = $data['confirm_status'];
        }
        //科目id
        if(!empty($data['project_id'])){
            $where['project_id'] = $data['project_id'];
        }

        //每页显示的条数
        $pagesize = (int)isset($data['pageSize']) && $data['pageSize'] > 0 ? $data['pageSize'] : 20;
        $page     = isset($data['page']) && $data['page'] > 0 ? $data['page'] : 1;
        $offset   = ($page - 1) * $pagesize;

        //計算總數
        $count = self::where(function($query) use ($data) {
                if(isset($data['order_no']) && !empty($data['order_no'])){
                    $query->where('order_no',$data['order_on'])
                        ->orwhere('name',$data['order_on'])
                        ->orwhere('mobile',$data['order_on']);
                }
            })
            ->where($where)
            ->where('del_flag',0)
            ->whereBetween('create_time', [$state_time, $end_time])
            ->count();

        //数据
        $order = self::where(function($query) use ($data) {
                if(isset($data['order_no']) && !empty($data['order_no'])){
                    $query->where('order_no',$data['order_on'])
                        ->orwhere('name',$data['order_on'])
                        ->orwhere('mobile',$data['order_on']);
                }
            })
            ->where($where)
            ->where('del_flag',0)
            ->whereBetween('create_time', [$state_time, $end_time])
            ->orderByDesc('id')
            ->offset($offset)->limit($pagesize)->get()->toArray();
        //循环查询分类
        if(!empty($order)){
            foreach ($order as $k=>&$v){
                //course  课程
                $course = Course::select('course_name')->where(['id'=>$v['course_id']])->first();
                $v['course_name'] = $course['course_name'];
                //Project  项目
                $project = Project::select('name')->where(['id'=>$v['project_id']])->first();
                $v['project_name'] = $project['name'];
                //Subject  学科
                $subject = Project::select('name')->where(['id'=>$v['subject_id']])->first();
                $v['subject_name'] = $subject['name'];
                if(!empty($v['education_id']) && $v['education_id'] != 0){
                    //查院校
                    $education = Education::select('education_name')->where(['id'=>$v['education_id']])->first();
                    $v['education_name'] = $education['education_name'];
                    //查专业
                    $major = Major::where(['id'=>$v['major_id']])->first();
                    $v['major_name'] = $major['major_name'];
                }
            }
        }
        $page=[
            'pageSize'=>$pagesize,
            'page' =>$page,
            'total'=>$count
        ];
        return ['code' => 200 , 'msg' => '查询成功','data'=>$order,'where'=>$data,'page'=>$page];
    }
    /*
         * @param  手动报单
         * @param   project_id  项目id
         * @param   subject_id  学科id
         * @param   course_id  课程id
         * @param   education_id  课程id
         * @param   major_id  院校id
         * @param   mobile  专业id
         * @param   pay_price  支付金额
         * @param   pay_type  支付方式（1支付宝扫码2微信扫码3银联快捷支付4微信小程序5线下录入）
         * @param   remark  备注
         * @param   name  姓名
         * @param   school_id  所属分校
         * @param   pay_voucher  支付凭证
         * @param  author  苏振文
         * @param  ctime   2020/9/3 10:46
         * return  array
         */
    public static function handOrder($data){
        $admin = isset(AdminLog::getAdminInfo()->admin_user) ? AdminLog::getAdminInfo()->admin_user : [];
        if(!isset($data['project_id']) || empty($data['project_id'])){
            return ['code' => 201 , 'msg' => '未选择项目'];
        }
        if(!isset($data['subject_id']) || empty($data['subject_id'])){
            return ['code' => 201 , 'msg' => '未选择学科'];
        }
        if(!isset($data['course_id']) || empty($data['course_id'])){
            return ['code' => 201 , 'msg' => '未选择课程'];
        }
        if(!isset($data['mobile']) || empty($data['mobile'])){
            return ['code' => 201 , 'msg' => '未输入手机号'];
        }
        if(!isset($data['pay_price']) || empty($data['pay_price'])){
            return ['code' => 201 , 'msg' => '未填写支付金额'];
        }
        if(!in_array($data['pay_type'],[1,2,3,4,5])){
            return ['code' => 201 , 'msg' => '未选择支付方式'];
        }
        if(!isset($data['name']) || empty($data['name'])){
            return ['code' => 201 , 'msg' => '未填写姓名'];
        }
        if(!isset($data['school_id']) || empty($data['school_id'])){
            return ['code' => 201 , 'msg' => '未选择分校'];
        }
        if(!isset($data['pay_voucher']) || empty($data['pay_voucher'])){
            return ['code' => 201 , 'msg' => '未上传支付凭证'];
        }
        $data['order_no'] = date('YmdHis', time()) . rand(1111, 9999); //订单号  随机生成
        $data['create_time'] =date('Y-m-d H:i:s');
        $data['pay_time'] =date('Y-m-d H:i:s');
        $data['pay_status'] = 1;
        $data['confirm_status'] = 0;
        $data['pay_voucher_user_id'] = $admin['id']; //上传凭证人
        $data['pay_voucher_time'] = date('Y-m-d H:i:s');//上传凭证时间
        $data['admin_id'] = $admin['id'];
        $add = self::insert($data);
        if($add){
            return ['code' => 200 , 'msg' => '报单成功'];
        }else{
            return ['code' => 201 , 'msg' => '报单失败'];
        }
    }
    /*
         * @param  查看订单凭证
         * @param  order_id
         * @param  author  苏振文
         * @param  ctime   2020/9/3 15:29
         * return  array
         */
    public static function orderVoucher($data){
        if(!isset($data['id'])|| empty($data['id'])){
            return ['code' => 201 , 'msg' => '订单有误'];
        }
        $order = self::where(['id'=>$data['id']])->first();
        $user = Admin::where(['id'=>$order['pay_voucher_user_id'],'is_del'=>0,'is_forbid'=>0])->first();
        $res=[
            'name' => $user['username'],
            'pay_voucher' => $order['pay_voucher'],
            'pay_voucher_time' => $order['pay_voucher_time']
        ];
        return ['code' => 200 , 'msg' => '查询成功','data'=>$res];
    }
    /*
         * @param  查询备注或驳回原因
         * @param  id 订单id
         * @param  author  苏振文
         * @param  ctime   2020/9/3 16:07
         * return  array
         */
    public static function orderDetail($data){
        if(!isset($data['id'])|| empty($data['id'])){
            return ['code' => 201 , 'msg' => '订单有误'];
        }
        $order = self::where(['id'=>$data['id']])->first();
        $remark=[];
        if(!empty($order['remark'])){
            $admin = Admin::where(['id'=>$order['admin_id']])->first();
            $remark = [
                'name' => $admin['username'],
                'create_time' =>$order['create_time'],
                'remark' => $order['remark']
            ];
        }
        if($order['confirm_status'] == 2){
            $reject=[];
            if(!empty($order['reject_des'])){
                $admin = Admin::where(['id'=>$order['reject_admin_id']])->first();
                $reject=[
                    'name' => $admin['username'],
                    'create_time' =>$order['reject_time'],
                    'reject' => $order['reject_des']
                ];
            }
            return ['code' => 200 , 'msg' => '查询成功','remark' => $remark,'reject' => $reject];
        }else{
            return ['code' => 200 , 'msg' => '查询成功','remark' => $remark];
        }

    }
    /*
         * @param  总校待确认订单列表   分校已提交订单
         * @param  subject_id  学科id
         * @param  school_id  分校id
         * @param  pay_type  支付方式（1支付宝扫码2微信扫码3银联快捷支付4微信小程序5线下录入）
         * @param  confirm_order_type  确认的订单类型 1课程订单 2报名订单3课程+报名订单
         * @param  return_visit  回访状态 0未回访 1 已回访
         * @param  classes  是否开课 0不开课 1开课
         * @param  order_on  订单号/手机号/姓名
         * @param  author  苏振文
         * @param  ctime   2020/9/7 10:14
         * return  array
         */
    public static function awaitOrder($data){
        $where['del_flag'] = 0;  //未删除
        $where['confirm_status'] = 0;  //未确认
        //判断学校
        $admin = isset(AdminLog::getAdminInfo()->admin_user) ? AdminLog::getAdminInfo()->admin_user : [];
        if($admin['school_id'] != 0){
            $where['school_id'] = $admin['school_id'];
        }else{
            if(!empty($data['school_id']) && $data['school_id'] == 0){
                $where['school_id'] = $data['school_id'];
            }
        }
        if(isset($data['subject_id']) || !empty($data['subject_id'])){
            $where['subject_id'] = $data['subject_id'];
        }
        if(isset($data['school_id']) || !empty($data['school_id'])){
            $where['school_id'] = $data['school_id'];
        }
        if(isset($data['pay_type']) || !empty($data['pay_type'])){
            $where['pay_type'] = $data['pay_type'];
        }
        if(isset($data['confirm_order_type']) || !empty($data['confirm_order_type'])){
            $where['confirm_order_type'] = $data['confirm_order_type'];
        }
        if(isset($data['return_visit']) || !empty($data['return_visit'])){
            $where['return_visit'] = $data['return_visit'];
        }
        if(isset($data['classes']) || !empty($data['classes'])){
            $where['classes'] = $data['classes'];
        }

        //每页显示的条数
        $pagesize = (int)isset($data['pageSize']) && $data['pageSize'] > 0 ? $data['pageSize'] : 20;
        $page     = isset($data['page']) && $data['page'] > 0 ? $data['page'] : 1;
        $offset   = ($page - 1) * $pagesize;

        //計算總數
        $count = self::where(function($query) use ($data) {
            if(isset($data['order_no']) && !empty($data['order_no'])){
                $query->where('order_no',$data['order_on'])
                    ->orwhere('name',$data['order_on'])
                    ->orwhere('mobile',$data['order_on']);
            }
        })
        ->where($where)
        ->count();

        $order = self::where(function($query) use ($data) {
            if(isset($data['order_no']) && !empty($data['order_no'])){
                $query->where('order_no',$data['order_on'])
                    ->orwhere('name',$data['order_on'])
                    ->orwhere('mobile',$data['order_on']);
            }
        })
        ->where($where)
        ->orderByDesc('id')
        ->offset($offset)->limit($pagesize)->get()->toArray();
         //循环查询分类
        if(!empty($order)){
            foreach ($order as $k=>&$v){
                //course  课程
                $course = Course::select('course_name')->where(['id'=>$v['course_id']])->first();
                $v['course_name'] = $course['course_name'];
                //Project  项目
                $project = Project::select('name')->where(['id'=>$v['project_id']])->first();
                $v['project_name'] = $project['name'];
                //Subject  学科
                $subject = Project::select('name')->where(['id'=>$v['subject_id']])->first();
                $v['subject_name'] = $subject['name'];
                if(!empty($v['education_id']) && $v['education_id'] != 0){
                    //查院校
                    $education = Education::select('education_name')->where(['id'=>$v['education_id']])->first();
                    $v['education_name'] = $education['education_name'];
                    //查专业
                    $major = Major::where(['id'=>$v['major_id']])->first();
                    $v['major_name'] = $major['major_name'];
                }
            }
        }
        $page=[
            'pageSize'=>$pagesize,
            'page' =>$page,
            'total'=>$count
        ];
        return ['code' => 200 , 'msg' => '查询成功','data'=>$order,'where'=>$data,'page'=>$page];
    }
    /*
         * @param  未确认订单进行确认
         * @param  id  订单id
         * @param  project_id    项目id
         * @param  subject_id   学科id
         * @param  course_id  课程id
         * @param  education_id  院校id
         * @param  major_id  专业id
         * @param  name   姓名
         * @param  mobile   手机号
         * @param  confirm_order_type   订单类型
         * @param  first_pay   缴费类型
         * @param  confirm_status    1确认2驳回
         * @param  reject_des  驳回原因
         * @param  remark   订单备注
         * @param  school_id  分校id
         * @param  course_Price   课程金额
         * @param  sign_Price   报名金额
         * @param  author  苏振文
         * @param  ctime   2020/9/7 15:09
         * return  array
         */
    public static function notarizeOrder($data){
        //获取操作人信息
        $admin = isset(AdminLog::getAdminInfo()->admin_user) ? AdminLog::getAdminInfo()->admin_user : [];
        if($data['confirm_status'] == 1){
            $data['comfirm_time'] = date('Y-m-d H:i:s');
            $data['have_user_id'] = $admin['id'];
            $data['have_user_name'] = $admin['username'];
        }
        if($data['confirm_status'] == 2){
            $data['reject_time'] = date('Y-m-d H:i:s');
            $data['reject_admin_id'] = $admin['id'];
        }
        $up = self::where(['id'=>$data['id']])->update($data);
        if($up){
            return ['code' => 200 , 'msg' => '操作成功'];
        }else{
            return ['code' => 201 , 'msg' => '操作失败'];
        }
    }
    /*
         * @param  分校未提交订单
         * @param  order_on   订单号
         * @param  author  苏振文
         * @param  ctime   2020/9/3 20:26
         * return  array
         */
    public static function unsubmittedOrder($data){
        //默认不传订单号   展示空页面
        $res=[];
        if(!isset($data['order_on']) || empty($data['order_on'])){
            return ['code' => 200 , 'msg' => '获取成功','data'=>$res];
        }
        $res = Pay_order_external::where(['order_on'=>$data['order_on'],'status'=>0])->first();
        if(!empty($res)){
            //course  课程
            $course = Course::select('course_name')->where(['id'=>$res['course_id']])->first();
            $res['course_name'] = $course['course_name'];
            //Project  项目
            $project = Project::select('name')->where(['id'=>$res['project_id']])->first();
            $res['project_name'] = $project['name'];
            //Subject  学科
            $subject = Project::select('name')->where(['id'=>$res['subject_id']])->first();
            $res['subject_name'] = $subject['name'];
            if(!empty($res['education_id']) && $res['education_id'] != 0){
                //查院校
                $education = Education::select('education_name')->where(['id'=>$res['education_id']])->first();
                $res['education_name'] = $education['education_name'];
                //查专业
                $major = Major::where(['id'=>$res['major_id']])->first();
                $res['major_name'] = $major['major_name'];
            }
            return ['code' => 200 , 'msg' => '获取成功','data'=>$res];
        }else{
            return ['code' => 201 , 'msg' => '无此订单'];
        }
    }
    /*
         * @param  未提交订单详情
         * @param  id   订单id
         * @param  author  苏振文
         * @param  ctime   2020/9/4 15:07
         * return  array
         */
    public static function unsubmittedOrderDetail($data){
        if(!isset($data['id']) || empty($data['id'])){
            return ['code' => 201 , 'msg' => '参数有误'];
        }
        $res = Pay_order_external::where(['id'=>$data['id'],'del_flag'=>0])->first();
        if(!$res){
            //查询分类
            //course  课程
            $course = Course::select('course_name')->where(['id'=>$res['course_id']])->first();
            $res['course_name'] = $course['course_name'];
            //Project  项目
            $project = Project::select('name')->where(['id'=>$res['project_id']])->first();
            $res['project_name'] = $project['name'];
            //Subject  学科
            $subject = Project::select('name')->where(['id'=>$res['subject_id']])->first();
            $res['subject_name'] = $subject['name'];
            if(!empty($res['education_id']) && $res['education_id'] != 0){
                //查院校
                $education = Education::select('education_name')->where(['id'=>$res['education_id']])->first();
                $res['education_name'] = $education['education_name'];
                //查专业
                $major = Major::where(['id'=>$res['major_id']])->first();
                $res['major_name'] = $major['major_name'];
            }
            return ['code' => 201 , 'msg' => '查无此订单'];
        }
        return ['code' => 200 , 'msg' => '查询成功','data'=>$res];
    }
    /*
         * @param  分校未提交订单进行提交
         * @param  id   第三方订单id
         * @param  project_id    项目id
         * @param  subject_id   学科id
         * @param  course_id  课程id
         * @param  education_id  院校id
         * @param  major_id  专业id
         * @param  name   姓名
         * @param  mobile   手机号
         * @param  confirm_order_type   订单类型
         * @param  first_pay   缴费类型
         * @param  return_visit   回访状态 0未回访 1 已回访
         * @param  classes   是否开课 0不开课 1开课
         * @param  remark   订单备注
         * @param  pay_voucher   上传凭证
         * @param  course_Price   课程金额
         * @param  sign_Price   报名金额
         * @param  author  苏振文
         * @param  ctime   2020/9/4 15:06
         * return  array
         */
    public static function DoSubmitted($data){
        //将此信息加入到pay_order_inside，修改pay_order_external中订单的status
        if(!isset($data['id']) || empty($data['id'])){
            return ['code' => 201 , 'msg' => '参数有误'];
        }
        if(!isset($data['project_id']) || empty($data['project_id'])){
            return ['code' => 201 , 'msg' => '未选择项目'];
        }
        if(!isset($data['subject_id']) || empty($data['subject_id'])){
            return ['code' => 201 , 'msg' => '未选择学科'];
        }
        if(!isset($data['course_id']) || empty($data['course_id'])){
            return ['code' => 201 , 'msg' => '未选择课程'];
        }
        if(!isset($data['name']) || empty($data['name'])){
            return ['code' => 201 , 'msg' => '未填写姓名'];
        }
        if(!isset($data['mobile']) || empty($data['mobile'])){
            return ['code' => 201 , 'msg' => '未填写手机号'];
        }
        if(!isset($data['confirm_order_type']) || empty($data['confirm_order_type'])){
            return ['code' => 201 , 'msg' => '未选择订单类型'];
        }
        if(!isset($data['first_pay']) || empty($data['first_pay'])){
            return ['code' => 201 , 'msg' => '未选择缴费类型'];
        }
        //获取操作员信息
        $admin = isset(AdminLog::getAdminInfo()->admin_user) ? AdminLog::getAdminInfo()->admin_user : [];
        //第三方订单数据
        $external = Pay_order_external::where(['id'=>$data['id']])->first();
        //入库
        $insert=[
            'name' => $data['name'],//姓名
            'mobile' =>$data['mobile'],//手机号
            'order_no' => $external['order_no'],//订单编号
            'create_time' => date('Y-m-d H:i:s'),//订单创建时间
            'pay_time' => $external['pay_time'],//支付成功时间
            'pay_price' => $external['pay_price'],//支付金额
            'course_id' => $data['course_id'],//课程id
            'project_id' => $data['project_id'],//项目id
            'subject_id' => $data['subject_id'], //学科id
            'education_id' => isset($data['subject_id'])?$data['education_id']:0, //院校id
            'major_id' => isset($data['major_id'])?$data['major_id']:0, //专业id
            'pay_status' => $external['pay_status'],//支付状态
            'pay_type' => $external['pay_type'], //支付方式（1支付宝扫码2微信扫码3银联快捷支付4微信小程序5线下录入）
            'confirm_status' => 0, //订单确认状态码
            'school_id' => $admin['school_id'],  //所属分校
            'consignee_statsu' => 0,//0带收集 1收集中 2已收集 3重新收集
            'confirm_order_type' => $data['confirm_order_type'],//确认的订单类型 1课程订单 2报名订单3课程+报名订单
            'first_pay' => $data['first_pay'],//支付类型 1全款 2定金 3部分尾款 4最后一笔尾款
//            'classes' => $data['classes'],//开课状态
//            'return_visit' => $data['return_visit'],//回访状态
            'remark' => $data['remark'], //备注
            'pay_voucher_user_id' => $admin['id'], //上传凭证人
            'pay_voucher_time' => date('Y-m-d H:i:s'), //上传凭证时间
            'pay_voucher' => $data['remark'], //支付凭证
            'course_Price' => $data['course_Price'],
            'sum_Price' => $external['pay_price'],
            'sign_Price' => $data['sign_Price'],
            'admin_id' => $admin['id']
        ];
        $add = Pay_order_inside::insert($insert);
        if($add){
            //修改第三方订单状态 修改为已提交
            Pay_order_external::where(['id'=>$data['id']])->update(['status'=>1]);
            return ['code' => 200 , 'msg' => '提交成功'];
        }else{
            return ['code' => 201 , 'msg' => '提交失败'];
        }
    }
    /*
         * @param  分校已提交订单进行取消
         * @param  id 订单id
         * @param  author  苏振文
         * @param  ctime   2020/9/4 17:50
         * return  array
         */
    public static function submittedOrderCancel($data){
        //流转订单假删   第三方订单修改状态
        if(!isset($data['id']) || empty($data['id'])){
            return ['code' => 201 , 'msg' => '参数为空'];
        }
        $order = self::where(['id'=>$data['id']])->first();
        $updel = self::where(['id'=>$data['id']])->update(['del_flag'=>1]);
        if($updel){
            //修改第三方订单号
            Pay_order_external::where(['inside_no'=>$order['order_on']])->update(['status'=>0]);
            return ['code' => 200 , 'msg' => '取消成功'];
        }else{
            return ['code' => 201 , 'msg' => '取消失败'];
        }
    }
    /*
         * @param  驳回订单
         * @param  subject_id  学科id
         * @param  school_id  分校id
         * @param  pay_type  支付方式（1支付宝扫码2微信扫码3银联快捷支付4微信小程序5线下录入）
         * @param  confirm_order_type  确认的订单类型 1课程订单 2报名订单3课程+报名订单
         * @param  return_visit  回访状态 0未回访 1 已回访
         * @param  classes  是否开课 0不开课 1开课
         * @param  order_on  订单号/手机号/姓名
         * @param  author  苏振文
         * @param  ctime   2020/9/7 16:03
         * return  array
         */
    public static function rejectOrder($data){
        $admin = isset(AdminLog::getAdminInfo()->admin_user) ? AdminLog::getAdminInfo()->admin_user: [];
        if($admin['school_id'] != 0){
            $where['school_id'] = $admin['schoiol_id'];
        }else{
            if(isset($data['school_id']) || empty($data['school_id'])){
                $where['school_id'] = $data['schoiol_id'];
            }
        }
        $where['del_flag'] = 0;  //未删除
        $where['confirm_status'] = 2;  //已驳回
        if(isset($data['subject_id']) || !empty($data['subject_id'])){
            $where['subject_id'] = $data['subject_id'];
        }
        if(isset($data['pay_type']) || !empty($data['pay_type'])){
            $where['pay_type'] = $data['pay_type'];
        }
        if(isset($data['confirm_order_type']) || !empty($data['confirm_order_type'])){
            $where['confirm_order_type'] = $data['confirm_order_type'];
        }
        if(isset($data['return_visit']) || !empty($data['return_visit'])){
            $where['return_visit'] = $data['return_visit'];
        }
        if(isset($data['classes']) || !empty($data['classes'])){
            $where['classes'] = $data['classes'];
        }

        //每页显示的条数
        $pagesize = (int)isset($data['pageSize']) && $data['pageSize'] > 0 ? $data['pageSize'] : 20;
        $page     = isset($data['page']) && $data['page'] > 0 ? $data['page'] : 1;
        $offset   = ($page - 1) * $pagesize;

        //計算總數
        $count = self::where(function($query) use ($data) {
            if(isset($data['order_no']) && !empty($data['order_no'])){
                $query->where('order_no',$data['order_on'])
                    ->orwhere('name',$data['order_on'])
                    ->orwhere('mobile',$data['order_on']);
            }
        })
        ->where($where)
        ->count();

        $order = self::where(function($query) use ($data) {
            if(isset($data['order_no']) && !empty($data['order_no'])){
                $query->where('order_no',$data['order_on'])
                    ->orwhere('name',$data['order_on'])
                    ->orwhere('mobile',$data['order_on']);
            }
        })
        ->where($where)
        ->orderByDesc('id')
        ->offset($offset)->limit($pagesize)->get()->toArray();
        //循环查询分类
        if(!empty($order)){
            foreach ($order as $k=>&$v){
                //course  课程
                $course = Course::select('course_name')->where(['id'=>$v['course_id']])->first();
                $v['course_name'] = $course['course_name'];
                //Project  项目
                $project = Project::select('name')->where(['id'=>$v['project_id']])->first();
                $v['project_name'] = $project['name'];
                //Subject  学科
                $subject = Project::select('name')->where(['id'=>$v['subject_id']])->first();
                $v['subject_name'] = $subject['name'];
                if(!empty($v['education_id']) && $v['education_id'] != 0){
                    //查院校
                    $education = Education::select('education_name')->where(['id'=>$v['education_id']])->first();
                    $v['education_name'] = $education['education_name'];
                    //查专业
                    $major = Major::where(['id'=>$v['major_id']])->first();
                    $v['major_name'] = $major['major_name'];
                }
            }
        }
        $page=[
            'pageSize'=>$pagesize,
            'page' =>$page,
            'total'=>$count
        ];
        return ['code' => 200 , 'msg' => '查询成功','data'=>$order,'where'=>$data,'page'=>$page];
    }
    /*
         * @param  被驳回订单进行操作
         * @param  id 订单id
         * @param  author  苏振文
         * @param  ctime   2020/9/7 16:16
         * return  array
         */
    public static function anewOrder($data){
        //总校操作   status变成0 到待确认
        //分校操作   status变成0 到已提交
        if(empty($data['id'])){
            return ['code' => 201 , 'msg' => '参数错误'];
        }
        $up = self::where(['id'=>$data['id']])->where(['confirm_status'=>0]);
        if($up){
            return ['code' => 200 , 'msg' => '操作成功'];
        }else{
            return ['code' => 201 , 'msg' => '操作失败'];
        }
    }
}
