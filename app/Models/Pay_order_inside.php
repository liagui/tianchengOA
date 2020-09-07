<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Project;
use App\Models\Course;
use App\Models\School;

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
        //判断是否是分校
//        $school_id = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0;
//        if($school_id != 0){
//            //只查询分校订单
//            $where['school_id'] = $school_id;
//        }else{
//            //判断总校传来的学校id
//            if(!empty($data['school_id'])){
//                $where['school_id'] = $data['school_id'];
//            }
//        }
        $where=[];
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
            ->whereBetween('create_time', [$state_time, $end_time])
            ->orderByDesc('id')
            ->offset($offset)->limit($pagesize)->get()->toArray();
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
         * @param   project_name  项目名称
         * @param   subject_id  学科id
         * @param   subject_name  学科名称
         * @param   course_id  课程id
         * @param   course_name  课程名称
         * @param   mobile  手机号
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
        if(!isset($data['project_id']) || empty($data['project_id']) || !isset($data['project_name']) || empty($data['project_name'])){
            return ['code' => 201 , 'msg' => '未选择项目'];
        }
        if(!isset($data['subject_id']) || empty($data['subject_id']) || !isset($data['subject_name']) || empty($data['subject_name'])){
            return ['code' => 201 , 'msg' => '未选择学科'];
        }
        if(!isset($data['course_id']) || empty($data['course_id']) || !isset($data['course_name']) || empty($data['course_name'])){
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
        $data['collecting_data'] = 1;
        $data['admin_id'] = 1;
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
    /*====================分校订单==========================*/
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
        $find = Pay_order_external::where(['id'=>$data['id'],'del_flag'=>0])->first();
        if(!$find){
            return ['code' => 201 , 'msg' => '查无此订单'];
        }
        return ['code' => 200 , 'msg' => '查询成功','data'=>$find];
    }
    /*
         * @param  分校未提交订单进行提交
         * @param  id   第三方订单id
         * @param  project_id    项目id
         * @param  project_name    项目名称
         * @param  subject_id   学科id
         * @param  subject_name  学科名称
         * @param  course_id  课程id
         * @param  course_name  课程名称
         * @param  education_id  院校id
         * @param  education_name  院校名称
         * @param  major_id  专业id
         * @param  major_name  专业名称
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
            'course_id' => $data['course_id'],//学科id
            'course_name' => $data['course_name'],//学科id
            'project_id' => $data['project_id'],//学科id
            'project_name' => $data['project_name'],//学科id
            'subject_id' => $data['subject_id'], //学科id
            'subject_name' => $data['subject_name'],//学科名称
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
         * @param 分校已提交订单
         * @param  subject_id    学科id
         * @param  pay_type    支付方式（1支付宝扫码2微信扫码3银联快捷支付4微信小程序5线下录入）
         * @param  confirm_order_type    订单类型 1课程订单 2报名订单3课程+报名订单
         * @param  return_visit    是否回访0未回访 1 已回访
         * @param  classes    是否开课 0不开课 1开课
         * @param  order_no    订单号/手机号/姓名
         * @param  pageSize    每页条数
         * @param  page    页码
         * @param  author  苏振文
         * @param  ctime   2020/9/4 14:34
         * return  array
         */
    public static function submittedOrder($data){
        //获取学校id
        //$school_id = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0;
//        $where['school_id'] = $school_id;
        $where=[];
        if(isset($data['subject_id'])||!empty($data['subject_id'])){
            $where['subject_id'] = $data['subject_id'];
        }
        if(isset($data['pay_type'])||!empty($data['pay_type'])){
            $where['pay_type'] = $data['pay_type'];
        }
        if(isset($data['confirm_order_type'])||!empty($data['confirm_order_type'])){
            $where['confirm_order_type'] = $data['confirm_order_type'];
        }
        if(isset($data['return_visit'])||!empty($data['return_visit'])){
            $where['return_visit'] = $data['return_visit'];
        }
        if(isset($data['classes'])||!empty($data['classes'])){
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
        ->where(['del_flag'=>0,'confirm_status'=>1])
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
        ->where(['del_flag'=>0,'confirm_status'=>1])
        ->orderByDesc('id')
        ->offset($offset)->limit($pagesize)->get()->toArray();
        $page=[
            'pageSize'=>$pagesize,
            'page' =>$page,
            'total'=>$count
        ];
        return ['code' => 200 , 'msg' => '查询成功','data'=>$order,'where'=>$data,'page'=>$page];
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

    }
    
    
    /*
     * @param  description   开课管理列表接口
     * @param  参数说明       body包含以下参数[
     *     category_id       项目-学科大小类(例如:[1,2])
     *     school_id         分校id
     *     order_type        订单类型(1.课程订单2.报名订单3.课程+报名订单)
     *     classes           开课状态(0不开课 1开课)
     *     keywords          订单号/手机号/姓名
     * ]
     * @param author    dzj
     * @param ctime     2020-09-07
     * return string
     */
    public static function getOpenCourseList($body=[]) {
        //每页显示的条数
        $pagesize = isset($body['pagesize']) && $body['pagesize'] > 0 ? $body['pagesize'] : 20;
        $page     = isset($body['page']) && $body['page'] > 0 ? $body['page'] : 1;
        $offset   = ($page - 1) * $pagesize;

        //获取开课管理的总数量
        $open_class_count = self::where(function($query) use ($body){
            //判断项目-学科大小类是否为空
            if(isset($body['category_id']) && !empty($body['category_id'])){
                $category_id= json_decode($body['category_id'] , true);
                $project_id = $category_id[0];
                $subject_id = $category_id[1];
                
                //判断项目id是否传递
                if($project_id && $project_id > 0){
                    $query->where('project_id' , '=' , $project_id);
                }
                
                //判断学科id是否传递
                if($subject_id && $subject_id > 0){
                    $query->where('subject_id' , '=' , $subject_id);
                }
            }
            
            //判断分校id是否为空和合法
            if(isset($body['school_id']) && !empty($body['school_id']) && $body['school_id'] > 0){
                $query->where('school_id' , '=' , $body['school_id']);
            }
            
            //判断订单类型是否为空和合法
            if(isset($body['order_type']) && !empty($body['order_type']) && in_array($body['order_type'] , [1,2,3])){
                $query->where('confirm_order_type' , '=' , $body['order_type']);
            }
            
            //判断开课状态是否为空和合法
            if(isset($body['classes']) && in_array($body['classes'] , [0,1])){
                $query->where('classes' , '=' , $body['classes']);
            }
        })->where(function($query) use ($body){
            //判断订单号/手机号/姓名是否为空
            if(isset($body['keywords']) && !empty($body['keywords'])){
                $query->where('name','like','%'.$body['keywords'].'%')->orWhere('mobile','like','%'.$body['keywords'].'%')->orWhere('order_no','like','%'.$body['keywords'].'%');
            }
        })->where('confirm_status' , 1)->where('begin_class' , 1)->count();
        
        if($open_class_count > 0){
            //新数组赋值
            $order_array = [];
            
            //获取开课列表
            $open_class_list = self::select('id as order_id' , 'order_no' , 'create_time' , 'mobile' , 'name' , 'course_id' , 'project_id' , 'subject_id' , 'school_id' , 'classes')->where(function($query) use ($body){
                //判断项目-学科大小类是否为空
                if(isset($body['category_id']) && !empty($body['category_id'])){
                    $category_id= json_decode($body['category_id'] , true);
                    $project_id = $category_id[0];
                    $subject_id = $category_id[1];

                    //判断项目id是否传递
                    if($project_id && $project_id > 0){
                        $query->where('project_id' , '=' , $project_id);
                    }

                    //判断学科id是否传递
                    if($subject_id && $subject_id > 0){
                        $query->where('subject_id' , '=' , $subject_id);
                    }
                }

                //判断分校id是否为空和合法
                if(isset($body['school_id']) && !empty($body['school_id']) && $body['school_id'] > 0){
                    $query->where('school_id' , '=' , $body['school_id']);
                }

                //判断订单类型是否为空和合法
                if(isset($body['order_type']) && !empty($body['order_type']) && in_array($body['order_type'] , [1,2,3])){
                    $query->where('confirm_order_type' , '=' , $body['order_type']);
                }

                //判断开课状态是否为空和合法
                if(isset($body['classes']) && in_array($body['classes'] , [0,1])){
                    $query->where('classes' , '=' , $body['classes']);
                }
            })->where(function($query) use ($body){
                //判断订单号/手机号/姓名是否为空
                if(isset($body['keywords']) && !empty($body['keywords'])){
                    $query->where('name','like','%'.$body['keywords'].'%')->orWhere('mobile','like','%'.$body['keywords'].'%')->orWhere('order_no','like','%'.$body['keywords'].'%');
                }
            })->where('confirm_status' , 1)->where('begin_class' , 1)->orderByDesc('create_time')->offset($offset)->limit($pagesize)->get()->toArray();
            
            //循环获取相关信息
            foreach($open_class_list as $k=>$v){
                //项目名称
                $project_name = Project::where('id' , $v['project_id'])->value('name');
                
                //学科名称
                $subject_name = Project::where('parent_id' , $v['project_id'])->where('id' , $v['subject_id'])->value('name');
                
                //课程名称
                $course_name  = Course::where('id' , $v['course_id'])->value('course_name');
                
                //分校的名称
                $school_name  = School::where('id' , $v['school_id'])->value('school_name');
                
                //开课数组管理赋值
                $order_array[] = [
                    'order_id'      =>  $v['order_id'] ,
                    'order_no'      =>  $v['order_no'] ,
                    'create_time'   =>  $v['create_time'] ,
                    'mobile'        =>  $v['mobile'] ,
                    'name'          =>  $v['name'] ,
                    'classes_status'=>  (int)$v['classes'] ,
                    'classes_name'  =>  $v['classes'] > 0 ? '已开课' : '未开课' ,
                    'project_name'  =>  $project_name && !empty($project_name) ? $project_name : '' ,
                    'subject_name'  =>  $subject_name && !empty($subject_name) ? $subject_name : '' ,
                    'course_name'   =>  $course_name  && !empty($course_name)  ? $course_name  : '' ,
                    'school_name'   =>  $school_name  && !empty($school_name)  ? $school_name  : '' 
                ];
            }
            return ['code' => 200 , 'msg' => '获取开课列表成功' , 'data' => ['open_class_list' => $order_array , 'total' => $open_class_count , 'pagesize' => $pagesize , 'page' => $page]];
        }
        return ['code' => 200 , 'msg' => '获取开课列表成功' , 'data' => ['open_class_list' => [] , 'total' => 0 , 'pagesize' => $pagesize , 'page' => $page]];
    }
    
    /*
     * @param  description   开课管理-确认开课方法
     * @param  参数说明       body包含以下参数[
     *     order_id        订单id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-07
     * return string
     */
    public static function doMakeSureOpenCourse($body=[]) {
        //判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }
        
        //判断订单id是否合法
        if(!isset($body['order_id']) || empty($body['order_id']) || $body['order_id'] <= 0){
            return ['code' => 202 , 'msg' => 'id不合法'];
        }
        
        //判断此订单是否存在
        $order_info = self::where('id' , $body['order_id'])->first();
        if(!$order_info || empty($order_info)){
            return ['code' => 203 , 'msg' => '此订单不存在'];
        }
        
        //获取当前订单的开课状态
        $classes_status = $order_info['classes'] > 0 ? 0 : 1;
        
        //开启事务
        DB::beginTransaction();

        //根据订单id更新信息
        if(false !== self::where('id',$body['order_id'])->update(['classes' => $classes_status])){
            //事务提交
            DB::commit();
            return ['code' => 200 , 'msg' => '更新成功'];
        } else {
            //事务回滚
            DB::rollBack();
            return ['code' => 203 , 'msg' => '更新失败'];
        }
    }
}
