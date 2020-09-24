<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pay_order_inside;
use App\Models\Orderdocumentary;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use App\Models\School;
use App\Models\Category;
use App\Models\Course;
use App\Models\StudentCourse;
class Student extends Model {
    //指定别的表名
    public $table = 'student';
    //时间戳设置
    public $timestamps = false;

    public static function getStudentStatus($data){
        //项目project_id 所属分校school_id 订单状态confirm_status 付款方式pay_type 是否回访return_visit 是否开课classes 手机/姓名keyword
        //每页显示的条数
        $pagesize = (int)isset($data['pagesize']) && $data['pagesize'] > 0 ? $data['pagesize'] : 20;
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
            if(isset($data['pay_type']) && $data['pay_type'] != -1){
                $query->where('pay_type',$data['pay_type']);
            }
            if(isset($data['confirm_status']) && $data['confirm_status'] != -1){
                $query->where('confirm_status',$data['confirm_status']);
            }
            if(isset($data['keyword']) && !empty(isset($data['keyword']))){
                $query->where('name','like','%'.$data['keyword'].'%')->orWhere('mobile','like','%'.$data['keyword'].'%');
            }
        })->count();
        //分页数据
        $res = Pay_order_inside::select()->where("seas_status",0)->where(function($query) use ($data) {
            if(isset($data['project_id']) && !empty($data['project_id'])){
                $query->where('project_id',$data['project_id']);
            }
            if(isset($data['school_id']) && !empty($data['school_id'])){
                $query->where('school_id',$data['school_id']);
            }
            if(isset($data['pay_type']) && $data['pay_type'] != -1){
                $query->where('pay_type',$data['pay_type']);
            }
            if(isset($data['confirm_status']) && $data['confirm_status'] != -1){
                $query->where('confirm_status',$data['confirm_status']);
            }
            if(isset($data['keyword']) && !empty(isset($data['keyword']))){
                $query->where('name','like','%'.$data['keyword'].'%')->orWhere('mobile','like','%'.$data['keyword'].'%');
            }
        })->orderByDesc("id")->get()->toArray();
        //获取分校名称
        foreach($res as $k =>&$v){

            //是否回访
            $a = Orderdocumentary::where("order_id",$v['id'])->first();
            if(empty($a)){
                $v['return_visit'] = 0;
            }else{
                $v['return_visit'] = 1;
            }
            //是否开课
            $c = StudentCourse::where(["order_no"=>$v['order_no'],"status"=>1])->first();
            if(empty($c)){
                $v['classes'] = 0;
            }else{
                $v['classes'] = 1;
            }
            $v['school_name'] = School::select("school_name")->where("id",$v['school_id'])->first()['school_name'];
            $v['project_name'] = Category::select("name")->where("id",$v['project_id'])->first()['name'];
            $v['subject_name'] = Category::select("name")->where("id",$v['subject_id'])->first()['name'];
            $v['course_name'] = Course::select("course_name")->where("id",$v['course_id'])->first()['course_name'];

            //confirm_order_type 确认的订单类型 1课程订单 2报名订单3课程+报名订单
            //first_pay  支付类型 1全款 2定金 3部分尾款 4最后一笔尾款
            //classes  是否开课 0不开课 1开课）
            //return_visit  回访状态 0未回访 1 已回访  是否回访
            if($v['confirm_order_type'] == 1){
                $data[$k]['confirm_order_type_name'] = "课程订单";
            }else if($v['confirm_order_type'] == 2){
                $data[$k]['confirm_order_type_name'] = "报名订单";
            }else{
                $data[$k]['confirm_order_type_name'] = "课程+报名订单";
            }

            if($v['first_pay'] == 1){
                $data[$k]['first_pay_name'] = "全款";
            }else if($v['first_pay'] == 2){
                $data[$k]['first_pay_name'] = "定金";
            }else if($v['first_pay'] == 3){
                $data[$k]['first_pay_name'] = "部分尾款";
            }else{
                $data[$k]['first_pay_name'] = "最后一笔尾款";
            }
            if($v['classes'] == 1){
                $data[$k]['classes_name'] = "开课";
            }else{
                $data[$k]['classes_name'] = "不开课";
            }

            if($v['return_visit'] == 1){
                $data[$k]['return_visit_name'] = "已回访";
            }else{
                $data[$k]['return_visit_name'] = "未回访";
            }

            if($v['return_visit'] == 1){
                $data[$k]['return_visit_status'] = "是";
            }else{
                $data[$k]['return_visit_status'] = "否";
            }

            $school_name = School::select("school_name")->where('id',$v['school_id'])->first();
            if(!empty($school_name)){
            $data[$k]['school_name'] = $school_name['school_name'];
            }
        }
        if(isset($data['return_visit']) && $data['return_visit'] != -1){
            $res = array_filter($res, function($t) use ($data) { return $t['return_visit'] == $data['return_visit']; });
            $res = array_merge($res);
            $count = count($res);
        }
        if(isset($data['classes']) && $data['classes'] != -1){
            $res = array_filter($res, function($t) use ($data) { return $t['classes'] == $data['classes']; });
            $res = array_merge($res);
            $count = count($res);
        }

        $total = $count;
        if($total > 0){
            $arr = array_merge($res);
            $start=($page-1)*$pagesize;
            $limit_s=$start+$pagesize;
            $list=[];
            for($i=$start;$i<$limit_s;$i++){
                if(!empty($arr[$i])){
                    array_push($list,$arr[$i]);
                }
            }
        }else{
            $list=[];
        }


        $page=[
            'pagesize'=>$pagesize,
            'page' =>$page,
            'total'=>$count
        ];
        if($list){
            return ['code' => 200, 'msg' => '查询成功','data' => $list,'page'=>$page];
        }else{
            return ['code' => 200, 'msg' => '查询暂无数据','data' => [],'page'=>$page];
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
        if($res || $res1){
            return ['code' => 200 , 'msg' => '跟单成功'];
        }else{
            return ['code' => 202 , 'msg' => '跟单失败'];
        }
    }
    //获取跟单记录
    public static function getdocumentary($data){
        $res = Orderdocumentary::where("order_id",$data['order_id'])->get()->toArray();
        $last_follow_time = Orderdocumentary::where("order_id",$data['order_id'])->orderByDesc('id')->first();
        array_unshift($res,$last_follow_time['last_follow_time']);
        if($res){
            return ['code' => 200, 'msg' => '查询成功', 'data' => $res];
        }else{
            return ['code' => 200, 'msg' => '查询暂无数据', 'data' => []];
        }
    }
    //转单
    public static function transferOrder($data){
        unset($data['/admin/transferOrder']);
        $update['have_user_id'] = $data['teacher_id'];
        $teacher_name = Teacher::select("username")->where("id",$data['teacher_id'])->first();
        $update['have_user_name'] = $teacher_name['username'];
        $order = Pay_order_inside::where("order_no",$data['order_id'])->first();
        if(empty($order)){
            return ['code' => 202 , 'msg' => '订单记录不存在，请检查'];
        }
        $res = Pay_order_inside::where('order_no',$data['order_id'])->update($update);
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
        //项目和学科
        if(!empty($data['project_id'])){
            $s_id = json_decode($data['project_id']);
            if(!empty($s_id[0])){
                $data['project_id'] = $s_id[0];
            }else{
                $data['project_id'] = 0;
            }
        }
        //获取班主任
        $teacher = Teacher::select("id","username")->where("id",$user_id)->first();
        //获取数据
        $one = array();
        $res1 = Pay_order_inside::select()->where("seas_status",0)->where("have_user_id",$user_id)->get()->toArray();
        foreach($res1 as $k => &$v){
            //是否回访
            $a = Orderdocumentary::where("order_id",$v['id'])->first();
            if(empty($a)){
                $v['return_visit'] = 0;
            }else{
                $v['return_visit'] = 1;
            }
            //是否开课
            $c = StudentCourse::where(["order_no"=>$v['order_no'],"status"=>1])->first();
            if(empty($c)){
                $v['classes'] = 0;
            }else{
                $v['classes'] = 1;
            }
        }
        //已回访单数
        $yet_singular = array_filter($res1, function($t) use ($data) { return $t['return_visit'] == 1; });
        $yet_singular = array_merge($yet_singular);
        $one['yet_singular'] =  count($yet_singular);
        //未回放单数
        $not_singular = array_filter($res1, function($t) use ($data) { return $t['return_visit'] == 0; });
        $not_singular = array_merge($not_singular);
        $one['not_singular'] = count($not_singular);
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
        $pagesize = (int)isset($data['pagesize']) && $data['pagesize'] > 0 ? $data['pagesize'] : 20;
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
        $res = Pay_order_inside::select()->where("seas_status",0)->where("have_user_id",$user_id)->where(function($query) use ($data) {
            if(isset($data['project_id']) && !empty($data['project_id'])){
                $query->where('project_id',$data['project_id']);
            }
            if(isset($data['school_id']) && !empty($data['school_id'])){
                $query->where('school_id',$data['school_id']);
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
        })->get()->toArray();

        foreach($res as $k => &$v){
            //是否回访
            $a = Orderdocumentary::where("order_id",$v['id'])->first();
            if(empty($a)){
                $v['return_visit'] = 0;
            }else{
                $v['return_visit'] = 1;
            }
            //是否开课
            $c = StudentCourse::where(["order_no"=>$v['order_no'],"status"=>1])->first();
            if(empty($c)){
                $v['classes'] = 0;
            }else{
                $v['classes'] = 1;
            }

            $v['school_name'] = School::select("school_name")->where("id",$v['school_id'])->first()['school_name'];
            $v['project_name'] = Category::select("name")->where("id",$v['project_id'])->first()['name'];
            $v['subject_name'] = Category::select("name")->where("id",$v['subject_id'])->first()['name'];
            $v['course_name'] = Course::select("course_name")->where("id",$v['course_id'])->first()['course_name'];
            if($v['first_pay'] == 1){
                $v['first_pay_name'] = "全款";
            }else if($v['first_pay'] == 2){
                $v['first_pay_name'] = "定金";
            }else if($v['first_pay'] == 3){
                $v['first_pay_name'] = "部分尾款";
            }else{
                $v['first_pay_name'] = "最后一笔尾款";
            }

            if($v['confirm_order_type'] == 1){
                $v['confirm_order_type_name'] = "课程订单";
            }else if($v['confirm_order_type'] == 2){
                $v['confirm_order_type_name'] = "报名订单";
            }else{
                $v['confirm_order_type_name'] = "课程+报名订单";
            }
    }
        if(isset($data['return_visit']) && $data['return_visit'] != -1){
            $res = array_filter($res, function($t) use ($data) { return $t['return_visit'] == $data['return_visit']; });
            $res = array_merge($res);
            $count = count($res);
        }
        if(isset($data['classes']) && $data['classes'] != -1){
            $res = array_filter($res, function($t) use ($data) { return $t['classes'] == $data['classes']; });
            $res = array_merge($res);
            $count = count($res);
        }

        $total = $count;
        if($total > 0){
            $arr = array_merge($res);
            $start=($page-1)*$pagesize;
            $limit_s=$start+$pagesize;
            $list=[];
            for($i=$start;$i<$limit_s;$i++){
                if(!empty($arr[$i])){
                    array_push($list,$arr[$i]);
                }
            }
        }else{
            $list=[];
        }
        $page=[
            'pagesize'=>$pagesize,
            'page' =>$page,
            'total'=>$count
        ];
        if($list){
            return ['code' => 200, 'msg' => '查询成功', 'data' => $list,'page'=>$page,'one'=>$one];
        }else{
            return ['code' => 200, 'msg' => '查询暂无数据','data' => [],'page'=>$page,'one'=>[]];
        }

    }
    //学员总览 班主任自己的学员
    public static function getStudent($data){
        //查询条件   项目-学科  分校 开课状态  回访状态  手机号/姓名 订单类型 付款形式
        //获取登录id
        $admin = isset(AdminLog::getAdminInfo()->admin_user) ? AdminLog::getAdminInfo()->admin_user: [];
        $user_id  = $admin['id'];


        //每页显示的条数
        $pagesize = (int)isset($data['pagesize']) && $data['pagesize'] > 0 ? $data['pagesize'] : 20;
        $page     = isset($data['page']) && $data['page'] > 0 ? $data['page'] : 1;
        $offset   = ($page - 1) * $pagesize;

        //项目和学科
        if(!empty($data['project_id'])){
            $s_id = json_decode($data['project_id']);
            if(!empty($s_id[0])){
                $data['project_id'] = $s_id[0];
            }else{
                $data['project_id'] = 0;
            }
            if(!empty($s_id[1])){
                $data['subject_id'] = $s_id[1];
            }else{
                $data['subject_id'] = 0;
            }
        }
        //总管理
        if($user_id == 1){
            //计算总数
            $count = Pay_order_inside::where("seas_status",0)->where(function($query) use ($data) {
                if(isset($data['project_id']) && !empty($data['project_id'])){
                    $query->where('project_id',$data['project_id']);
                }
                if(isset($data['subject_id']) && !empty($data['subject_id'])){
                    $query->where('subject_id',$data['subject_id']);
                }
                if(isset($data['school_id']) && !empty($data['school_id'])){
                    $query->where('school_id',$data['school_id']);
                }
                if(isset($data['pay_type']) && $data['pay_type'] != -1){
                    $query->where('pay_type',$data['pay_type']);
                }
                if(isset($data['confirm_order_type']) && $data['confirm_order_type'] != -1){
                    $query->where('confirm_order_type',$data['confirm_order_type']);
                }
                if(isset($data['keyword']) && !empty(isset($data['keyword']))){
                    $query->where('name','like','%'.$data['keyword'].'%')->orWhere('mobile','like','%'.$data['keyword'].'%');
                }
            })->count();
            //分页数据
            $res = Pay_order_inside::where("seas_status",0)->where(function($query) use ($data) {
                if(isset($data['project_id']) && !empty($data['project_id'])){
                    $query->where('project_id',$data['project_id']);
                }
                if(isset($data['subject_id']) && !empty($data['subject_id'])){
                    $query->where('subject_id',$data['subject_id']);
                }
                if(isset($data['school_id']) && !empty($data['school_id'])){
                    $query->where('school_id',$data['school_id']);
                }
                if(isset($data['pay_type']) && $data['pay_type'] != -1){
                    $query->where('pay_type',$data['pay_type']);
                }
                if(isset($data['confirm_order_type']) && $data['confirm_order_type'] != -1){
                    $query->where('confirm_order_type',$data['confirm_order_type']);
                }
                if(isset($data['keyword']) && !empty(isset($data['keyword']))){
                    $query->where('name','like','%'.$data['keyword'].'%')->orWhere('mobile','like','%'.$data['keyword'].'%');
                }
            })->orderByDesc("id")->get()->toArray();
        }else{
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
                if(isset($data['pay_type']) && $data['pay_type'] != -1){
                    $query->where('pay_type',$data['pay_type']);
                }
                if(isset($data['confirm_order_type']) && $data['confirm_order_type'] != -1){
                    $query->where('confirm_order_type',$data['confirm_order_type']);
                }
                if(isset($data['keyword']) && !empty(isset($data['keyword']))){
                    $query->where('name','like','%'.$data['keyword'].'%')->orWhere('mobile','like','%'.$data['keyword'].'%');
                }
            })->count();
            //分页数据
            $res = Pay_order_inside::select()->where("seas_status",0)->where("have_user_id",$user_id)->where(function($query) use ($data) {
                if(isset($data['project_id']) && !empty($data['project_id'])){
                    $query->where('project_id',$data['project_id']);
                }
                if(isset($data['subject_id']) && !empty($data['subject_id'])){
                    $query->where('subject_id',$data['subject_id']);
                }
                if(isset($data['school_id']) && !empty($data['school_id'])){
                    $query->where('school_id',$data['school_id']);
                }
                if(isset($data['pay_type']) && $data['pay_type'] != -1){
                    $query->where('pay_type',$data['pay_type']);
                }
                if(isset($data['confirm_order_type']) && $data['confirm_order_type'] != -1){
                    $query->where('confirm_order_type',$data['confirm_order_type']);
                }
                if(isset($data['keyword']) && !empty(isset($data['keyword']))){
                    $query->where('name','like','%'.$data['keyword'].'%')->orWhere('mobile','like','%'.$data['keyword'].'%');
                }
            })->orderByDesc("id")->get()->toArray();
        }
        foreach($res as $k => &$v){

                //是否回访
                $a = Orderdocumentary::where("order_id",$v['id'])->first();
                if(empty($a)){
                    $v['return_visit'] = 0;
                }else{
                    $v['return_visit'] = 1;
                }
                //是否开课
                $c = StudentCourse::where(["order_no"=>$v['order_no'],"status"=>1])->first();
                if(empty($c)){
                    $v['classes'] = 0;
                }else{
                    $v['classes'] = 1;
                }
                $v['school_name'] = School::select("school_name")->where("id",$v['school_id'])->first()['school_name'];
                $v['project_name'] = Category::select("name")->where("id",$v['project_id'])->first()['name'];
                $v['subject_name'] = Category::select("name")->where("id",$v['subject_id'])->first()['name'];
                $v['course_name'] = Course::select("course_name")->where("id",$v['course_id'])->first()['course_name'];
                if($v['first_pay'] == 1){
                    $v['first_pay_name'] = "全款";
                }else if($v['first_pay'] == 2){
                    $v['first_pay_name'] = "定金";
                }else if($v['first_pay'] == 3){
                    $v['first_pay_name'] = "部分尾款";
                }else{
                    $v['first_pay_name'] = "最后一笔尾款";
                }

                if($v['confirm_order_type'] == 1){
                    $v['confirm_order_type_name'] = "课程订单";
                }else if($v['confirm_order_type'] == 2){
                    $v['confirm_order_type_name'] = "报名订单";
                }else{
                    $v['confirm_order_type_name'] = "课程+报名订单";
                }
        }
        if(isset($data['return_visit']) && $data['return_visit'] != -1){
            $res = array_filter($res, function($t) use ($data) { return $t['return_visit'] == $data['return_visit']; });
            $res = array_merge($res);
            $count = count($res);
        }
        if(isset($data['classes']) && $data['classes'] != -1){
            $res = array_filter($res, function($t) use ($data) { return $t['classes'] == $data['classes']; });
            $res = array_merge($res);
            $count = count($res);
        }
        $total = $count;
        if($total > 0){
            $arr = array_merge($res);
            $start=($page-1)*$pagesize;
            $limit_s=$start+$pagesize;
            $list=[];
            for($i=$start;$i<$limit_s;$i++){
                if(!empty($arr[$i])){
                    array_push($list,$arr[$i]);
                }
            }
        }else{
            $list=[];
        }
        $page=[
            'pagesize'=>$pagesize,
            'page' =>$page,
            'total'=>$count
        ];
        if($list){
            return ['code' => 200, 'msg' => '查询成功', 'data' => $list,'page'=>$page];
        }else{
            return ['code' => 200, 'msg' => '查询暂无数据', 'data' => [],'page'=>$page];
        }
    }
    //学员公海 被放入公海的所有学员订单
    public static function getStudentSeas($data){
    //查询条件   项目  分校 开课状态  回访状态  手机号/姓名 订单状态 付款形式
        //每页显示的条数
        $pagesize = (int)isset($data['pagesize']) && $data['pagesize'] > 0 ? $data['pagesize'] : 20;
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
            if(isset($data['pay_type']) && $data['pay_type'] != -1){
                $query->where('pay_type',$data['pay_type']);
            }
            if(isset($data['confirm_status']) && $data['confirm_status'] != -1){
                $query->where('confirm_status',$data['confirm_status']);
            }
            if(isset($data['keyword']) && !empty(isset($data['keyword']))){
                $query->where('name','like','%'.$data['keyword'].'%')->orWhere('mobile','like','%'.$data['keyword'].'%');
            }
        })->count();
        //分页数据
        $res = Pay_order_inside::select()->where("seas_status",1)->where(function($query) use ($data) {
            if(isset($data['project_id']) && !empty($data['project_id'])){
                $query->where('project_id',$data['project_id']);
            }
            if(isset($data['school_id']) && !empty($data['school_id'])){
                $query->where('school_id',$data['school_id']);
            }
            if(isset($data['pay_type']) && $data['pay_type'] != -1){
                $query->where('pay_type',$data['pay_type']);
            }
            if(isset($data['confirm_status']) && $data['confirm_status'] != -1){
                $query->where('confirm_status',$data['confirm_status']);
            }
            if(isset($data['keyword']) && !empty(isset($data['keyword']))){
                $query->where('name','like','%'.$data['keyword'].'%')->orWhere('mobile','like','%'.$data['keyword'].'%');
            }
        })->get()->toArray();
        foreach($res as $k => &$v){

            //是否回访
            $a = Orderdocumentary::where("order_id",$v['id'])->first();
            if(empty($a)){
                $v['return_visit'] = 0;
            }else{
                $v['return_visit'] = 1;
            }
            //是否开课
            $c = StudentCourse::where(["order_no"=>$v['order_no'],"status"=>1])->first();
            if(empty($c)){
                $v['classes'] = 0;
            }else{
                $v['classes'] = 1;
            }

            $v['school_name'] = School::select("school_name")->where("id",$v['school_id'])->first()['school_name'];
            $v['project_name'] = Category::select("name")->where("id",$v['project_id'])->first()['name'];
            $v['subject_name'] = Category::select("name")->where("id",$v['subject_id'])->first()['name'];
            $v['course_name'] = Course::select("course_name")->where("id",$v['course_id'])->first()['course_name'];
            if($v['first_pay'] == 1){
                $v['first_pay_name'] = "全款";
            }else if($v['first_pay'] == 2){
                $v['first_pay_name'] = "定金";
            }else if($v['first_pay'] == 3){
                $v['first_pay_name'] = "部分尾款";
            }else{
                $v['first_pay_name'] = "最后一笔尾款";
            }

            if($v['confirm_order_type'] == 1){
                $v['confirm_order_type_name'] = "课程订单";
            }else if($v['confirm_order_type'] == 2){
                $v['confirm_order_type_name'] = "报名订单";
            }else{
                $v['confirm_order_type_name'] = "课程+报名订单";
            }
    }

            if(isset($data['return_visit']) && $data['return_visit'] != -1){
                $res = array_filter($res, function($t) use ($data) { return $t['return_visit'] == $data['return_visit']; });
                $res = array_merge($res);
                $count = count($res);
            }
            if(isset($data['classes']) && $data['classes'] != -1){
                $res = array_filter($res, function($t) use ($data) { return $t['classes'] == $data['classes']; });
                $res = array_merge($res);
                $count = count($res);
            }
            $total = $count;
            if($total > 0){
                $arr = array_merge($res);
                $start=($page-1)*$pagesize;
                $limit_s=$start+$pagesize;
                $list=[];
                for($i=$start;$i<$limit_s;$i++){
                    if(!empty($arr[$i])){
                        array_push($list,$arr[$i]);
                    }
                }
            }else{
                $list=[];
            }
        $page=[
            'pagesize'=>$pagesize,
            'page' =>$page,
            'total'=>$count
        ];
        if($list){
            return ['code' => 200, 'msg' => '查询成功', 'data' => $list,'page'=>$page];
        }else{
            return ['code' => 200, 'msg' => '查询暂无数据', 'data' => [],'page'=>$page];
        }

    }
    public static function updateConsigneeStatsu($data){
        unset($data['/admin/updateConsigneeStatsu']);
        //consignee_statsu料收集状态 1收集中
        //initiator_name资料发起人姓名
        //获取登录id
        $admin = isset(AdminLog::getAdminInfo()->admin_user) ? AdminLog::getAdminInfo()->admin_user: [];
        $user_id  = $admin['id'];
        $update['initiator_name'] = $admin['username'];;
        $update['consignee_status'] = 1;
        $order = Pay_order_inside::where("id",$data['order_id'])->first();
        if(empty($order)){
            return ['code' => 202 , 'msg' => '订单记录不存在，请检查'];
        }
        $res = Pay_order_inside::where('id',$data['order_id'])->update($update);
        if($res){
            //查询是否存在
            $order = Pay_order_inside::where("id",$data['order_id'])->first();
            $student = self::where(["user_name"=>$order['name'],"mobile"=>$order['mobile']])->first();
            $student_information = DB::table("student_information")->where(["student_id"=>$student['id'],"school_id"=>$order['school_id'],"project_id"=>$order['project_id'],"subject_id"=>$order['subject_id'],"course_id"=>$order['course_id']])->first();
            if($student_information){
                return ['code' => 202 , 'msg' => '数据已存在'];
            }else{
                //创建关联数据
                $add['order_id'] = $data['order_id'];
                $add['student_id'] = $student['id'];
                $add['school_id'] = $order['school_id'];
                $add['project_id'] = $order['project_id'];
                $add['subject_id'] = $order['subject_id'];
                $add['course_id'] = $order['course_id'];
                $add['initiator_id'] = $admin['id'];
                $add['create_time'] = date("Y-m-d H:i:s");
                DB::table("student_information")->insert($add);
                return ['code' => 200 , 'msg' => '更改资料收集状态成功'];
            }
        }else{
            return ['code' => 202 , 'msg' => '更改资料收集状态失败'];
        }
    }
}
