<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\MaterialListing;
use App\Models\TeacherSchool;
use App\Models\TeacherCategory;
use App\Models\Pay_order_inside;
use App\Models\Category;
use App\Models\School;
use Illuminate\Support\Facades\DB;

class Teacher extends Model {
    //指定别的表名
    public $table = 'admin';
    //时间戳设置
    public $timestamps = false;

    public static function getTeacherList($data){
        //每页显示的条数
        $pagesize = (int)isset($data['pagesize']) && $data['pagesize'] > 0 ? $data['pagesize'] : 20;
        $page     = isset($data['page']) && $data['page'] > 0 ? $data['page'] : 1;
        $offset   = ($page - 1) * $pagesize;
        $count = self::where('admin.role_id',3)->where(function($query) use ($data){
            if(isset($data['school_id']) && !empty(isset($data['school_id']))){
                $query->whereRaw("find_in_set({$data['school_id']},admin.school_id)");
            }
            if(isset($data['category_id']) && !empty(isset($data['category_id']))){
                $query->whereRaw("find_in_set({$data['category_id']},admin.category_id)");
            }
            if(isset($data['status']) && $data['status'] != -1){
                $query->where(['admin.status'=>$data['status']]);
            }
            if(isset($data['keyword']) && !empty(isset($data['keyword']))){
                $query->where('admin.real_name','like','%'.$data['keyword'].'%')->orWhere('admin.mobile','like','%'.$data['keyword'].'%')->orWhere('admin.wx','like','%'.$data['keyword'].'%');
            }
        })->where('is_del',1)->count();
        $data = self::where('admin.role_id',3)->where(function($query) use ($data){
            if(isset($data['school_id']) && !empty(isset($data['school_id']))){
                $query->whereRaw("find_in_set({$data['school_id']},admin.school_id)");
            }
            if(isset($data['category_id']) && !empty(isset($data['category_id']))){
                $query->whereRaw("find_in_set({$data['category_id']},admin.category_id)");
            }
            if(isset($data['status']) && $data['status'] != -1){
                $query->where(['admin.status'=>$data['status']]);
            }
            if(isset($data['keyword']) && !empty(isset($data['keyword']))){
                $query->where('admin.real_name','like','%'.$data['keyword'].'%')->orWhere('admin.mobile','like','%'.$data['keyword'].'%')->orWhere('admin.wx','like','%'.$data['keyword'].'%');
            }
        })->where('is_del',1)->offset($offset)->limit($pagesize)->get();
        foreach($data as $k=>&$v){
            $school = explode(",",$v['school_id']);
            $category = explode(",",$v['category_id']);
            //查询分校名称
            $v['school'] = DB::table("school")->select("school_name")->whereIn('id',$school)->get()->toArray();
            //查询项目名称
            $v['category'] = DB::table("category")->select("name")->whereIn('id',$category)->get()->toArray();

        }
        foreach($data as $k=>$vv){
            $school = explode(",",$vv['school_id']);
            $school_id = array_column(School::select("id")->where(["is_del"=>0,"is_open"=>0])->get()->toArray(),'id');
            $diff_school = array_diff($school_id, $school);

            $category = explode(",",$vv['category_id']);
            $category_id = array_column(Category::select("id")->where(["is_del"=>0,"is_hide"=>0,"parent_id"=>0])->get()->toArray(),'id');
            $diff_category = array_diff($category_id, $category);

            if($school[0] == 0 && !(count($diff_school) > 0)){
                $data[$k]['school'] = "全部分校";
            }else{
                //查询分校名称
                $data[$k]['school'] = implode(',',array_column($vv['school'] , 'school_name'));
            }
            if($category[0] == 0 && !(count($diff_category) > 0)){
                $data[$k]['category'] = "全部分校";
            }else{
                //查询项目名称
                $data[$k]['category'] = implode(',',array_column($vv['category'] , 'name'));
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
            return ['code' => 200, 'msg' => '查询暂无数据', 'data' => [],'page'=>$page];
        }
    }
    public static function getTeacherListAll($data){
        $data = self::select("id","username")->where('admin.role_id',3)->get();
        if($data){
            return ['code' => 200, 'msg' => '查询成功', 'data' => $data];
        }else{
            return ['code' => 200, 'msg' => '查询暂无数据','data' => []];
        }
    }
    public static function createTeacher($data){
        //创建班主任账号
        unset($data['/admin/createTeacher']);
        if($data['password'] != $data['verifypassword']){
            return ['code' => 202, 'msg' => '两次密码输入不一致'];
        }
        $teacher['username'] = $data['username'];
        //账户唯一验证
        $admin = self::where('username',$data['username'])->first();
        if($admin){
            return ['code' => 202 , 'msg' => '账户已存在'];
        }
        $teacher['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $teacher['create_time'] = date("Y-m-d H:i:s");
        $teacher['role_id'] = 3;
        $teacher['create_id'] = 1;
        $teacher['school_id'] = implode(',',json_decode($data['teacher_school']));
        if($teacher['school_id'][0] == 0){
            $school = School::select("id")->where(["is_open"=>0,"is_del"=>0])->get()->toArray();
            $school = array_column($school,'id');
            $teacher['school_id'] = "0,".implode(',',$school);
        }
        $teacher['category_id'] = implode(',',json_decode($data['teacher_category']));
        if($teacher['category_id'][0] == 0){
            $category = Category::select("id")->where(["is_hide"=>0,"is_del"=>0,"parent_id"=>0])->get()->toArray();
            $category = array_column($category,'id');
            $teacher['category_id'] = "0,".implode(',',$category);
        }
        $res = self::insert($teacher);
        if($res){
            return ['code' => 200 , 'msg' => '创建班主任成功'];
        }else{
            return ['code' => 202 , 'msg' => '创建班主任失败'];
        }
    }
    //获取班主任详情
    public static function GetTeacherOne($data){
        $teacher = self::select("id","real_name","school_id","category_id")->where(['id'=>$data['teacher_id'],"role_id"=>3])->first();
        if($teacher){
            return ['code' => 200, 'msg' => '查询成功', 'data' => $teacher];
        }else{
            return ['code' => 200, 'msg' => '查询暂无数据', 'data' => []];
        }
    }
    //更新班主任绑定分校
    public static function UpdateTeacherSchool($data){
        unset($data['/admin/UpdateTeacherSchool']);
        $teacher['school_id'] = implode(',',json_decode($data['teacher_school']));
        $teacher['updated_at'] = date("Y-m-d H:i:s");
        if($teacher['school_id'][0] == 0){
            $school = School::select("id")->where(["is_open"=>0,"is_del"=>0])->get()->toArray();
            $school = array_column($school,'id');
            $teacher['school_id'] = "0,".implode(',',$school);
        }
        $res = self::where("id",$data['teacher_id'])->update($teacher);
        if($res){
            return ['code' => 200 , 'msg' => '更新班主任绑定分校成功'];
        }else{
            return ['code' => 202 , 'msg' => '更新班主任绑定分校失败'];
        }
    }
    //更新班主任绑定项目
    public static function UpdateTeacherCategory($data){
        unset($data['/admin/UpdateTeacherCategory']);
        $teacher['category_id'] = implode(',',json_decode($data['teacher_category']));
        $teacher['updated_at'] = date("Y-m-d H:i:s");
        if($teacher['category_id'][0] == 0){
            $category = Category::select("id")->where(["is_hide"=>0,"is_del"=>0,"parent_id"=>0])->get()->toArray();
            $category = array_column($category,'id');
            $teacher['category_id'] = "0,".implode(',',$category);
        }
        $res = self::where("id",$data['teacher_id'])->update($teacher);
        if($res){
            return ['code' => 200 , 'msg' => '更新班主任绑定项目成功'];
        }else{
            return ['code' => 202 , 'msg' => '更新班主任绑定项目失败'];
        }
    }
    public static function updateTeacherStatus($data){
        unset($data['/admin/updateTeacherStatus']);
        $teacher = self::where(['id'=>$data['teacher_id'],'role_id'=>3])->first();
        $update['updated_at'] = date("Y-m-d H:i:s");
        if(empty($teacher)){
            return ['code' => 202 , 'msg' => '请检查账号是否存在'];
        }
        if($teacher['status'] == 1){
            $update['status'] = 0;
        }else{
            $update['status'] = 1;
        }
        $res = self::where('id',$data['teacher_id'])->update($update);
        if($res){
            return ['code' => 200 , 'msg' => '更新值班状态成功'];
        }else{
            return ['code' => 202 , 'msg' => '更新值班状态失败'];
        }
    }

    public static function updateTeacherSeasStatus($data){
        unset($data['/admin/updateTeacherSeasStatus']);
        $teacher = self::where(['id'=>$data['teacher_id'],'role_id'=>3])->first();
        if(empty($teacher)){
            return ['code' => 202 , 'msg' => '请检查账号是否存在'];
        }
        if($teacher['dimission'] == 1){
            $update['dimission'] = 0;
        }else{
            $update['dimission'] = 1;
        }
        $update['is_forbid'] = 0;
        $update['updated_at'] = date("Y-m-d H:i:s");
        $res = self::where('id',$data['teacher_id'])->update($update);
        if($res){
            //更新所有订单的状态为放入放入公海
            $update = Pay_order_inside::where(['have_user_id'=>$data['teacher_id']])->update(['seas_status'=>1,"seas_time"=>date("Y-m-d H:i:s")]);
            return ['code' => 200 , 'msg' => '更新离职状态成功'];
        }else{
            return ['code' => 202 , 'msg' => '更新离职状态失败'];
        }
    }


    //status  1值班中2休息中3已离职
    public static function getTeacherPerformance($data){
        //每页显示的条数
        $pagesize = (int)isset($data['pagesize']) && $data['pagesize'] > 0 ? $data['pagesize'] : 20;
        $page     = isset($data['page']) && $data['page'] > 0 ? $data['page'] : 1;
        $offset   = ($page - 1) * $pagesize;
        //查询所有班主任
        //搜索条件  时间区间   开始时间和结束时间
        //对比订单  comfirm_time 是否在这个时间区间内
        $where['is_del'] = 1;
        if(isset($data['status']) && !empty($data['status'])){
            if($data['status'] == 1){
                $where['dimission'] = 0;
                $where['status'] = 1;
            }elseif ($data['status'] == 2){
                $where['dimission'] = 0;
                $where['status'] = 0;
            }elseif ($data['status'] == 3){
                $where['dimission'] = 1;
            }
        }
        $count = self::where('role_id',3)->count();
        $teacher = self::select("real_name","mobile","wx","id","dimission","status as teacherstatus")->where($where)->where('role_id',3)->offset($offset)->limit($pagesize)->get()->toArray();
        foreach($teacher as $k =>&$v){
            if($v['dimission'] == 0){
                if($v['teacherstatus'] == 1){
                    $v['status'] = 1; //值班中
                }else{
                    $v['status'] = 2; //休息中
                }
            }else{
                $v['status'] = 3;  //已离职
            }
            $res1 = Pay_order_inside::select()->where("seas_status",0)->where("have_user_id",$v['id'])
            ->where(function($query) use ($data){
                if(isset($data['start_time']) && !empty(isset($data['start_time']))  && isset($data['end_time']) && !empty(isset($data['end_time']))){
                    $query->whereBetween('comfirm_time',[date("Y-m-d H:i:s",strtotime($data['start_time'])),date("Y-m-d H:i:s",strtotime($data['end_time']))]);
                }
            })
            ->get()->toArray();
            foreach($res1 as $kk => &$vv){
                //是否回访
                $a = Orderdocumentary::where("order_id",$v['id'])->first();
                if(empty($a)){
                    $vv['return_visit'] = 0;
                }else{
                    $vv['return_visit'] = 1;
                }
            }
            //已回访单数
            $yet_singular = array_filter($res1, function($t) use ($data) { return $t['return_visit'] == 1; });
            $yet_singular = array_merge($yet_singular);
            $v['yet_singular'] =  count($yet_singular);
            //未回放单数
            $not_singular = array_filter($res1, function($t) use ($data) { return $t['return_visit'] == 0; });
            $not_singular = array_merge($not_singular);
            $v['not_singular'] = count($not_singular);

            //总回放单数
            $v['sum_singular'] = $v['yet_singular'] + $v['not_singular'];


            //已完成业绩
            $v['completed_performance'] = Pay_order_inside::select("course_Price")->where(['have_user_id'=>$v['id']])
            ->where(function($query) use ($data){
                if(isset($data['start_time']) && !empty(isset($data['start_time']))  && isset($data['end_time']) && !empty(isset($data['end_time']))){
                    $query->whereBetween('comfirm_time',[date("Y-m-d H:i:s",strtotime($data['start_time'])),date("Y-m-d H:i:s",strtotime($data['end_time']))]);
                }
            })->sum("course_Price");

            //退费业绩
            $v['return_premium'] = DB::table("refund_order")->where(["teacher_id"=>$v['id'],"refund_plan"=>2,"confirm_status"=>1])->where(function($query) use ($data){
                if(isset($data['start_time']) && !empty(isset($data['start_time']))  && isset($data['end_time']) && !empty(isset($data['end_time']))){
                    $query->whereBetween('refund_time',[date("Y-m-d H:i:s",strtotime($data['start_time'])),date("Y-m-d H:i:s",strtotime($data['end_time']))]);
                }
            })->sum("refund_Price");
        }
            $one = [
                //总退费金额
               'return_premium' => number_format(array_sum(array_column($teacher , 'return_premium')) , 2),
               //已完成业绩
               'completed_performance' => number_format(array_sum(array_column($teacher , 'completed_performance')) , 2),
               //总回放单数
               'sum_singular' => array_sum(array_column($teacher , 'sum_singular')),
               //已回访单数
               'yet_singular' => array_sum(array_column($teacher , 'yet_singular')),
               //未回访单数
               'not_singular' => array_sum(array_column($teacher , 'not_singular')),
            ];

        $page=[
            'pageSize'=>$pagesize,
            'page' =>$page,
            'total'=>$count
        ];
        if($teacher){
            return ['code' => 200, 'msg' => '查询成功', 'data' => $teacher,'page'=>$page,'one'=>$one];
        }else{
            return ['code' => 200, 'msg' => '查询暂无数据', 'data' => [],'page'=>$page,'one'=>[]];
        }
    }
    public static function getTeacherPerformanceOne($data){
        //搜索条件 项目 所属分校 开课状态 回访状态 手机号/姓名/微信号
        $pagesize = (int)isset($data['pagesize']) && $data['pagesize'] > 0 ? $data['pagesize'] : 20;
        $page     = isset($data['page']) && $data['page'] > 0 ? $data['page'] : 1;
        $offset   = ($page - 1) * $pagesize;
        //班主任姓名
        $one = self::select("real_name")->where("id",$data['teacher_id'])->first()->toArray();
        //退费金额
        $one['return_premium'] = DB::table("refund_order")->where(["teacher_id"=>$data['teacher_id'],"refund_plan"=>2,"confirm_status"=>1])->sum("refund_Price");

        $res1 = Pay_order_inside::select()->where("seas_status",0)->where("have_user_id",$data['teacher_id'])
        ->where(function($query) use ($data){
            if(isset($data['start_time']) && !empty(isset($data['start_time']))  && isset($data['end_time']) && !empty(isset($data['end_time']))){
                $query->whereBetween('comfirm_time',[date("Y-m-d H:i:s",strtotime($data['start_time'])),date("Y-m-d H:i:s",strtotime($data['end_time']))]);
            }
        })
        ->get()->toArray();

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
        $one['completed_performance'] = Pay_order_inside::select("course_Price")->where(['have_user_id'=>$data['teacher_id']])
        ->where(function($query) use ($data){
            if(isset($data['start_time']) && !empty(isset($data['start_time']))  && isset($data['end_time']) && !empty(isset($data['end_time']))){
                $query->whereBetween('comfirm_time',[date("Y-m-d H:i:s",strtotime($data['start_time'])),date("Y-m-d H:i:s",strtotime($data['end_time']))]);
            }
        })->sum("course_Price");

        //查询详细数据
        $count = Pay_order_inside::where(['have_user_id'=>$data['teacher_id'],'seas_status'=>0])->where(function($query) use ($data){
            if(isset($data['school_id']) && !empty(isset($data['school_id']))){
                $query->where('school_id','like','%'.$data['school_id'].'%');
            }
            if(isset($data['project_id']) && $data['project_id'] != -1){
                $query->where(['project_id'=>$data['project_id']]);
            }
            if(isset($data['keyword']) && !empty(isset($data['keyword']))){
                $query->where('name','like','%'.$data['keyword'].'%')->orWhere('mobile','like','%'.$data['keyword'].'%');
            }
            if(isset($data['start_time']) && !empty(isset($data['start_time']))  && isset($data['end_time']) && !empty(isset($data['end_time']))){
                $query->whereBetween('comfirm_time',[date("Y-m-d H:i:s",strtotime($data['start_time'])),date("Y-m-d H:i:s",strtotime($data['end_time']))]);
            }
        })->count();
        $res = Pay_order_inside::where(['have_user_id'=>$data['teacher_id'],'seas_status'=>0])->where(function($query) use ($data){
            if(isset($data['school_id']) && !empty(isset($data['school_id']))){
                $query->where('school_id','like','%'.$data['school_id'].'%');
            }
            if(isset($data['project_id']) && $data['project_id'] != -1){
                $query->where(['project_id'=>$data['project_id']]);
            }
            if(isset($data['keyword']) && !empty(isset($data['keyword']))){
                $query->where('name','like','%'.$data['keyword'].'%')->orWhere('mobile','like','%'.$data['keyword'].'%');
            }
            if(isset($data['start_time']) && !empty(isset($data['start_time']))  && isset($data['end_time']) && !empty(isset($data['end_time']))){
                $query->whereBetween('comfirm_time',[date("Y-m-d H:i:s",strtotime($data['start_time'])),date("Y-m-d H:i:s",strtotime($data['end_time']))]);
            }
        })->get()->toArray();

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

            $school_name = School::select("school_name")->where('id',$v['school_id'])->first();
            if(!empty($school_name)){
            $v['school_name'] = $school_name['school_name'];
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
            'pageSize'=>$pagesize,
            'page' =>$page,
            'total'=>$count
        ];
        if($list){
            return ['code' => 200, 'msg' => '查询成功', 'data' => $list,'one'=>$one,'page'=>$page];
        }else{
            return ['code' => 200, 'msg' => '查询暂无数据', 'data' => [],'one'=>0,'page'=>$page];
        }

    }



}
