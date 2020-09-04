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
            ->whereBetween('create_at', [$state_time, $end_time])
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
        $data['create_time'] =date('Y-m-d H:i:s');
        $data['pay_time'] =date('Y-m-d H:i:s');
        $data['pay_status'] = 1;
        $data['collecting_data'] = 1;
        $add = self::insert($data);
        if($add){
            return ['code' => 200 , 'msg' => '报单成功'];
        }else{
            return ['code' => 201 , 'msg' => '报单失败'];
        }
    }
}
