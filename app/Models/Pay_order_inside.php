<?php
namespace App\Models;

use App\Models\AdminLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\StudentCourse;
use Illuminate\Support\Facades\Redis;

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
         * @param  project_id  数组
         * @param  school_id   学校id
         * @param  state_time   创建时间
         * @param  end_time    结束时间
         * @param  order_no    订单号/手机号/姓名
         * @param  pagesize    每页显示条数
         * @param  page    第几页
         * @param  苏振文
         * @param  2020/9/2 15:52
         * return  array
         */
    public static function orderList($data,$schoolarr){
        $where['del_flag']=0;
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
        //科目id&学科id
        if(!empty($data['project_id'])){
            $parent = json_decode($data['project_id'], true);
            $where['project_id'] = $parent[0];
            if(!empty($parent[1])){
                $where['subject_id'] = $parent[1];
            }
        }

        //每页显示的条数
        $pagesize = (int)isset($data['pagesize']) && $data['pagesize'] > 0 ? $data['pagesize'] : 20;
        $page     = isset($data['page']) && $data['page'] > 0 ? $data['page'] : 1;
        $offset   = ($page - 1) * $pagesize;

        //計算總數
        $count = self::where(function($query) use ($data,$schoolarr) {
                if(isset($data['order_no']) && !empty($data['order_no'])){
                    $query->where('order_no',$data['order_on'])
                        ->orwhere('name',$data['order_on'])
                        ->orwhere('mobile',$data['order_on']);
                }
                $query->whereIn('school_id',$schoolarr);
            })
            ->where($where)
            ->whereBetween('create_time', [$state_time, $end_time])
            ->count();

        //数据
        $order = self::where(function($query) use ($data,$schoolarr) {
                if(isset($data['order_no']) && !empty($data['order_no'])){
                    $query->where('order_no',$data['order_on'])
                        ->orwhere('name',$data['order_on'])
                        ->orwhere('mobile',$data['order_on']);
                }
                $query->whereIn('school_id',$schoolarr);
            })
            ->where($where)
            ->whereBetween('create_time', [$state_time, $end_time])
            ->orderByDesc('id')
            ->offset($offset)->limit($pagesize)->get()->toArray();
        //循环查询分类
        $countprice = 0;
        if(!empty($order)){
            foreach ($order as $k=>&$v){
                //查学校
                $school = School::where(['id'=>$v['school_id']])->first();
                if($school){
                    $v['school_name'] = $school['school_name'];
                }
                $countprice = $countprice + $v['pay_price'];
                if($v['pay_type'] == 1){
                    $v['pay_type_text'] = '支付宝扫码';
                }else if ($v['pay_type'] == 2){
                    $v['pay_type_text'] = '微信扫码';
                }else if ($v['pay_type'] == 3){
                    $v['pay_type_text'] = '银联快捷支付';
                }else if ($v['pay_type'] == 4){
                    $v['pay_type_text'] = '微信小程序';
                }else if ($v['pay_type'] == 5){
                    $v['pay_type_text'] = '线下录入';
                }
                if($v['pay_status'] == 0){
                    $v['pay_status_text'] = '未支付';
                }else{
                    $v['pay_status_text'] = '已支付';
                }
                if($v['return_visit'] == 0){
                    $v['return_visit_text'] = '未回访';
                }else{
                    $v['return_visit_text'] = '已回访';
                }
                if($v['classes'] == 0){
                    $v['classes_text'] = '未开课';
                }else{
                    $v['classes_text'] = '已开课';
                }
                if($v['confirm_order_type'] == 1){
                    $v['confirm_order_type_text'] = '课程订单';
                }else if($v['confirm_order_type'] == 2){
                    $v['confirm_order_type_text'] = '报名订单';
                }else if($v['confirm_order_type'] == 3){
                    $v['confirm_order_type_text'] = '课程+报名订单';
                }
                if($v['first_pay'] == 1){
                    $v['first_pay_text'] = '全款';
                }else if($v['first_pay'] == 2){
                    $v['first_pay_text'] = '定金';
                }else if($v['first_pay'] == 3){
                    $v['first_pay_text'] = '部分尾款';
                }else if($v['first_pay'] == 4){
                    $v['first_pay_text'] = '最后一笔尾款';
                }
                if($v['confirm_status'] == 0){
                    $v['confirm_status_text'] = '未确认';
                }else if($v['confirm_status'] == 1){
                    $v['confirm_status_text'] = '确认';
                }else if($v['confirm_status'] == 2){
                    $v['confirm_status_text'] = '驳回';
                }
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
                //根据上传凭证人id查询凭证名称
                $adminname = Admin::where(['id'=>$v['pay_voucher_user_id']])->first();
                $v['pay_voucher_name'] = $adminname['username'];
                //驳回人查询
                $adminreject = Admin::where(['id'=>$v['reject_admin_id']])->first();
                $v['reject_admin_name'] = $adminreject['username'];
            }
        }
        $page=[
            'pagesize'=>$pagesize,
            'page' =>$page,
            'total'=>$count
        ];
        //总金额
        return ['code' => 200 , 'msg' => '查询成功','data'=>$order,'countprice'=>$countprice,'where'=>$data,'page'=>$page];
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
        //科目id&学科id
        if(!empty($data['project_id'])){
            $parent = json_decode($data['project_id'], true);
            $where['project_id'] = $parent[0];
            if(!empty($parent[1])){
                $where['subject_id'] = $parent[1];
            }
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
        unset($data['/admin/order/handOrder']);
        if(empty($data['education_id'])){
            unset($data['education_id']);
            unset($data['major_id']);
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
         * @param  订单详情
         * @param  id  订单id
         * @param  author  苏振文
         * @param  ctime   2020/9/8 15:58
         * return  array
         */
    public static function sureOrder($data){
        if(!isset($data['id']) || empty($data['id'])){
            return ['code' => 201 , 'msg' => '参数有误'];
        }
        $res =self::where(['id'=>$data['id'],'del_flag'=>0])->first();
        if($res){
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
            return ['code' => 200 , 'msg' => '查询成功','data'=>$res];
        }else{
            return ['code' => 201 , 'msg' => '查无此订单'];
        }
    }
    /*
         * @param  总校待确认订单列表   分校已提交订单
         * @param  project_id   数组形式
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
    public static function awaitOrder($data,$schoolarr){
        $where['del_flag'] = 0;  //未删除
        $where['confirm_status'] = 0;  //未确认
        //科目id&学科id
        if(!empty($data['project_id'])){
            $parent = json_decode($data['project_id'], true);
            $where['project_id'] = $parent[0];
            if(!empty($parent[1])){
                $where['subject_id'] = $parent[1];
            }
        }
        if(isset($data['school_id']) && !empty($data['school_id'])){
            $where['school_id'] = $data['school_id'];
        }
        if(isset($data['pay_type']) && !empty($data['pay_type'])){
            $where['pay_type'] = $data['pay_type'];
        }
        if(isset($data['confirm_order_type']) && !empty($data['confirm_order_type'])){
            $where['confirm_order_type'] = $data['confirm_order_type'];
        }
        if(isset($data['return_visit']) && !empty($data['return_visit'])){
            $where['return_visit'] = $data['return_visit'];
        }
        if(isset($data['classes']) && !empty($data['classes'])){
            $where['classes'] = $data['classes'];
        }

        //每页显示的条数
        $pagesize = (int)isset($data['pagesize']) && $data['pagesize'] > 0 ? $data['pagesize'] : 20;
        $page     = isset($data['page']) && $data['page'] > 0 ? $data['page'] : 1;
        $offset   = ($page - 1) * $pagesize;

        //計算總數
        $count = self::where(function($query) use ($data,$schoolarr) {
            if(isset($data['order_no']) && !empty($data['order_no'])){
                $query->where('order_no',$data['order_no'])
                    ->orwhere('name',$data['order_no'])
                    ->orwhere('mobile',$data['order_no']);
            }
            $query->whereIn('school_id',$schoolarr);
        })
        ->where($where)
        ->count();

        $order = self::where(function($query) use ($data,$schoolarr) {
            if(isset($data['order_no']) && !empty($data['order_no'])){
                $query->where('order_no',$data['order_no'])
                    ->orwhere('name',$data['order_no'])
                    ->orwhere('mobile',$data['order_no']);
            }
            $query->whereIn('school_id',$schoolarr);
        })
        ->where($where)
        ->orderByDesc('id')
        ->offset($offset)->limit($pagesize)->get()->toArray();
         //循环查询分类
        if(!empty($order)){
            foreach ($order as $k=>&$v){
                //查学校
                $school = School::where(['id'=>$v['school_id']])->first();
                if($school){
                    $v['school_name'] = $school['school_name'];
                }
                if($v['pay_type'] == 1){
                    $v['pay_type_text'] = '支付宝扫码';
                }else if ($v['pay_type'] == 2){
                    $v['pay_type_text'] = '微信扫码';
                }else if ($v['pay_type'] == 3){
                    $v['pay_type_text'] = '银联快捷支付';
                }else if ($v['pay_type'] == 4){
                    $v['pay_type_text'] = '微信小程序';
                }else if ($v['pay_type'] == 5){
                    $v['pay_type_text'] = '线下录入';
                }
                if($v['pay_status'] == 0){
                    $v['pay_status_text'] = '未支付';
                }else{
                    $v['pay_status_text'] = '已支付';
                }
                if($v['return_visit'] == 0){
                    $v['return_visit_text'] = '未回访';
                }else{
                    $v['return_visit_text'] = '已回访';
                }
                if($v['classes'] == 0){
                    $v['classes_text'] = '未开课';
                }else{
                    $v['classes_text'] = '已开课';
                }
                if($v['confirm_order_type'] == 1){
                    $v['confirm_order_type_text'] = '课程订单';
                }else if($v['confirm_order_type'] == 2){
                    $v['confirm_order_type_text'] = '报名订单';
                }else if($v['confirm_order_type'] == 3){
                    $v['confirm_order_type_text'] = '课程+报名订单';
                }
                if($v['first_pay'] == 1){
                    $v['first_pay_text'] = '全款';
                }else if($v['first_pay'] == 2){
                    $v['first_pay_text'] = '定金';
                }else if($v['first_pay'] == 3){
                    $v['first_pay_text'] = '部分尾款';
                }else if($v['first_pay'] == 4){
                    $v['first_pay_text'] = '最后一笔尾款';
                }
                if($v['confirm_status'] == 0){
                    $v['confirm_status_text'] = '未确认';
                }else if($v['confirm_status'] == 1){
                    $v['confirm_status_text'] = '确认';
                }else if($v['confirm_status'] == 2){
                    $v['confirm_status_text'] = '驳回';
                }
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
            'pagesize'=>$pagesize,
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
        $order = self::where(['id'=>$data['id']])->first();
        unset($data['/admin/order/notarizeOrder']);
        if(empty($data['education_id'])){
            unset($data['education_id']);
            unset($data['major_id']);
        }
        if(!isset($data['fee_id'])|| empty($data['fee_id'])){
            unset($data['fee_id']);
            unset($data['sign_Price']);
        }
        if(empty($data['course_Price'])){
            unset($data['course_Price']);
        }
        if($data['confirm_status'] == 1){
            $data['comfirm_time'] = date('Y-m-d H:i:s');
            $data['have_user_id'] = $admin['id'];
            $data['have_user_name'] = $admin['username'];
            //确认订单  排课
            //值班班主任 排课
            $classlead = Admin::where(['is_del'=>1,'is_forbid'=>1,'status'=>1,'is_use'=>1])->get()->toArray();
            if(!empty($classlead)){
                //上次值班的班主任id
                $leadid = Redis::get('classlead');
                if(empty($leadid)){
                    //如果没有 就从第一个开始
                    $data['have_user_id'] = $classlead[0]['id'];
                    Redis::setex('classlead' , $classlead[0]['id']);
                }else{
                    //如果有 判断班主任id是否等于或大于最后一个数，从第一个开始排 否者数组取下一个
                    $len = count($classlead,1);
                    if($classlead[$len-1] <= $leadid){
                        $data['have_user_id'] = $classlead[0]['id'];
                        Redis::setex('classlead' , $classlead[0]['id']);
                    }else{
                        foreach ($classlead as $k => $v){
                            if($v['id'] > $leadid){
                                $data['have_user_id'] = $v['id'];
                                Redis::setex('classlead' , $v['id']);
                                break;
                            }
                        }
                    }
                }
            }
            //计算成本
            //  after_tax_amount  税后金额
            //  return_commission_amount  返佣金额
            //  earnest_money  保证金
            //  agent_margin 代理保证金
            //  first_out_of_amount  1级抽离金额
            //  second_out_of_amount  2级抽离金额
        }
        if($data['confirm_status'] == 2){
            $data['reject_time'] = date('Y-m-d H:i:s');
            $data['reject_admin_id'] = $admin['id'];
        }
        $up = self::where(['id'=>$data['id']])->update($data);
        if($up){
            //确认或驳回订单之后 将用户信息加入学生表中    在学生与课程关联表中加数据   班主任排课
            $student = Student::where(['user_name'=>$data['name'],'mobile'=>$data['mobile']])->first();
            if(!$student){
                $add=[
                    'user_name' => $data['name'],
                    'mobile' => $data['mobile'],
                    'create_time' => date('Y-m-d H:i:s'),
                ];
                Student::insert($add);
                $student_course = [
                    'order_no' => $order['order_no'],
                    'student_name' => $data['name'],
                    'phone' => $data['mobile'],
                    'order_type' => $data['confirm_order_type'],
                    'school_id' => $data['school_id'],
                    'project_id' => $data['project_id'],
                    'subject_id' => $data['subject_id'],
                    'course_id' => $data['course_id'],
                    'status' => 0,
                    'create_time' => date('Y-m-d H:i:s')
                ];
                StudentCourse::insert($student_course);
            }
            return ['code' => 200 , 'msg' => '操作成功'];
        }else{
            return ['code' => 201 , 'msg' => '操作失败'];
        }
    }
    /*
         * @param  总校确认订单列表
         * @param  $user_id     参数
         * @param  author  苏振文
         * @param  ctime   2020/9/14 9:47
         * return  array
         */
    public static function sureOrderList($data,$schoolarr){
        $where['del_flag'] = 0;  //未删除
        $where['confirm_status'] = 1;  //已确认
        //科目id&学科id
        if(!empty($data['project_id'])){
            $parent = json_decode($data['project_id'], true);
            $where['project_id'] = $parent[0];
            if(!empty($parent[1])){
                $where['subject_id'] = $parent[1];
            }
        }
        if(isset($data['school_id']) && !empty($data['school_id'])){
            $where['school_id'] = $data['school_id'];
        }
        if(isset($data['pay_type']) && !empty($data['pay_type'])){
            $where['pay_type'] = $data['pay_type'];
        }
        if(isset($data['confirm_order_type']) && !empty($data['confirm_order_type'])){
            $where['confirm_order_type'] = $data['confirm_order_type'];
        }
        if(isset($data['return_visit']) && !empty($data['return_visit'])){
            $where['return_visit'] = $data['return_visit'];
        }
        if(isset($data['classes']) && !empty($data['classes'])){
            $where['classes'] = $data['classes'];
        }

        //每页显示的条数
        $pagesize = (int)isset($data['pagesize']) && $data['pagesize'] > 0 ? $data['pagesize'] : 20;
        $page     = isset($data['page']) && $data['page'] > 0 ? $data['page'] : 1;
        $offset   = ($page - 1) * $pagesize;

        //計算總數
        $count = self::where(function($query) use ($data,$schoolarr) {
            if(isset($data['order_no']) && !empty($data['order_no'])){
                $query->where('order_no',$data['order_on'])
                    ->orwhere('name',$data['order_on'])
                    ->orwhere('mobile',$data['order_on']);
            }
            $query->whereIn('school_id',$schoolarr);
        })
            ->where($where)
            ->count();

        $order = self::where(function($query) use ($data,$schoolarr) {
            if(isset($data['order_no']) && !empty($data['order_no'])){
                $query->where('order_no',$data['order_on'])
                    ->orwhere('name',$data['order_on'])
                    ->orwhere('mobile',$data['order_on']);
            }
            $query->whereIn('school_id',$schoolarr);
        })
            ->where($where)
            ->orderByDesc('id')
            ->offset($offset)->limit($pagesize)->get()->toArray();
        //循环查询分类
        if(!empty($order)){
            foreach ($order as $k=>&$v){
                if($v['pay_type'] == 1){
                    $v['pay_type_text'] = '支付宝扫码';
                }else if ($v['pay_type'] == 2){
                    $v['pay_type_text'] = '微信扫码';
                }else if ($v['pay_type'] == 3){
                    $v['pay_type_text'] = '银联快捷支付';
                }else if ($v['pay_type'] == 4){
                    $v['pay_type_text'] = '微信小程序';
                }else if ($v['pay_type'] == 5){
                    $v['pay_type_text'] = '线下录入';
                }
                if($v['pay_status'] == 0){
                    $v['pay_status_text'] = '未支付';
                }else{
                    $v['pay_status_text'] = '已支付';
                }
                if($v['return_visit'] == 0){
                    $v['return_visit_text'] = '未回访';
                }else{
                    $v['return_visit_text'] = '已回访';
                }
                if($v['classes'] == 0){
                    $v['classes_text'] = '未开课';
                }else{
                    $v['classes_text'] = '已开课';
                }
                if($v['confirm_order_type'] == 1){
                    $v['confirm_order_type_text'] = '课程订单';
                }else if($v['confirm_order_type'] == 2){
                    $v['confirm_order_type_text'] = '报名订单';
                }else if($v['confirm_order_type'] == 3){
                    $v['confirm_order_type_text'] = '课程+报名订单';
                }
                if($v['first_pay'] == 1){
                    $v['first_pay_text'] = '全款';
                }else if($v['first_pay'] == 2){
                    $v['first_pay_text'] = '定金';
                }else if($v['first_pay'] == 3){
                    $v['first_pay_text'] = '部分尾款';
                }else if($v['first_pay'] == 4){
                    $v['first_pay_text'] = '最后一笔尾款';
                }
                if($v['confirm_status'] == 0){
                    $v['confirm_status_text'] = '未确认';
                }else if($v['confirm_status'] == 1){
                    $v['confirm_status_text'] = '确认';
                }else if($v['confirm_status'] == 2){
                    $v['confirm_status_text'] = '驳回';
                }
                //查学校
                $school = School::where(['id'=>$v['school_id']])->first();
                if($school){
                    $v['school_name'] = $school['school_name'];
                }
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
                //根据上传凭证人id查询凭证名称
                $adminname = Admin::where(['id'=>$v['pay_voucher_user_id']])->first();
                $v['pay_voucher_name'] = $adminname['username'];
                //备注
                $beizhuname = Admin::where(['id'=>$v['admin_id']])->first();
                $v['remark_name'] = $beizhuname['username'];
            }
        }
        $page=[
            'pagesize'=>$pagesize,
            'page' =>$page,
            'total'=>$count
        ];
        return ['code' => 200 , 'msg' => '查询成功','data'=>$order,'where'=>$data,'page'=>$page];
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
        if(!isset($data['order_no']) || empty($data['order_no'])){
            return ['code' => 200 , 'msg' => '获取成功','data'=>$res];
        }
        $res = Pay_order_external::where(['order_no'=>$data['order_no'],'status'=>0])->first();
        if(!empty($res)){
            if($res['pay_type'] == 1 || $res['pay_type'] == 3){
                    $res['pay_type_text'] = '微信支付';
            }else{
                $res['pay_type_text'] = '支付宝支付';
            }
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
         * @param  （分校）未提交订单详情
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
        unset($data['begin_class']);
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
            'education_id' => isset($data['education_id'])?$data['education_id']:0, //院校id
            'major_id' => isset($data['major_id'])?$data['major_id']:0, //专业id
            'pay_status' => $external['pay_status'],//支付状态
            'pay_type' => $external['pay_type'], //支付方式（1支付宝扫码2微信扫码3银联快捷支付4微信小程序5线下录入）
            'confirm_status' => 0, //订单确认状态码
            'school_id' => $data['school_id'],  //所属分校
            'consignee_status' => 0,//0带收集 1收集中 2已收集 3重新收集
            'confirm_order_type' => $data['confirm_order_type'],//确认的订单类型 1课程订单 2报名订单3课程+报名订单
            'first_pay' => $data['first_pay'],//支付类型 1全款 2定金 3部分尾款 4最后一笔尾款
//            'classes' => $data['classes'],//开课状态
//            'return_visit' => $data['return_visit'],//回访状态
            'remark' => $data['remark'], //备注
            'pay_voucher_user_id' => $admin['id'], //上传凭证人
            'pay_voucher_time' => date('Y-m-d H:i:s'), //上传凭证时间
            'pay_voucher' => $data['remark'], //支付凭证
            'course_Price' => isset($data['course_Price'])?$data['course_Price']:0,
            'sum_Price' => $external['pay_price'],
            'sign_Price' => isset($data['sign_Price'])?$data['sign_Price']:0,
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
         * @param  project_id  arr
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
    public static function rejectOrder($data,$schoolarr){
        $where['del_flag'] = 0;  //未删除
        $where['confirm_status'] = 2;  //已驳回
        //科目id&学科id
        if(!empty($data['project_id'])){
            $parent = json_decode($data['project_id'], true);
            $where['project_id'] = $parent[0];
            if(!empty($parent[1])){
                $where['subject_id'] = $parent[1];
            }
        }
        $begindata="2020-03-04";
        $enddate = date('Y-m-d');
        $statetime = !empty($data['state_time'])?$data['state_time']:$begindata;
        $endtime = !empty($data['end_time'])?$data['end_time']:$enddate;
        $state_time = $statetime." 00:00:00";
        $end_time = $endtime." 23:59:59";
        if(isset($data['pay_type']) && !empty($data['pay_type'])){
            $where['pay_type'] = $data['pay_type'];
        }
        if(isset($data['confirm_order_type']) && !empty($data['confirm_order_type'])){
            $where['confirm_order_type'] = $data['confirm_order_type'];
        }
        if(isset($data['return_visit']) && !empty($data['return_visit'])){
            $where['return_visit'] = $data['return_visit'];
        }
        if(isset($data['classes']) && !empty($data['classes'])){
            $where['classes'] = $data['classes'];
        }

        //每页显示的条数
        $pagesize = (int)isset($data['pagesize']) && $data['pagesize'] > 0 ? $data['pagesize'] : 20;
        $page     = isset($data['page']) && $data['page'] > 0 ? $data['page'] : 1;
        $offset   = ($page - 1) * $pagesize;

        //計算總數
        $count = self::where(function($query) use ($data,$schoolarr) {
            if(isset($data['order_no']) && !empty($data['order_no'])){
                $query->where('order_no',$data['order_on'])
                    ->orwhere('name',$data['order_on'])
                    ->orwhere('mobile',$data['order_on']);
            }
            $query->whereIn('school_id',$schoolarr);
        })
        ->whereBetween('create_time', [$state_time, $end_time])
        ->where($where)
        ->count();

        $order = self::where(function($query) use ($data,$schoolarr) {
            if(isset($data['order_no']) && !empty($data['order_no'])){
                $query->where('order_no',$data['order_on'])
                    ->orwhere('name',$data['order_on'])
                    ->orwhere('mobile',$data['order_on']);
            }
            $query->whereIn('school_id',$schoolarr);
        })
        ->where($where)
        ->whereBetween('create_time', [$state_time, $end_time])
        ->orderByDesc('id')
        ->offset($offset)->limit($pagesize)->get()->toArray();
        //循环查询分类
        if(!empty($order)){
            foreach ($order as $k=>&$v){
                if($v['pay_type'] == 1){
                    $v['pay_type_text'] = '支付宝扫码';
                }else if ($v['pay_type'] == 2){
                    $v['pay_type_text'] = '微信扫码';
                }else if ($v['pay_type'] == 3){
                    $v['pay_type_text'] = '银联快捷支付';
                }else if ($v['pay_type'] == 4){
                    $v['pay_type_text'] = '微信小程序';
                }else if ($v['pay_type'] == 5){
                    $v['pay_type_text'] = '线下录入';
                }
                if($v['pay_status'] == 0){
                    $v['pay_status_text'] = '未支付';
                }else{
                    $v['pay_status_text'] = '已支付';
                }
                if($v['return_visit'] == 0){
                    $v['return_visit_text'] = '未回访';
                }else{
                    $v['return_visit_text'] = '已回访';
                }
                if($v['classes'] == 0){
                    $v['classes_text'] = '未开课';
                }else{
                    $v['classes_text'] = '已开课';
                }
                if($v['confirm_order_type'] == 1){
                    $v['confirm_order_type_text'] = '课程订单';
                }else if($v['confirm_order_type'] == 2){
                    $v['confirm_order_type_text'] = '报名订单';
                }else if($v['confirm_order_type'] == 3){
                    $v['confirm_order_type_text'] = '课程+报名订单';
                }
                if($v['first_pay'] == 1){
                    $v['first_pay_text'] = '全款';
                }else if($v['first_pay'] == 2){
                    $v['first_pay_text'] = '定金';
                }else if($v['first_pay'] == 3){
                    $v['first_pay_text'] = '部分尾款';
                }else if($v['first_pay'] == 4){
                    $v['first_pay_text'] = '最后一笔尾款';
                }
                if($v['confirm_status'] == 0){
                    $v['confirm_status_text'] = '未确认';
                }else if($v['confirm_status'] == 1){
                    $v['confirm_status_text'] = '确认';
                }else if($v['confirm_status'] == 2){
                    $v['confirm_status_text'] = '驳回';
                }
                //查学校
                $school = School::where(['id'=>$v['school_id']])->first();
                if($school){
                    $v['school_name'] = $school['school_name'];
                }
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
                //根据上传凭证人id查询凭证名称
                $adminname = Admin::where(['id'=>$v['pay_voucher_user_id']])->first();
                $v['pay_voucher_name'] = $adminname['username'];
                //驳回人查询
                $adminreject = Admin::where(['id'=>$v['reject_admin_id']])->first();
                $v['reject_admin_name'] = $adminreject['username'];
            }
        }
        $page=[
            'pagesize'=>$pagesize,
            'page' =>$page,
            'total'=>$count
        ];
        return ['code' => 200 , 'msg' => '查询成功','data'=>$order,'where'=>$data,'page'=>$page];
    }
    /*
         * @param  被驳回订单  取消订单
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
        $up = self::where(['id'=>$data['id']])->update(['confirm_status'=>0]);
        if($up){
            return ['code' => 200 , 'msg' => '操作成功'];
        }else{
            return ['code' => 201 , 'msg' => '操作失败'];
        }
    }
    /*
        * @param  驳回订单
        * @param  id 订单id
        * @param  auth 驳回原因
        * @param  author  苏振文
        * @param  ctime   2020/9/7 16:16
        * return  array
        */
    public static function DorejectOrder($data){
        if(empty($data['id'])){
            return ['code' => 201 , 'msg' => '参数错误'];
        }
        $admin = isset(AdminLog::getAdminInfo()->admin_user) ? AdminLog::getAdminInfo()->admin_user : [];
        $redate=[
            'reject_admin_id' => $admin['id'],
            'reject_time' => date('Y-m-d H:i:s'),
            'reject_des' => $data['reject_des'],
            'confirm_status' => 2
        ];
        $up = self::where(['id'=>$data['id']])->update($redate);
        if($up){
            return ['code' => 200 , 'msg' => '操作成功'];
        }else{
            return ['code' => 201 , 'msg' => '操作失败'];
        }
    }


    /*
     * @param  description   开课管理列表接口
     * @param  参数说明       body包含以下参数[
     *     category_id       项目-学科大小类(例如:[1,2])
     *     school_id         分校id
     *     order_type        订单类型(1.课程订单2.报名订单3.课程+报名订单)
     *     status            开课状态(0不开课 1开课)
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
        $open_class_count = StudentCourse::where(function($query) use ($body){
            //判断项目-学科大小类是否为空
            if(isset($body['category_id']) && !empty($body['category_id'])){
                $category_id= json_decode($body['category_id'] , true);
                $project_id = isset($category_id[0]) && $category_id[0] ? $category_id[0] : 0;
                $subject_id = isset($category_id[1]) && $category_id[1] ? $category_id[1] : 0;

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
                $query->where('order_type' , '=' , $body['order_type']);
            }

            //判断开课状态是否为空和合法
            if(isset($body['status']) && !empty($body['status']) && in_array($body['status'] , [0,1])){
                $query->where('status' , '=' , $body['status']);
            }
        })->where(function($query) use ($body){
            //判断订单号/手机号/姓名是否为空
            if(isset($body['keywords']) && !empty($body['keywords'])){
                $query->where('name','like','%'.$body['keywords'].'%')->orWhere('phone','like','%'.$body['keywords'].'%')->orWhere('order_no','like','%'.$body['keywords'].'%');
            }
        })->count();

        if($open_class_count > 0){
            //新数组赋值
            $order_array = [];

            //获取开课列表
            $open_class_list = StudentCourse::select('id as open_id' , 'create_time' , 'phone' , 'student_name' , 'course_id' , 'project_id' , 'subject_id' , 'school_id' , 'status')->where(function($query) use ($body){
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
                    $query->where('order_type' , '=' , $body['order_type']);
                }

                //判断开课状态是否为空和合法
                if(isset($body['status']) && !empty($body['status']) && in_array($body['status'] , [0,1])){
                    $query->where('status' , '=' , $body['status']);
                }
            })->where(function($query) use ($body){
                //判断订单号/手机号/姓名是否为空
                if(isset($body['keywords']) && !empty($body['keywords'])){
                    $query->where('name','like','%'.$body['keywords'].'%')->orWhere('mobile','like','%'.$body['keywords'].'%')->orWhere('order_no','like','%'.$body['keywords'].'%');
                }
            })->orderByDesc('create_time')->offset($offset)->limit($pagesize)->get()->toArray();

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
                    'open_id'       =>  $v['open_id'] ,
                    'create_time'   =>  $v['create_time'] ,
                    'phone'         =>  $v['phone'] ,
                    'student_name'  =>  $v['student_name'] ,
                    'status'        =>  (int)$v['status'] ,
                    'status_name'   =>  $v['status'] > 0 ? '已开课' : '未开课' ,
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
     * @param  description   开课管理订单详情接口
     * @param  参数说明       body包含以下参数[
     *       open_id         开课得管理id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-08
     * return string
     */
    public static function getOpenCourseInfo($body=[]) {
        //判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }

        //每页显示的条数
        $pagesize = isset($body['pagesize']) && $body['pagesize'] > 0 ? $body['pagesize'] : 20;
        $page     = isset($body['page']) && $body['page'] > 0 ? $body['page'] : 1;
        $offset   = ($page - 1) * $pagesize;

        //判断开课得管理id是否合法
        if(!isset($body['open_id']) || empty($body['open_id']) || $body['open_id'] <= 0){
            return ['code' => 202 , 'msg' => '开课得管理id不合法'];
        }

        //根据开课管理得id获取信息
        $info = StudentCourse::where('id' , $body['open_id'])->first();
        if(!$info || empty($info)){
            return ['code' => 203 , 'msg' => '此开课管理信息不存在'];
        }

        //学员名称
        $name   = $info['student_name'];
        //手机号
        $mobile = $info['phone'];
        //所属分校
        $school_id = $info['school_id'];
        //项目
        $project_id= $info['project_id'];
        //学科
        $subject_id= $info['subject_id'];
        //课程
        $course_id = $info['course_id'];

        //获取订单的总数量
        $order_count = self::where('name' , $name)->where('mobile' , $mobile)->where('school_id' , $school_id)->where('project_id' , $project_id)->where('subject_id' , $subject_id)->where('course_id' , $course_id)->where('del_flag' , 0)->count();

        //支付方式数组
        $pay_type_array = [1=>'支付宝扫码',2=>'微信扫码',3=>'银联快捷支付',4=>'微信小程序',5=>'线下录入'];

        //支付状态数组
        $pay_status_array = [0=>'未支付',1=>'已支付',2=>'支付失败',3=>'已退款'];

        //回访数组
        $return_visit_array = [0=>'否',1=>'是'];

        //开课数组
        $classes_array      = [0=>'否',1=>'是'];

        //订单类型数组
        $order_type_array   = [1=>'课程订单',2=>'报名订单',3=>'课程+报名订单'];

        //订单状态数组
        $order_status_array = [0=>'未确认',1=>'已确认',2=>'已驳回'];

        //缴费类型数组
        $first_pay_array    = [1=>'全款',2=>'定金',3=>'部分尾款',4=>'最后一笔尾款'];

        //判断订单总数量是否大于0
        if($order_count > 0){
            //新数组赋值
            $order_array = [];

            //获取订单列表
            $order_list = self::select('order_no' , 'create_time' , 'mobile' , 'name' , 'course_id' , 'project_id' , 'subject_id' , 'school_id' , 'pay_type' , 'course_Price' , 'sign_Price' , 'sum_Price' , 'pay_status' , 'classes' , 'return_visit' , 'pay_time' , 'confirm_order_type' , 'first_pay' , 'confirm_status' , 'pay_voucher')->where('name' , $name)->where('mobile' , $mobile)->where('school_id' , $school_id)->where('project_id' , $project_id)->where('subject_id' , $subject_id)->where('course_id' , $course_id)->where('del_flag' , 0)->orderByDesc('create_time')->offset($offset)->limit($pagesize)->get()->toArray();

            //循环获取相关信息
            foreach($order_list as $k=>$v){
                //分校的名称
                $school_name  = School::where('id' , $v['school_id'])->value('school_name');

                //项目名称
                $project_name = Project::where('id' , $v['project_id'])->value('name');

                //学科名称
                $subject_name = Project::where('parent_id' , $v['project_id'])->where('id' , $v['subject_id'])->value('name');

                //课程名称
                $course_name  = Course::where('id' , $v['course_id'])->value('course_name');

                //新数组信息赋值
                $order_array[] = [
                    'order_no'           =>  $v['order_no'] && !empty($v['order_no']) ? $v['order_no'] : '-' ,
                    'create_time'        =>  $v['create_time'] && !empty($v['create_time']) ? $v['create_time'] : '-' ,
                    'name'               =>  $v['name'] && !empty($v['name']) ? $v['name'] : '-' ,
                    'mobile'             =>  $v['mobile'] && !empty($v['mobile']) ? $v['mobile'] : '-' ,
                    'school_name'        =>  $school_name  && !empty($school_name)  ? $school_name  : '-' ,
                    'project_name'       =>  $project_name && !empty($project_name) ? $project_name : '-' ,
                    'subject_name'       =>  $subject_name && !empty($subject_name) ? $subject_name : '-' ,
                    'course_name'        =>  $course_name  && !empty($course_name)  ? $course_name  : '-' ,
                    'pay_type'           =>  $v['pay_type'] > 0 && isset($pay_type_array[$v['pay_type']]) ? $pay_type_array[$v['pay_type']] : '-' ,
                    'course_price'       =>  !empty($v['course_Price']) && $v['course_Price'] > 0 ? $v['course_Price'] : '-' ,
                    'sign_price'         =>  !empty($v['sign_Price']) && $v['sign_Price'] > 0 ? $v['sign_Price'] : '-' ,
                    'sum_price'          =>  !empty($v['sum_Price']) && $v['sum_Price'] > 0 ? $v['sum_Price'] : '-' ,
                    'pay_status'         =>  $v['pay_status'] > 0 && isset($pay_status_array[$v['pay_status']]) ? $pay_status_array[$v['pay_status']] : '-' ,
                    'return_visit'       =>  $v['return_visit'] ,
                    'return_visit_name'  =>  $v['return_visit'] > 0 && isset($return_visit_array[$v['return_visit']]) ? $return_visit_array[$v['return_visit']] : '-' ,
                    'classes'            =>  (int)$v['classes'] ,
                    'classes_name'       =>  $v['classes'] > 0 && isset($classes_array[$v['classes']]) ? $classes_array[$v['classes']] : '-' ,
                    'pay_time'           =>  $v['pay_time'] && !empty($v['pay_time']) ? $v['pay_time'] : '-' ,
                    'order_type'         =>  $v['confirm_order_type'] > 0 && isset($order_type_array[$v['confirm_order_type']]) ? $order_type_array[$v['confirm_order_type']] : '-' ,
                    'first_pay'          =>  $v['first_pay'] > 0 && isset($first_pay_array[$v['first_pay']]) ? $first_pay_array[$v['first_pay']] : '-' ,
                    'pay_voucher_name'   =>  $v['pay_voucher'] && !empty($v['pay_voucher']) ? '已上传' : '未上传' ,
                    'pay_voucher'        =>  $v['pay_voucher'] && !empty($v['pay_voucher']) ? 1 : 0 ,
                    'order_status_name'  =>  $v['confirm_status'] > 0 && isset($order_status_array[$v['confirm_status']]) ? $order_status_array[$v['confirm_status']] : '-' ,
                    'order_status'       =>  $v['confirm_status']
                ];
            }
            return ['code' => 200 , 'msg' => '获取订单详情成功' , 'data' => ['order_list' => $order_array , 'total' => $order_count , 'pagesize' => $pagesize , 'page' => $page]];
        }
        return ['code' => 200 , 'msg' => '获取订单详情成功' , 'data' => ['order_list' => [] , 'total' => 0 , 'pagesize' => $pagesize , 'page' => $page]];
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
    public static function doMakeSureOpenCourse($body=[]) {
        //判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }

        //判断开课id是否合法
        if(!isset($body['open_id']) || empty($body['open_id']) || $body['open_id'] <= 0){
            return ['code' => 202 , 'msg' => '开课id不合法'];
        }

        //根据开课管理得id获取信息
        $info = StudentCourse::where('id' , $body['open_id'])->first();
        if(!$info || empty($info)){
            return ['code' => 203 , 'msg' => '此开课管理信息不存在'];
        }

        //判断项目id是否合法
        if(!isset($body['project_id']) || empty($body['project_id']) || $body['project_id'] <= 0){
            return ['code' => 202 , 'msg' => '项目id不合法'];
        }

        //判断学科id是否合法
        if(!isset($body['subject_id']) || empty($body['subject_id']) || $body['subject_id'] <= 0){
            return ['code' => 202 , 'msg' => '学科id不合法'];
        }

        //判断课程id是否合法
        if(!isset($body['course_id']) || empty($body['course_id']) || $body['course_id'] <= 0){
            return ['code' => 202 , 'msg' => '课程id不合法'];
        }

        //判断学员名称是否合法
        if(!isset($body['student_name']) || empty($body['student_name'])){
            return ['code' => 202 , 'msg' => '学员名称为空'];
        }

        //判断学员手机号是否合法
        if(!isset($body['phone']) || empty($body['phone'])){
            return ['code' => 202 , 'msg' => '手机号为空'];
        }

        //判断此开课记录是否存在
        $info = self::where('id' , $body['open_id'])->first();
        if(!$info || empty($info)){
            return ['code' => 203 , 'msg' => '此开课记录不存在'];
        }

        //获取后端的操作员id
        $admin_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;

        //获取当前开课记录的状态
        $status = $info['status'] > 0 ? 0 : 1;

        //判断是否是取消状态
        if($status == 1){
            //封装成数组
            $array = [
                'student_name'   =>   $body['student_name'] ,
                'phone'          =>   $body['phone'] ,
                'project_id'     =>   $body['project_id'] ,
                'subject_id'     =>   $body['subject_id'] ,
                'course_id'      =>   $body['course_id'] ,
                'status'         =>   1 ,
                'create_id'      =>   $admin_id ,
                'open_time'      =>   date('Y-m-d H:i:s') ,
                'update_time'    =>   date('Y-m-d H:i:s')
            ];
        } else {
            //封装成数组
            $array = [
                'student_name'   =>   $body['student_name'] ,
                'phone'          =>   $body['phone'] ,
                'project_id'     =>   $body['project_id'] ,
                'subject_id'     =>   $body['subject_id'] ,
                'course_id'      =>   $body['course_id'] ,
                'status'         =>   0 ,
                'create_id'      =>   $admin_id ,
                'update_time'    =>   date('Y-m-d H:i:s')
            ];
        }

        //开启事务
        DB::beginTransaction();

        //根据开课id更新信息
        if(false !== StudentCourse::where('id',$body['open_id'])->update($array)){
            //更新学员开课状态
            //self::where('id',$info['order_id'])->update(['classes' => $status]);
            //事务提交
            DB::commit();
            return ['code' => 200 , 'msg' => '更新成功'];
        } else {
            //事务回滚
            DB::rollBack();
            return ['code' => 203 , 'msg' => '更新失败'];
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
    public static function getStudentCourseInfoById($body=[]){
        //判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }

        //判断开课id是否合法
        if(!isset($body['open_id']) || empty($body['open_id']) || $body['open_id'] <= 0){
            return ['code' => 202 , 'msg' => '开课id不合法'];
        }

        //根据开课管理得id获取信息
        $info = StudentCourse::select('student_name' , 'phone' , 'project_id' , 'subject_id' , 'course_id')->where('id' , $body['open_id'])->first();
        if(!$info || empty($info)){
            return ['code' => 203 , 'msg' => '此开课管理信息不存在'];
        } else {
            return ['code' => 200 , 'msg' => '获取详情成功' , 'data' => $info];
        }
    }
    
    
    /*
     * @param  description   财务管理-收入详情
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
    public static function getIncomeeList($body=[]) {
        //每页显示的条数
        $pagesize = isset($body['pagesize']) && $body['pagesize'] > 0 ? $body['pagesize'] : 20;
        $page     = isset($body['page']) && $body['page'] > 0 ? $body['page'] : 1;
        $offset   = ($page - 1) * $pagesize;

        //获取收入详情的总数量
        $count = self::where(function($query) use ($body){
            //判断分校id是否为空和合法
            if(isset($body['school_id']) && !empty($body['school_id']) && $body['school_id'] > 0){
                $query->where('school_id' , '=' , $body['school_id']);
            }
                
            //判断项目-学科大小类是否为空
            if(isset($body['category_id']) && !empty($body['category_id'])){
                $category_id= json_decode($body['category_id'] , true);
                $project_id = isset($category_id[0]) && $category_id[0] ? $category_id[0] : 0;
                $subject_id = isset($category_id[1]) && $category_id[1] ? $category_id[1] : 0;

                //判断项目id是否传递
                if($project_id && $project_id > 0){
                    $query->where('project_id' , '=' , $project_id);
                }

                //判断学科id是否传递
                if($subject_id && $subject_id > 0){
                    $query->where('subject_id' , '=' , $subject_id);
                }
            }
            
            //判断课程id是否为空和合法
            if(isset($body['course_id']) && !empty($body['course_id']) && $body['course_id'] > 0){
                $query->where('course_id' , '=' , $body['course_id']);
            }
            
            //获取日期
            if(isset($body['create_time']) && !empty($body['create_time'])){
                $create_time = json_decode($body['create_time'] , true);
                $state_time  = $create_time[0]." 00:00:00";
                $end_time    = $create_time[1]." 23:59:59";
                $query->whereBetween('create_time', [$state_time, $end_time]);
            }
        })->whereIn('education_id' , $body['schoolId'])->where('del_flag' , 0)->count();

        if($count > 0){
            //新数组赋值
            $array = [];

            //获取收入详情列表
            $list = self::where(function($query) use ($body){
                //判断分校id是否为空和合法
                if(isset($body['school_id']) && !empty($body['school_id']) && $body['school_id'] > 0){
                    $query->where('school_id' , '=' , $body['school_id']);
                }

                //判断项目-学科大小类是否为空
                if(isset($body['category_id']) && !empty($body['category_id'])){
                    $category_id= json_decode($body['category_id'] , true);
                    $project_id = isset($category_id[0]) && $category_id[0] ? $category_id[0] : 0;
                    $subject_id = isset($category_id[1]) && $category_id[1] ? $category_id[1] : 0;

                    //判断项目id是否传递
                    if($project_id && $project_id > 0){
                        $query->where('project_id' , '=' , $project_id);
                    }

                    //判断学科id是否传递
                    if($subject_id && $subject_id > 0){
                        $query->where('subject_id' , '=' , $subject_id);
                    }
                }

                //判断课程id是否为空和合法
                if(isset($body['course_id']) && !empty($body['course_id']) && $body['course_id'] > 0){
                    $query->where('course_id' , '=' , $body['course_id']);
                }

                //获取日期
                if(isset($body['create_time']) && !empty($body['create_time'])){
                    $create_time = json_decode($body['create_time'] , true);
                    $state_time  = $create_time[0]." 00:00:00";
                    $end_time    = $create_time[1]." 23:59:59";
                    $query->whereBetween('create_time', [$state_time, $end_time]);
                }
            })->whereIn('education_id' , $body['schoolId'])->where('del_flag' , 0)->orderByDesc('create_time')->offset($offset)->limit($pagesize)->get()->toArray();

            //循环获取相关信息
            foreach($list as $k=>$v){
                //项目名称
                $project_name = Project::where('id' , $v['project_id'])->value('name');

                //学科名称
                $subject_name = Project::where('parent_id' , $v['project_id'])->where('id' , $v['subject_id'])->value('name');

                //课程名称
                $course_name  = Course::where('id' , $v['course_id'])->value('course_name');

                //分校的名称
                $school_name  = School::where('id' , $v['school_id'])->value('school_name');

                //数组赋值
                $array[] = [
                    'create_time'   =>  date('Ymd' ,strtotime($v['create_time'])) ,
                    'school_name'   =>  $school_name  && !empty($school_name)  ? $school_name  : '' ,
                    'project_name'  =>  $project_name && !empty($project_name) ? $project_name : '' ,
                    'subject_name'  =>  $subject_name && !empty($subject_name) ? $subject_name : '' ,
                    'course_name'   =>  $course_name  && !empty($course_name)  ? $course_name  : '' ,
                    'received_order'=>  0 ,  //到账订单数量
                    'refund_order'  =>  0 ,  //退费订单数量
                    'received_money'=>  0 ,  //到账金额
                    'refund_money'  =>  0 ,  //退费金额
                    'enroll_price'  =>  $v['sign_Price'] ,  //报名费用
                    'prime_cost'    =>  $v['sum_Price']  ,  //成本
                    'return_commission_amount' => $v['return_commission_amount'] ,  //返佣金额(实际佣金)
                ];
            }
            return ['code' => 200 , 'msg' => '获取列表成功' , 'data' => ['list' => $array , 'total' => $count , 'pagesize' => $pagesize , 'page' => $page]];
        }
        return ['code' => 200 , 'msg' => '获取列表成功' , 'data' => ['list' => [] , 'total' => 0 , 'pagesize' => $pagesize , 'page' => $page]];
    }
}
