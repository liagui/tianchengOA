<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Pay_order_inside;
use App\Models\Orderdocumentary;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use App\Models\School;
class Student extends Model {
    //指定别的表名
    public $table = 'student';
    //时间戳设置
    public $timestamps = false;

    public static function getStudentStatus($data){
        //项目project_id 所属分校school_id 订单状态confirm_status 付款方式pay_type 是否回访return_visit 是否开课classes 手机/姓名keyword
        //每页显示的条数
        $pagesize = (int)isset($data['pageSize']) && $data['pageSize'] > 0 ? $data['pageSize'] : 20;
        $page     = isset($data['page']) && $data['page'] > 0 ? $data['page'] : 1;
        $offset   = ($page - 1) * $pagesize;
        //计算总数
        $count = Pay_order_inside::select()->where("seas_status",0)->where(function($query) use ($data) {
            if(isset($data['project_id']) && !empty($data['project_id'])){
                $query->where('project_id',$data['project_id']);
            }
            if(isset($data['school_id']) && !empty($data['school_id'])){
                $query->where('school_id',$data['school_id']);
            }
            if(isset($data['return_visit']) && $data['return_visit'] != -1){
                $query->where('return_visit',$data['return_visit']);
            }
            if(isset($data['pay_type']) && $data['pay_type'] != -1){
                $query->where('pay_type',$data['pay_type']);
            }
            if(isset($data['classes']) && $data['classes'] != -1){
                $query->where('classes',$data['classes']);
            }
            if(isset($data['confirm_status']) && $data['confirm_status'] != -1){
                $query->where('confirm_status',$data['confirm_status']);
            }
            if(isset($data['keyword']) && !empty(isset($data['keyword']))){
                $query->where('name','like','%'.$data['keyword'].'%')->orWhere('mobile','like','%'.$data['keyword'].'%');
            }
        })->count();
        //分页数据
        $data = Pay_order_inside::select()->where("seas_status",0)->where(function($query) use ($data) {
            if(isset($data['project_id']) && !empty($data['project_id'])){
                $query->where('project_id',$data['project_id']);
            }
            if(isset($data['school_id']) && !empty($data['school_id'])){
                $query->where('school_id',$data['school_id']);
            }
            if(isset($data['return_visit']) && $data['return_visit'] != -1){
                $query->where('return_visit',$data['return_visit']);
            }
            if(isset($data['pay_type']) && $data['pay_type'] != -1){
                $query->where('pay_type',$data['pay_type']);
            }
            if(isset($data['classes']) && $data['classes'] != -1){
                $query->where('classes',$data['classes']);
            }
            if(isset($data['confirm_status']) && $data['confirm_status'] != -1){
                $query->where('confirm_status',$data['confirm_status']);
            }
            if(isset($data['keyword']) && !empty(isset($data['keyword']))){
                $query->where('name','like','%'.$data['keyword'].'%')->orWhere('mobile','like','%'.$data['keyword'].'%');
            }
        })->offset($offset)->limit($pagesize)->get()->toArray();

        //获取分校名称
        foreach($data as $k =>$v){
             $school_name = School::select("school_name")->where('id',$v['school_id'])->first();
             if(!empty($school_name)){
                $data[$k]['school_name'] = $school_name['school_name'];
             }
        }
        $page=[
            'pageSize'=>$pagesize,
            'page' =>$page,
            'total'=>$count
        ];
        if($data){
            return ['code' => 200, 'msg' => '查询成功', 'data' => $data,'page'=>$page];
        }else{
            return ['code' => 202, 'msg' => '查询暂无数据'];
        }
    }
    //跟单
    public static function documentary($data){
        unset($data['/admin/documentary']);
        $order = Pay_order_inside::where("id",$data['order_id'])->first();
        if(empty($order)){
            return ['code' => 202 , 'msg' => '订单记录不存在，请检查'];
        }
        //跟单的是公海单  需要修改状态
        if($order['seas_status'] == 1){
            $res1 = Pay_order_inside::where('id',$data['order_id'])->update(['seas_status'=>0]);
        }
        $data['follow_time'] = date("Y-m-d H:i:s");
        $res = Orderdocumentary::insert($data);
        if($res && $res1){
            return ['code' => 200 , 'msg' => '跟单成功'];
        }else{
            return ['code' => 202 , 'msg' => '跟单失败'];
        }
    }
    //获取跟单记录
    public static function getdocumentary($data){
        $data = Orderdocumentary::where("order_id",$data['order_id'])->get();
        if($data){
            return ['code' => 200, 'msg' => '查询成功', 'data' => $data];
        }else{
            return ['code' => 202, 'msg' => '查询暂无数据'];
        }
    }
    //转单
    public static function transferOrder($data){
        unset($data['/admin/transferOrder']);
        $update['have_user_id'] = $data['teacher_id'];
        $update['have_user_name'] = $data['teacher_name'];
        $order = Pay_order_inside::where("id",$data['order_id'])->first();
        if(empty($order)){
            return ['code' => 202 , 'msg' => '订单记录不存在，请检查'];
        }
        $res = Pay_order_inside::where('id',$data['order_id'])->update($update);
        if($res){
            return ['code' => 200 , 'msg' => '转单成功'];
        }else{
            return ['code' => 202 , 'msg' => '转单失败'];
        }
    }
    //业绩查询 班主任自己的业绩
    public static function getStudentPerformance($data){
        //查询条件  时间区间 项目  分校 开课状态  回访状态  手机号/姓名
        //已完成业绩：145135.65元 总回放单数：1000 已回访单数：1900 未回访单数：100
        //获取登录id
        $admin = isset(AdminLog::getAdminInfo()->admin_user) ? AdminLog::getAdminInfo()->admin_user: [];
        $user_id  = $admin['id'];
        //获取班主任
        $teacher = Teacher::select("id","username")->where("id",$user_id)->first();
        //获取数据
        $one = array();
        //已回访单数
        $one['yet_singular'] = Pay_order_inside::where(['return_visit'=>1,'have_user_id'=>$user_id,"seas_status"=>0])->where(function($query) use ($data){
            if(isset($data['start_time']) && !empty(isset($data['start_time']))  && isset($data['end_time']) && !empty(isset($data['end_time']))){
                $query->whereBetween('comfirm_time',[date("Y-m-d H:i:s",strtotime($data['start_time'])),date("Y-m-d H:i:s",strtotime($data['end_time']))]);
            }
        })->count();
        //未回放单数
        $one['not_singular'] = Pay_order_inside::where(['return_visit'=>0,'have_user_id'=>$user_id,"seas_status"=>0])->where(function($query) use ($data){
            if(isset($data['start_time']) && !empty(isset($data['start_time']))  && isset($data['end_time']) && !empty(isset($data['end_time']))){
                $query->whereBetween('comfirm_time',[date("Y-m-d H:i:s",strtotime($data['start_time'])),date("Y-m-d H:i:s",strtotime($data['end_time']))]);
            }
        })->count();
        //总回放单数
        $one['sum_singular'] = $one['yet_singular'] + $one['not_singular'];
        //已完成业绩
        $one['completed_performance'] = Pay_order_inside::select("course_Price")->where(['have_user_id'=>$user_id,"seas_status"=>0])
        ->where(function($query) use ($data){
            if(isset($data['start_time']) && !empty(isset($data['start_time']))  && isset($data['end_time']) && !empty(isset($data['end_time']))){
                $query->whereBetween('comfirm_time',[date("Y-m-d H:i:s",strtotime($data['start_time'])),date("Y-m-d H:i:s",strtotime($data['end_time']))]);
            }
        })->sum("course_Price");
        //退费业绩
        $one['return_premium'] = DB::table("refund_order")->where(["teacher_id"=>$user_id,"refund_plan"=>2,"confirm_status"=>1])->where(function($query) use ($data){
            if(isset($data['start_time']) && !empty(isset($data['start_time']))  && isset($data['end_time']) && !empty(isset($data['end_time']))){
                $query->whereBetween('refund_time',[date("Y-m-d H:i:s",strtotime($data['start_time'])),date("Y-m-d H:i:s",strtotime($data['end_time']))]);
            }
        })->sum("refund_Price");
        //获取列表数据
        //每页显示的条数
        $pagesize = (int)isset($data['pageSize']) && $data['pageSize'] > 0 ? $data['pageSize'] : 20;
        $page     = isset($data['page']) && $data['page'] > 0 ? $data['page'] : 1;
        $offset   = ($page - 1) * $pagesize;
        //计算总数
        $count = Pay_order_inside::select()->where("seas_status",0)->where("seas_status",0)->where("have_user_id",$user_id)->where(function($query) use ($data) {
            if(isset($data['project_id']) && !empty($data['project_id'])){
                $query->where('project_id',$data['project_id']);
            }
            if(isset($data['school_id']) && !empty($data['school_id'])){
                $query->where('school_id',$data['school_id']);
            }
            if(isset($data['return_visit']) && $data['return_visit'] != -1){
                $query->where('return_visit',$data['return_visit']);
            }
            if(isset($data['classes']) && $data['classes'] != -1){
                $query->where('classes',$data['classes']);
            }
            if(isset($data['confirm_status']) && $data['confirm_status'] != -1){
                $query->where('confirm_status',$data['confirm_status']);
            }
            if(isset($data['start_time']) && !empty(isset($data['start_time']))  && isset($data['end_time']) && !empty(isset($data['end_time']))){
                $query->whereBetween('comfirm_time',[date("Y-m-d H:i:s",strtotime($data['start_time'])),date("Y-m-d H:i:s",strtotime($data['end_time']))]);
            }
            if(isset($data['keyword']) && !empty(isset($data['keyword']))){
                $query->where('name','like','%'.$data['keyword'].'%')->orWhere('mobile','like','%'.$data['keyword'].'%');
            }
        })->count();
        //分页数据
        $data = Pay_order_inside::select()->where("seas_status",0)->where("have_user_id",$user_id)->where(function($query) use ($data) {
            if(isset($data['project_id']) && !empty($data['project_id'])){
                $query->where('project_id',$data['project_id']);
            }
            if(isset($data['school_id']) && !empty($data['school_id'])){
                $query->where('school_id',$data['school_id']);
            }
            if(isset($data['return_visit']) && $data['return_visit'] != -1){
                $query->where('return_visit',$data['return_visit']);
            }
            if(isset($data['classes']) && $data['classes'] != -1){
                $query->where('classes',$data['classes']);
            }
            if(isset($data['confirm_status']) && $data['confirm_status'] != -1){
                $query->where('confirm_status',$data['confirm_status']);
            }
            if(isset($data['start_time']) && !empty(isset($data['start_time']))  && isset($data['end_time']) && !empty(isset($data['end_time']))){
                $query->whereBetween('comfirm_time',[date("Y-m-d H:i:s",strtotime($data['start_time'])),date("Y-m-d H:i:s",strtotime($data['end_time']))]);
            }
            if(isset($data['keyword']) && !empty(isset($data['keyword']))){
                $query->where('name','like','%'.$data['keyword'].'%')->orWhere('mobile','like','%'.$data['keyword'].'%');
            }
        })->offset($offset)->limit($pagesize)->get()->toArray();
        $page=[
            'pageSize'=>$pagesize,
            'page' =>$page,
            'total'=>$count
        ];
        if($data){
            return ['code' => 200, 'msg' => '查询成功', 'data' => $data,'page'=>$page,'one'=>$one];
        }else{
            return ['code' => 202, 'msg' => '查询暂无数据'];
        }

    }
    //学员总览 班主任自己的学员
    public static function getStudent($data){
        //查询条件   项目-学科  分校 开课状态  回访状态  手机号/姓名 订单状态 付款形式
        //获取登录id
        $admin = isset(AdminLog::getAdminInfo()->admin_user) ? AdminLog::getAdminInfo()->admin_user: [];
        $user_id  = $admin['id'];
        //每页显示的条数
        $pagesize = (int)isset($data['pageSize']) && $data['pageSize'] > 0 ? $data['pageSize'] : 20;
        $page     = isset($data['page']) && $data['page'] > 0 ? $data['page'] : 1;
        $offset   = ($page - 1) * $pagesize;
        //计算总数
        $count = Pay_order_inside::select()->where("seas_status",0)->where("have_user_id",$user_id)->where(function($query) use ($data) {
            if(isset($data['project_id']) && !empty($data['project_id'])){
                $query->where('project_id',$data['project_id']);
            }
            if(isset($data['subject_id']) && !empty($data['subject_id'])){
                $query->where('subject_id',$data['subject_id']);
            }
            if(isset($data['school_id']) && !empty($data['school_id'])){
                $query->where('school_id',$data['school_id']);
            }
            if(isset($data['return_visit']) && $data['return_visit'] != -1){
                $query->where('return_visit',$data['return_visit']);
            }
            if(isset($data['pay_type']) && $data['pay_type'] != -1){
                $query->where('pay_type',$data['pay_type']);
            }
            if(isset($data['classes']) && $data['classes'] != -1){
                $query->where('classes',$data['classes']);
            }
            if(isset($data['confirm_status']) && $data['confirm_status'] != -1){
                $query->where('confirm_status',$data['confirm_status']);
            }
            if(isset($data['keyword']) && !empty(isset($data['keyword']))){
                $query->where('name','like','%'.$data['keyword'].'%')->orWhere('mobile','like','%'.$data['keyword'].'%');
            }
        })->count();
        //分页数据
        $data = Pay_order_inside::select()->where("seas_status",0)->where("have_user_id",$user_id)->where(function($query) use ($data) {
            if(isset($data['project_id']) && !empty($data['project_id'])){
                $query->where('project_id',$data['project_id']);
            }
            if(isset($data['subject_id']) && !empty($data['subject_id'])){
                $query->where('subject_id',$data['subject_id']);
            }
            if(isset($data['school_id']) && !empty($data['school_id'])){
                $query->where('school_id',$data['school_id']);
            }
            if(isset($data['return_visit']) && $data['return_visit'] != -1){
                $query->where('return_visit',$data['return_visit']);
            }
            if(isset($data['pay_type']) && $data['pay_type'] != -1){
                $query->where('pay_type',$data['pay_type']);
            }
            if(isset($data['classes']) && $data['classes'] != -1){
                $query->where('classes',$data['classes']);
            }
            if(isset($data['confirm_status']) && $data['confirm_status'] != -1){
                $query->where('confirm_status',$data['confirm_status']);
            }
            if(isset($data['keyword']) && !empty(isset($data['keyword']))){
                $query->where('name','like','%'.$data['keyword'].'%')->orWhere('mobile','like','%'.$data['keyword'].'%');
            }
        })->offset($offset)->limit($pagesize)->get()->toArray();
        $page=[
            'pageSize'=>$pagesize,
            'page' =>$page,
            'total'=>$count
        ];
        if($data){
            return ['code' => 200, 'msg' => '查询成功', 'data' => $data,'page'=>$page];
        }else{
            return ['code' => 202, 'msg' => '查询暂无数据'];
        }
    }
    //学员公海 被放入公海的所有学员订单
    public static function getStudentSeas($data){
    //查询条件   项目  分校 开课状态  回访状态  手机号/姓名 订单状态 付款形式
        //每页显示的条数
        $pagesize = (int)isset($data['pageSize']) && $data['pageSize'] > 0 ? $data['pageSize'] : 20;
        $page     = isset($data['page']) && $data['page'] > 0 ? $data['page'] : 1;
        $offset   = ($page - 1) * $pagesize;
        //计算总数
        $count = Pay_order_inside::select()->where("seas_status",1)->where(function($query) use ($data) {
            if(isset($data['project_id']) && !empty($data['project_id'])){
                $query->where('project_id',$data['project_id']);
            }
            if(isset($data['school_id']) && !empty($data['school_id'])){
                $query->where('school_id',$data['school_id']);
            }
            if(isset($data['return_visit']) && $data['return_visit'] != -1){
                $query->where('return_visit',$data['return_visit']);
            }
            if(isset($data['pay_type']) && $data['pay_type'] != -1){
                $query->where('pay_type',$data['pay_type']);
            }
            if(isset($data['classes']) && $data['classes'] != -1){
                $query->where('classes',$data['classes']);
            }
            if(isset($data['confirm_status']) && $data['confirm_status'] != -1){
                $query->where('confirm_status',$data['confirm_status']);
            }
            if(isset($data['keyword']) && !empty(isset($data['keyword']))){
                $query->where('name','like','%'.$data['keyword'].'%')->orWhere('mobile','like','%'.$data['keyword'].'%');
            }
        })->count();
        //分页数据
        $data = Pay_order_inside::select()->where("seas_status",1)->where(function($query) use ($data) {
            if(isset($data['project_id']) && !empty($data['project_id'])){
                $query->where('project_id',$data['project_id']);
            }
            if(isset($data['school_id']) && !empty($data['school_id'])){
                $query->where('school_id',$data['school_id']);
            }
            if(isset($data['return_visit']) && $data['return_visit'] != -1){
                $query->where('return_visit',$data['return_visit']);
            }
            if(isset($data['pay_type']) && $data['pay_type'] != -1){
                $query->where('pay_type',$data['pay_type']);
            }
            if(isset($data['classes']) && $data['classes'] != -1){
                $query->where('classes',$data['classes']);
            }
            if(isset($data['confirm_status']) && $data['confirm_status'] != -1){
                $query->where('confirm_status',$data['confirm_status']);
            }
            if(isset($data['keyword']) && !empty(isset($data['keyword']))){
                $query->where('name','like','%'.$data['keyword'].'%')->orWhere('mobile','like','%'.$data['keyword'].'%');
            }
        })->offset($offset)->limit($pagesize)->get()->toArray();
        $page=[
            'pageSize'=>$pagesize,
            'page' =>$page,
            'total'=>$count
        ];
        if($data){
            return ['code' => 200, 'msg' => '查询成功', 'data' => $data,'page'=>$page];
        }else{
            return ['code' => 202, 'msg' => '查询暂无数据'];
        }

    }
}
