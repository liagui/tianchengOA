<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refund_order extends Model
{
    //指定别的表名
    public $table = 'refund_order';
    //时间戳设置
    public $timestamps = false;
    /*
         * @param  添加退费订单
         * @param  student_name  学员姓名
         * @param  phone  学员手机号
         * @param  project_id  arr
         * @param  course_id   课程id
         * @param  refund_price   退费金额
         * @param  school_id   学校
         * @param  refund_reason   退费原因
         * @param  author  苏振文
         * @param  ctime   2020/9/9 10:45
         * return  array
         */
    public static function initOrder($data){
        if(!isset($data['student_name']) || empty($data['student_name'])){
            return ['code' => 201 , 'msg' => '未填写学员名'];
        }
        if(!isset($data['phone']) || empty($data['phone'])){
            return ['code' => 201 , 'msg' => '未填写学员手机号'];
        }
        if(!isset($data['project_id']) || empty($data['project_id'])){
            return ['code' => 201 , 'msg' => '未选择项目'];
        }
        if(!empty($data['project_id'])){
            $parent = json_decode($data['project_id'], true);
            $data['project_id'] = $parent[0];
            if(!empty($parent[1])){
                $data['subject_id'] = $parent[1];
            }
        }
        if(!isset($data['course_id']) || empty($data['course_id'])){
            return ['code' => 201 , 'msg' => '未选择课程'];
        }
        if(!isset($data['school_id']) || empty($data['school_id'])){
            return ['code' => 201 , 'msg' => '未选择学校'];
        }
        if(!isset($data['refund_price']) || empty($data['refund_price'])){
            return ['code' => 201 , 'msg' => '未填写退费金额'];
        }
        //根据学生名和手机号查询用户
        $student = Student::where(['user_name'=>$data['student_name'],'mobile'=>$data['phone']])->first();
        $res = [
            'student_id' => isset($student)?$student['id']:0,
            'student_name' => $data['student_name'],
            'phone' => $data['phone'],
            'refund_no' => 'TF'.date('YmdHis', time()) . rand(1111, 9999),
            'refund_Price' => $data['refund_price'],
            'school_id' => $data['school_id'],
            'confirm_status' => 0,
            'create_time' => date('Y-m-d H:i:s'),
            'refund_reason' => isset($data['refund_reason'])?$data['refund_reason']:'',
            'refund_plan' => 0,
            'teacher_id' =>isset(AdminLog::getAdminInfo()->admin_user->id)?AdminLog::getAdminInfo()->admin_user->id:0,
            'course_id' => $data['course_id'],
            'project_id' => $data['project_id'],
            'subject_id' => $data['subject_id'],
        ];
        $add = self::insert($res);
        if($add){
            return ['code' => 200 , 'msg' => '申请成功'];
        }else{
            return ['code' => 201 , 'msg' => '申请失败'];
        }
    }
    /*
         * @param 列表
         * @param  school_id     学校
         * @param  confirm_status   退费状态0未确认 1确认
         * @param  refund_plan   1未打款 2已打款
         * @param  state_time   开始时间
         * @param  end_time   结束时间
         * @param  order_on   订单号/手机号/姓名
         * @param  author  苏振文
         * @param  ctime   2020/9/9 14:48
         * return  array
         */
    public static function returnOrder($data){
        $admin = isset(AdminLog::getAdminInfo()->admin_user) ? AdminLog::getAdminInfo()->admin_user: [];
        //退费状态
        $where=[];
        if(!empty($data['confirm_status'])){
            $where['confirm_status'] = $data['pay_confirm_statusstatus'];
        }
        //打款状态
        if(!empty($data['refund_plan'])){
            $where['refund_plan'] = $data['refund_plan'];
        }
        //判断时间
        $begindata="2020-03-04";
        $enddate = date('Y-m-d');
        $statetime = !empty($data['state_time'])?$data['state_time']:$begindata;
        $endtime = !empty($data['end_time'])?$data['end_time']:$enddate;
        $state_time = $statetime." 00:00:00";
        $end_time = $endtime." 23:59:59";
        //每页显示的条数
        $pagesize = (int)isset($data['pagesize']) && $data['pagesize'] > 0 ? $data['pagesize'] : 20;
        $page     = isset($data['page']) && $data['page'] > 0 ? $data['page'] : 1;
        $offset   = ($page - 1) * $pagesize;

        //計算總數
        $count = self::where(function($query) use ($data,$admin) {
            if(isset($data['refund_no']) && !empty($data['order_no'])){
                $query->where('order_no',$data['order_on'])
                    ->orwhere('student_name',$data['order_on'])
                    ->orwhere('phone',$data['order_on']);
            }
            if($admin['school_id'] == 0){
                if(isset($data['school_id']) && $data['school_id'] != 0 && $data['school_id'] != '' ){
                    $query->where('school_id',$data['school_id']);
                }
            }else{
                $query->whereIn('school_id',explode(',',$admin['school_id']));
            }
        })
        ->where($where)
        ->whereBetween('create_time', [$state_time, $end_time])
        ->count();

        //計算總數
        $order = self::where(function($query) use ($data,$admin) {
            if(isset($data['refund_no']) && !empty($data['order_no'])){
                $query->where('order_no',$data['order_on'])
                    ->orwhere('student_name',$data['order_on'])
                    ->orwhere('phone',$data['order_on']);
            }
            if($admin['school_id'] == 0){
                if(isset($data['school_id']) && $data['school_id'] != 0 && $data['school_id'] != '' ){
                    $query->where('school_id',$data['school_id']);
                }
            }else{
                $query->whereIn('school_id',explode(',',$admin['school_id']));
            }
        })
        ->where($where)
        ->whereBetween('create_time', [$state_time, $end_time])
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
                if($v['confirm_status'] == 0){
                    $v['confirm_status_text'] = '未确认';
                }else{
                    $v['confirm_status_text'] = '已确认';
                }
                if($v['refund_plan'] == 0){
                    $v['refund_plan_text'] = '未确认';
                }else if($v['refund_plan'] == 1){
                    $v['refund_plan_text'] = '未打款';
                }else{
                    $v['refund_plan_text'] = '已打款';
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
        $where['state_time'] = $state_time;
        $where['end_time'] = $end_time;
        $page=[
            'pagesize'=>$pagesize,
            'page' =>$page,
            'total'=>$count
        ];
        return ['code' => 200 , 'msg' => '查询成功','data'=>$order,'where'=>$data,'page'=>$page];
    }

    /*
         * @param  单条详情
         * @param  id     参数
         * @param  author  苏振文
         * @param  ctime   2020/9/16 19:59
         * return  array
         */
    public static function returnOne($data){
        $res = self::where(['id'=>$data['id']])->first();
        //项目 学科  姓名
        $course = Course::select('course_name')->where(['id'=>$res['course_id']])->first();
        $res['course_name'] = $course['course_name'];
        $project = Project::select('name')->where(['id'=>$res['project_id']])->first();
        $res['project_name'] = $project['name'];
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
        //查学校
        $school = School::where(['id'=>$res['school_id']])->first();
        if($school){
            $res['school_name'] = $school['school_name'];
        }
        //根据用户名 手机号  项目 学科 课程 查询订单
        $order = Pay_order_inside::where([
            'name'=>$res['student_name'],
            'mobile'=>$res['phone'],
            'project_id'=>$res['project_id'],
            'subject_id'=>$res['subject_id'],
            'course_id' => $res['course_id']
        ])->get();
        return ['code' => 200, 'msg' => '获取成功','data'=>$res,'order'=>$order];
    }
    /*
         * @param  查看退款凭证
         * @param  $id    订单id
         * @param  author  苏振文
         * @param  ctime   2020/9/9 16:08
         * return  array
         */
    public static function seeOrder($data){
        $order = self::select('remit_name','refund_credentials','remit_time')->where(['id' => $data['id']])->first();
        if(!$order){
            return ['code' => 201 , 'msg' => '参数不对'];
        }
        if(!empty($order['refund_credentials'])){
            return ['code' => 200, 'msg' => '获取成功','data'=>$order];
        }else{
            return ['code' => 201, 'msg' => '未上传凭证'];
        }
    }
    /*
         * @param  修改退费状态
         * @param  id 订单id
         * @param  student_name 学生姓名
         * @param  phone 学生手机号
         * @param  project_id arr
         * @param  course_id 课程id
         * @param  order_ id 关联订单id  arr
         * @param  reality_price  实际退费金额
         * @param  school_id  学校id
         * @param  refund_reason  退费原因
         * @param  pay_credentials  arr 凭证
         * @param  author  苏振文
         * @param  ctime   2020/9/9 16:19
         * return  array
         */
    public static function amendOrder($data){
        $order = self::select('confirm_status','refund_plan')->where(['id' => $data['id']])->first();
        if(!$order){
            return ['code' => 201 , 'msg' => '参数不对'];
        }
        if($order['confirm_status'] == 1){
            return ['code' => 200, 'msg' => '修改成功'];
        }else{
            $parent = json_decode($data['project_id'], true);
            $up['project_id'] = $parent[0];
            if(!empty($parent[1])){
                $up['subject_id'] = $parent[1];
            }
            $up['course_id'] = $data['course_id'];
            $up['student_name'] = $data['student_name'];
            $up['phone'] = $data['phone'];
            $up['confirm_status'] = 1;
            $up['order_ id'] = implode(',',$data['order_id']);
            $up['reality_price'] = $data['reality_price'];
            $up['school_id'] = $data['school_id'];
            $up['refund_reason'] = $data['refund_reason'];
            $up['pay_credentials'] = implode(',',$data['pay_credentials']);
            $up['refund_time'] = date('Y-m-d H:i:s');
            if($order['refund_plan'] == 0){
                $up['refund_plan'] = 1;
            }
            $date = self::where(['id'=>$data['id']])->update($up);
            if($date){
                return ['code' => 200, 'msg' => '修改成功'];
            }else{
                return ['code' => 201, 'msg' => '修改失败'];
            }
        }
    }
    /*
         * @param  修改打款状态
         * @param  id  订单id
         * @param  remit_time  凭证时间
         * @param  refund_credentials  arr 退款凭证  多图
         * @param  remit_name  打款人
         * @param  author  苏振文
         * @param  ctime   2020/9/9 16:25
         * return  array
         */
    public static function remitOrder($data){
        $order = self::where(['id' => $data['id']])->first();
        if(!$order){
            return ['code' => 201 , 'msg' => '参数不对'];
        }
        $data['refund_plan'] = 2;
        if($order['confirm_status'] == 0){
            $data['confirm_status'] = 1;
        }
        $data['refund_credentials'] = implode(',',$data['refund_credentials']);
        unset($data['/admin/order/remitOrder']);
        $up = self::where(['id' => $data['id']])->update($data);
        if($up){
            return ['code' => 200, 'msg' => '上传成功'];
        }else{
            return ['code' => 201, 'msg' => '修改失败'];
        }
    }
}
