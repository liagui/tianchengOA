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
         * @param  bank_name   银行名
         * @param  openbank_name   开户行名称
         * @param bank_card   卡号
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
        if(isset($data['pay_credentials']) && !empty($data['pay_credentials'])){
            $credentials = json_decode($data['pay_credentials'],true);
            $credentialss = implode(',',$credentials);
        }else{
            $credentialss='';
        }
        //根据学生名和手机号查询用户
        $student = Student::where(['user_name'=>$data['student_name'],'mobile'=>$data['phone']])->first();
        $res = [
            'student_id' => isset($student)?$student['id']:0,
            'student_name' => $data['student_name'],
            'phone' => $data['phone'],
            'refund_no' => 'TF'.date('YmdHis', time()) . rand(1111, 9999),
            'refund_Price' => isset($data['refund_price'])?$data['refund_price']:0,
            'sing_price' => isset($data['sing_price'])?$data['sing_price']:0,
            'school_id' => $data['school_id'],
            'confirm_status' => 0,
            'create_time' => date('Y-m-d H:i:s'),
            'refund_reason' => isset($data['refund_reason'])?$data['refund_reason']:'',
            'refund_plan' => 0,
            'course_id' => $data['course_id'],
            'project_id' => $data['project_id'],
            'subject_id' => $data['subject_id'],
            'pay_credentials' => $credentialss,
            'remit_time' => date('Y-m-d H:i:s'),
            'bank_name' => $data['bank_name'],
            'openbank_name' => $data['openbank_name'],
            'bank_card' => $data['bank_card']
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
         * @param  refund_plan   1未打款 2已打款 3 已驳回
         * @param  state_time   开始时间
         * @param  end_time   结束时间
         * @param  order_on   订单号/手机号/姓名
         * @param  return_state_time   开始时间
         * @param  return_end_time   结束时间
         * return  array
         * @param  author  苏振文
         * @param  ctime   2020/9/9 14:48
         */
    public static function returnOrder($data,$schoolarr){
        //退费状态
        $where=[];
        if(isset($data['confirm_status'])){
            $where['confirm_status'] = $data['confirm_status'];
        }
        //打款状态
        if(isset($data['refund_plan'])){
            $where['refund_plan'] = $data['refund_plan'];
        }
        //学校id
        $school_id=[];
        if(isset($data['school_name'])){
            $school_id = School::select('id')->where('school_name','like','%'.$data['school_name'].'%')->where('is_del',0)->get();
        }
        //判断时间
        $begindata="2020-03-04";
        $enddate = date('Y-m-d');
        $statetime = !empty($data['start_time'])?$data['start_time']:$begindata;
        $endtime = !empty($data['end_time'])?$data['end_time']:$enddate;
        $state_time = $statetime." 00:00:00";
        $end_time = $endtime." 23:59:59";
        //判断确认时间是否为空
        $begindata="2020-03-04";
        $enddate = date('Y-m-d');
        $returnstatetime = !empty($data['return_state_time'])?$data['return_state_time']:$begindata;
        $returnendtime = !empty($data['return_end_time'])?$data['return_end_time']:$enddate;
        $return_state_time = $returnstatetime." 00:00:00";
        $return_end_time = $returnendtime." 23:59:59";
        //每页显示的条数
        $pagesize = (int)isset($data['pagesize']) && $data['pagesize'] > 0 ? $data['pagesize'] : 20;
        $page     = isset($data['page']) && $data['page'] > 0 ? $data['page'] : 1;
        $offset   = ($page - 1) * $pagesize;

        //科目id&学科id
        if(!empty($data['project_id'])){
            $parent = json_decode($data['project_id'], true);
            if(!empty($parent[0])){
                $where['project_id'] = $parent[0];
                if(!empty($parent[1])){
                    $where['subject_id'] = $parent[1];
                }
            }
        }
        if(isset($data['course_id'])){
            $where['course_id'] = $data['course_id'];
        }
        //計算總數
        $count = self::where($where)->where(function($query) use ($data,$schoolarr,$school_id) {
            if(isset($data['confirm_order_type'])){
                $query->where('confirm_order_type',$data['confirm_order_type']);
            }
            if(isset($data['order_on']) && !empty($data['order_on'])){
                $query->where('refund_no','like','%'.$data['order_on'].'%')
                    ->orwhere('student_name','like','%'.$data['order_on'].'%')
                    ->orwhere('phone','like','%'.$data['order_on'].'%');
            }
            if(!empty($school_id)){
                $query->whereIn('school_id',$school_id);
            }
            $query->whereIn('school_id',$schoolarr);
        })
        ->whereBetween('create_time', [$state_time, $end_time])
//        ->whereBetween('refund_time', [$return_state_time, $return_end_time])
        ->count();

        //列表
        $order = self::where($where)->where(function($query) use ($data,$schoolarr,$school_id) {
            if(isset($data['confirm_order_type'])){
                $query->where('confirm_order_type',$data['confirm_order_type']);
            }
            if(isset($data['order_on']) && !empty($data['order_on'])){
                $query->where('refund_no','like','%'.$data['order_on'].'%')
                    ->orwhere('student_name','like','%'.$data['order_on'].'%')
                    ->orwhere('phone','like','%'.$data['order_on'].'%');
            }
            if(!empty($school_id)){
                $query->whereIn('school_id',$school_id);
            }
            $query->whereIn('school_id',$schoolarr);
        })
        ->whereBetween('create_time', [$state_time, $end_time])
//        ->whereBetween('refund_time', [$return_state_time, $return_end_time])
        ->orderByDesc('id')
        ->offset($offset)->limit($pagesize)->get()->toArray();
        //循环查询分类
        if(!empty($order)){
            foreach ($order as $k=>&$v){
                $v['pay_voucher'] = !empty($v['pay_voucher'])?explode(",",$v['pay_voucher']):'';
                $tui = explode(',',$v['refund_credentials']);
                $v['refund_credentials'] = $tui;
                $zhifu = explode(',',$v['pay_credentials']);
                $v['pay_credentials'] = $zhifu;
                //查学校
                $school = School::where(['id'=>$v['school_id']])->first();
                if($school){
                    $v['school_name'] = $school['school_name'];
                }
                if($v['confirm_status'] == 0){
                    $v['confirm_status_text'] = '未确认';
                }else if($v['confirm_status'] == 1){
                    $v['confirm_status_text'] = '已确认';
                }else if($v['confirm_status'] == 2){
                    $v['confirm_status_text'] = '被驳回';
                }else if($v['confirm_status'] == 3){
                    $v['confirm_status_text'] = '待财务确认';
                }
                if($v['refund_plan'] == 0){
                    $v['refund_plan_text'] = '未确认';
                }else if($v['refund_plan'] == 1){
                    $v['refund_plan_text'] = '未打款';
                }else if($v['refund_plan'] == 2){
                    $v['refund_plan_text'] = '已打款';
                }else if($v['refund_plan'] == 3){
                    $v['refund_plan_text'] = '被驳回';
                }
                if($v['confirm_status'] == 0){
                    $v['finance_text'] = '待退费员确认';
                }else if($v['confirm_status'] == 1){
                    $v['finance_text'] = '已确认';
                }else if($v['confirm_status'] == 2){
                    $v['finance_text'] = '被驳回';
                }else if($v['confirm_status'] == 3){
                    $v['finance_text'] = '待确认';
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
        unset($where['confirm_status']);
        unset($where['refund_plan']);
        //退费总金额
        $tuicount = self::where($where)->where(function($query) use ($data,$schoolarr,$school_id) {
            if(isset($data['confirm_order_type'])){
                $query->where('confirm_order_type',$data['confirm_order_type']);
            }
            if(isset($data['order_on']) && !empty($data['order_on'])){
                $query->where('refund_no','like','%'.$data['order_on'].'%')
                    ->orwhere('student_name','like','%'.$data['order_on'].'%')
                    ->orwhere('phone','like','%'.$data['order_on'].'%');
            }
            if(!empty($school_id)){
                $query->whereIn('school_id',$school_id);
            }
            $query->whereIn('school_id',$schoolarr);
        })->whereBetween('create_time', [$state_time, $end_time])->sum('refund_Price');
        //未确认金额   confirm_status 0
        $weicount = self::where($where)->where(function($query) use ($data,$schoolarr,$school_id) {
            if(isset($data['confirm_order_type'])){
                $query->where('confirm_order_type',$data['confirm_order_type']);
            }
            if(isset($data['order_on']) && !empty($data['order_on'])){
                $query->where('refund_no','like','%'.$data['order_on'].'%')
                    ->orwhere('student_name','like','%'.$data['order_on'].'%')
                    ->orwhere('phone','like','%'.$data['order_on'].'%');
            }
            if(!empty($school_id)){
                $query->whereIn('school_id',$school_id);
            }
            $query->whereIn('school_id',$schoolarr);
        })->where(['confirm_status'=>1,'refund_plan'=>1])->whereBetween('create_time', [$state_time, $end_time])->sum('reality_price');
        $weisingcount = self::where($where)->where(function($query) use ($data,$schoolarr,$school_id) {
            if(isset($data['confirm_order_type'])){
                $query->where('confirm_order_type',$data['confirm_order_type']);
            }
            if(isset($data['order_on']) && !empty($data['order_on'])){
                $query->where('refund_no','like','%'.$data['order_on'].'%')
                    ->orwhere('student_name','like','%'.$data['order_on'].'%')
                    ->orwhere('phone','like','%'.$data['order_on'].'%');
            }
            if(!empty($school_id)){
                $query->whereIn('school_id',$school_id);
            }
            $query->whereIn('school_id',$schoolarr);
        })->where(['confirm_status'=>1,'refund_plan'=>1])->whereBetween('create_time', [$state_time, $end_time])->sum('reality_sing_price');
        //已确认金额   confirm_status 1
        $surecount = self::where($where)->where(function($query) use ($data,$schoolarr,$school_id) {
            if(isset($data['confirm_order_type'])){
                $query->where('confirm_order_type',$data['confirm_order_type']);
            }
            if(isset($data['order_on']) && !empty($data['order_on'])){
                $query->where('refund_no','like','%'.$data['order_on'].'%')
                    ->orwhere('student_name','like','%'.$data['order_on'].'%')
                    ->orwhere('phone','like','%'.$data['order_on'].'%');
            }
            if(!empty($school_id)){
                $query->whereIn('school_id',$school_id);
            }
            $query->whereIn('school_id',$schoolarr);
        })->where(['confirm_status'=>1])->whereBetween('create_time', [$state_time, $end_time])->sum('refund_Price');
        //已退金额   refund_plan = 2
        $yituicount = self::where($where)->where(function($query) use ($data,$schoolarr,$school_id) {
            // if(isset($data['confirm_order_type'])){
            //     $query->where('confirm_order_type',$data['confirm_order_type']);
            // }
            if(isset($data['order_on']) && !empty($data['order_on'])){
                $query->where('refund_no','like','%'.$data['order_on'].'%')
                    ->orwhere('student_name','like','%'.$data['order_on'].'%')
                    ->orwhere('phone','like','%'.$data['order_on'].'%');
            }
            if(!empty($school_id)){
                $query->whereIn('school_id',$school_id);
            }
            $query->whereIn('school_id',$schoolarr);
        })->where(['refund_plan'=>2])->whereBetween('create_time', [$state_time, $end_time])->sum('reality_price');
        //未处理条数
        $weisum = self::where($where)->where(function($query) use ($data,$schoolarr,$school_id) {
            if(isset($data['confirm_order_type'])){
                $query->where('confirm_order_type',$data['confirm_order_type']);
            }
            if(isset($data['order_on']) && !empty($data['order_on'])){
                $query->where('refund_no','like','%'.$data['order_on'].'%')
                    ->orwhere('student_name','like','%'.$data['order_on'].'%')
                    ->orwhere('phone','like','%'.$data['order_on'].'%');
            }
            if(!empty($school_id)){
                $query->whereIn('school_id',$school_id);
            }
            $query->whereIn('school_id',$schoolarr);
        })->where(['confirm_status'=>0])->whereBetween('create_time', [$state_time, $end_time])->count();
        //未打款条数
        $weidksum = self::where($where)->where(function($query) use ($data,$schoolarr,$school_id) {
            if(isset($data['confirm_order_type'])){
                $query->where('confirm_order_type',$data['confirm_order_type']);
            }
            if(isset($data['order_on']) && !empty($data['order_on'])){
                $query->where('refund_no','like','%'.$data['order_on'].'%')
                    ->orwhere('student_name','like','%'.$data['order_on'].'%')
                    ->orwhere('phone','like','%'.$data['order_on'].'%');
            }
            if(!empty($school_id)){
                $query->whereIn('school_id',$school_id);
            }
            $query->whereIn('school_id',$schoolarr);
        })->where(['confirm_status'=>1,'refund_plan'=>1])->whereBetween('create_time', [$state_time, $end_time])->count();
        $count=[
            'tuicount' => $tuicount,
            'weicount' => $weicount + $weisingcount,
            'surecoun' => $surecount,
            'yituicount' => $yituicount,
            'weisum' => $weisum,
            'weidksum' => $weidksum,
        ];
        return ['code' => 200 , 'msg' => '查询成功','data'=>$order,'page'=>$page,'count'=>$count];
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
        $arrproject = [$res['project_id'],$res['subject_id']];
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
        if(!empty($res['pay_credentials'])){
            $res['pay_credentials'] = explode(',',$res['pay_credentials']);
        }
        //查学校
        $school = School::where(['id'=>$res['school_id']])->first();
        if($school){
            $res['school_name'] = $school['school_name'];
        }
        //根据用户名 手机号  项目 学科 课程 查询订单
        if(empty($res['order_id'])) {
            $orderid=[];
            $order = Pay_order_inside::where([
                'name' => $res['student_name'],
                'mobile' => $res['phone'],
                'project_id' => $res['project_id'],
                'subject_id' => $res['subject_id'],
                'course_id' => $res['course_id'],
            ])->get();
            if (!empty($order)) {
                foreach ($order as $k => &$v) {
                    array_push($orderid, $v['id']);
                    $v['select'] = true;
                    $school = School::where(['id' => $v['school_id']])->first();
                    $v['school_name'] = $school['school_name'];
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
        }else{
            $orderid = explode($res['order_id'],true);
            foreach ($orderid as $k=>$v){
                $orderone = Pay_order_inside::where(['id'=>$v])->first();
                //course  课程
                $course = Course::select('course_name')->where(['id'=>$orderone['course_id']])->first();
                $orderone['course_name'] = $course['course_name'];
                //Project  项目
                $project = Project::select('name')->where(['id'=>$orderone['project_id']])->first();
                $orderone['project_name'] = $project['name'];
                //Subject  学科
                $subject = Project::select('name')->where(['id'=>$orderone['subject_id']])->first();
                $orderone['subject_name'] = $subject['name'];
                if(!empty($v['education_id']) && $v['education_id'] != 0){
                    //查院校
                    $education = Education::select('education_name')->where(['id'=>$orderone['education_id']])->first();
                    $orderone['education_name'] = $education['education_name'];
                    //查专业
                    $major = Major::where(['id'=>$orderone['major_id']])->first();
                    $orderone['major_name'] = $major['major_name'];
                }
                $order[] = $orderone;
            }

        }
        unset($res['project_id']);
        unset($res['subject_id']);
        $res['project_id'] = $arrproject;
        return ['code' => 200, 'msg' => '获取成功','data'=>$res,'order'=>$order,'order_id'=>$orderid];
    }
    /*
         * @param  根据where查关联订单
         * @param  用户名 手机号  项目 学科 课程
         * @param  author  苏振文
         * @param  ctime   2020/9/17 15:45
         * return  array
         */
    public static function returnWhereOne($data){
    //根据用户名 手机号  项目 学科 课程 查询订单
        if(!empty($data['project_id'])){
            $parent = json_decode($data['project_id'], true);
            $data['project_id'] = $parent[0];
            if(!empty($parent[1])){
                $data['subject_id'] = $parent[1];
            }
        }
        $order = Pay_order_inside::where([
            'name'=>$data['student_name'],
            'mobile'=>$data['phone'],
            'project_id'=>$data['project_id'],
            'subject_id'=>$data['subject_id'],
            'course_id' => $data['course_id'],
            'del_flag' => 0
        ])->get();
        return ['code' => 200, 'msg' => '获取成功','data'=>$order];
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
        $tui = explode(',',$order['refund_credentials']);
        $order['refund_credentials'] = $tui;
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
         * @param  status  1通过2驳回
         * @param  author  苏振文
         * @param  ctime   2020/9/9 16:19
         * return  array
         */
    public static function amendOrder($data){
        $admin = isset(AdminLog::getAdminInfo()->admin_user) ? AdminLog::getAdminInfo()->admin_user : [];
        $order = self::select('confirm_status','refund_plan')->where(['id' => $data['id']])->first();
        if(!$order){
            return ['code' => 201 , 'msg' => '参数不对'];
        }
        if($order['confirm_status'] == 2){
            return ['code' => 200, 'msg' => '修改成功'];
        }else{
            if($data['status'] == 0 || empty($data['status'])){
                if(isset($data['pay_credentials']) && !empty($data['pay_credentials'])) {
                    $credentials = json_decode($data['pay_credentials'],true);
                    $credentialss = implode(',',$credentials);
                    $up['pay_credentials'] = $credentialss;
                    $up['remit_time'] = date('Y-m-d H:i:s');
                }
                $up['refund_reason'] = $data['refund_reason'];
                $up['reality_price'] = $data['reality_price'];
                $date = self::where(['id'=>$data['id']])->update($up);
                return ['code' => 200, 'msg' => '修改成功'];
            }
            //判断是通过还是驳回
            if($data['status'] == 1){
                $parent = json_decode($data['project_id'], true);
                $up['project_id'] = $parent[0];
                if(!empty($parent[1])){
                    $up['subject_id'] = $parent[1];
                }
                if(isset($data['order_id'])){
                    $orderid = json_decode($data['order_id'],true);
                    foreach ($orderid as $k=>$v){
                        $orderdetail = Pay_order_inside::where(['id'=>$v['id']])->first();
                        if($orderdetail['first_pay'] == 1 || $orderdetail['first_pay'] == 2){
                            $up['teacher_id'] = $orderdetail['have_user_id'];
                        }
                    }
                }else{
                    $orderid = [];
                    if(!isset($data['pay_credentials']) || empty($data['pay_credentials'])) {
                        return ['code' => 201, 'msg' => '请上传支付凭证'];
                    }else{
                        $credentials = json_decode($data['pay_credentials'],true);
                        $credentialss = implode(',',$credentials);
                        $up['pay_credentials'] = $credentialss;
                        $up['remit_time'] = date('Y-m-d H:i:s');
                    }
                }
                $up['course_id'] = $data['course_id'];
                $up['student_name'] = $data['student_name'];
                $up['phone'] = $data['phone'];
                $up['confirm_status'] = 3;
                $up['order_id'] = implode(',',$orderid);
                $up['reality_price'] = $data['reality_price'];
                $up['reality_sing_price'] = $data['reality_sing_price'];
                $up['school_id'] = $data['school_id'];
                $up['refund_reason'] = $data['refund_reason'];
                $up['refund_time'] = date('Y-m-d H:i:s');
                $up['confirm_user_id'] = $admin['id'];
                if($order['refund_plan'] == 0){
                    $up['refund_plan'] = 1;
                }
                $date = self::where(['id'=>$data['id']])->update($up);
                if($date){
                    return ['code' => 200, 'msg' => '修改成功'];
                }else{
                    return ['code' => 201, 'msg' => '修改失败'];
                }
            }else{
                if(!isset($data['refund_cause']) || empty($data['refund_cause'])){
                    return ['code' => 201, 'msg' => '请填写驳回原因'];
                }
                $up['confirm_status'] = 2;
                $up['refund_plan'] = 0;
                $up['refund_cause'] = $data['refund_cause'];
                $up['refund_time'] = date('Y-m-d H:i:s');
                $up['confirm_user_id'] = $admin['id'];
                $date = self::where(['id'=>$data['id']])->update($up);
                if($date){
                    return ['code' => 200, 'msg' => '操作成功'];
                }else{
                    return ['code' => 201, 'msg' => '操作失败'];
                }
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
        if($order['confirm_status'] != 1){
            return ['code' => 201 , 'msg' => '财务未审核'];
        }
        if(!$order){
            return ['code' => 201 , 'msg' => '参数不对'];
        }
        $data['refund_plan'] = 2;
        if($order['confirm_status'] == 0){
            $data['confirm_status'] = 1;
        }
        if(isset($data['refund_credentials']) && !empty($data['refund_credentials'])){
            $credent = json_decode($data['refund_credentials'],true);
            $data['refund_credentials'] = implode(',',$credent);
        }
        unset($data['/admin/order/remitOrder']);
        $up = self::where(['id' => $data['id']])->update($data);
        if($up){
            return ['code' => 200, 'msg' => '上传成功'];
        }else{
            return ['code' => 201, 'msg' => '修改失败'];
        }
    }
    //财务确认退费
    public static function financeOrder($data){
        //status 1通过2驳回
        $refund_cause = '';
        if($data['status'] == 2){
            $refund_cause = $data['refund_cause'];
        }
        $up = self::where(['id' => $data['id']])->update(['confirm_status'=>$data['status'],'refund_cause'=>$refund_cause]);
        if($up){
            return ['code' => 200, 'msg' => '审核成功'];
        }else{
            return ['code' => 201, 'msg' => '审核失败'];
        }
    }
    //退款确认添加备注
    public static function addremark($data){
        unset($data['/admin/order/addremark']);
        $admin = isset(AdminLog::getAdminInfo()->admin_user) ? AdminLog::getAdminInfo()->admin_user : [];
        $find = self::where(['id' => $data['id']])->first();
        $str = $admin['username'].':'.$data['remark'].'<br/>';
        $newstr = $find['remit_remark'].$str;
        $up = self::where(['id' => $data['id']])->update(['remit_remark'=>$newstr]);
        if($up){
            return ['code' => 200, 'msg' => '添加成功'];
        }else{
            return ['code' => 201, 'msg' => '添加失败'];
        }
    }
    //关联订单列表 id  订单id
    public static function relevanceOrder($data){
        $returnorder = self::where(['id'=>$data['id']])->first();
        $orderid = explode(',',$returnorder['order_id']);
        $order=[];
        if(!empty($returnorder['order_id'])){
            foreach ($orderid as $k=>$v){
                $orderone = Pay_order_inside::where(['id'=>$v])->first();
                //查学校
                if(empty($orderone['school_id']) || $orderone['school_id'] == 0){
                    $orderone['school_name'] = '';
                }else{
                    $school = School::where(['id'=>$orderone['school_id']])->first();
                    if($school){
                        $orderone['school_name'] = $school['school_name'];
                    }
                }

                if($orderone['pay_type'] <= 9){
                    if(!empty($orderone['offline_id'])){
                        $chnnel = Channel::where(['id'=>$v['offline_id']])->first();
                        if($orderone['pay_type'] == 1){
                            $orderone['pay_type_text'] = $chnnel['channel_name'].'-微信';
                        }else if ($orderone['pay_type'] == 2){
                            $orderone['pay_type_text'] = $chnnel['channel_name'].'-支付宝';
                        }else if ($orderone['pay_type'] == 3){
                            $orderone['pay_type_text'] = $chnnel['channel_name'].'-汇聚-微信';
                        }else if ($orderone['pay_type'] == 4){
                            $orderone['pay_type_text'] =$chnnel['channel_name'].'-汇聚-支付宝';
                        }else if ($orderone['pay_type'] == 5 ||$orderone['pay_type'] == 8||$orderone['pay_type'] == 9){
                            $orderone['pay_type_text'] =$chnnel['channel_name'].'-银联';
                        }else if ($orderone['pay_type'] == 6){
                            $orderone['pay_type_text'] =$chnnel['channel_name'].'-汇付';
                        }
                    }else{
                        $orderone['pay_type_text']='';
                    }
                }else{
                    if(!empty($orderone['offline_id'])){
                        $offline = OfflinePay::where(['id'=>$orderone['offline_id']])->first();
                        if ($orderone['pay_type'] == 10){
                            $orderone['pay_type_text'] = '银行卡支付-'.$offline['account_name'];
                        }else if ($orderone['pay_type'] == 11){
                            $orderone['pay_type_text'] = '对公转账-'.$offline['account_name'];
                        }else if ($orderone['pay_type'] == 12){
                            $orderone['pay_type_text'] = '支付宝账号对公-'.$offline['account_name'];
                        }
                    }else{
                        $orderone['pay_type_text']='';
                    }
                }



                if($orderone['pay_status'] == 0){
                    $orderone['pay_status_text'] = '未支付';
                }else{
                    $orderone['pay_status_text'] = '已支付';
                }
                if(!isset($orderone['return_visit'])){
                    $orderone['return_visit_text'] = '';
                }else{
                    if($orderone['return_visit'] == 0){
                        $orderone['return_visit_text'] = '否';
                    }else{
                        $orderone['return_visit_text'] = '是';
                    }
                }
                if(!isset($orderone['classes'])){
                    $orderone['classes_text'] = '';
                }else{
                    if( $orderone['classes'] == 0){
                        $orderone['classes_text'] = '否';
                    }else{
                        $orderone['classes_text'] = '是';
                    }
                }
                if(empty($orderone['confirm_order_type'])){
                    $orderone['confirm_order_type_text'] = '';
                }else{
                    if($orderone['confirm_order_type'] == 1){
                        $orderone['confirm_order_type_text'] = '课程订单';
                    }else if($orderone['confirm_order_type'] == 2){
                        $orderone['confirm_order_type_text'] = '报名订单';
                    }else if($orderone['confirm_order_type'] == 3){
                        $orderone['confirm_order_type_text'] = '课程+报名订单';
                    }
                }

                if(empty($orderone['first_pay'])){
                    $orderone['first_pay_text'] = '';
                }else{
                    if($orderone['first_pay'] == 1){
                        $orderone['first_pay_text'] = '全款';
                    }else if($orderone['first_pay'] == 2){
                        $orderone['first_pay_text'] = '定金';
                    }else if($orderone['first_pay'] == 3){
                        $orderone['first_pay_text'] = '部分尾款';
                    }else if($orderone['first_pay'] == 4){
                        $orderone['first_pay_text'] = '最后一笔尾款';
                    }
                }
                if(isset($orderone['status']) && strlen($orderone['status']) >0 && $orderone['status'] == 0){
                    $orderone['confirm_status_text'] = '待提交';
                }else if($orderone['confirm_status'] == 0){
                    $orderone['confirm_status_text'] = '待总校财务确认';
                }else if($orderone['confirm_status'] == 1){
                    $orderone['confirm_status_text'] = '待总校确认';
                }else if($orderone['confirm_status'] == 2){
                    $orderone['confirm_status_text'] = '已确认';
                }else if($orderone['confirm_status'] == 3){
                    $orderone['confirm_status_text'] = '被财务驳回';
                }else if($orderone['confirm_status'] == 4){
                    $orderone['confirm_status_text'] = '被总校驳回';
                }
                //course  课程
                $course = Course::select('course_name')->where(['id'=>$orderone['course_id']])->first();
                $orderone['course_name'] = $course['course_name'];
                //Project  项目
                $project = Project::select('name')->where(['id'=>$orderone['project_id']])->first();
                $orderone['project_name'] = $project['name'];
                //Subject  学科
                $subject = Project::select('name')->where(['id'=>$orderone['subject_id']])->first();
                $orderone['subject_name'] = $subject['name'];
                if(!empty($orderone['education_id']) && $orderone['education_id'] != 0){
                    //查院校
                    $education = Education::select('education_name')->where(['id'=>$orderone['education_id']])->first();
                    $orderone['education_name'] = $education['education_name'];
                    //查专业
                    $major = Major::where(['id'=>$orderone['major_id']])->first();
                    $orderone['major_name'] = $major['major_name'];
                }
                $order[] = $orderone;
            }
        }
        return ['code' => 200 , 'msg' => '获取成功','data'=>$order];
    }
    //关联支付凭证 id  订单id
    public static function relevanceVoucher($data){
        $returnorder = self::select('order_id','pay_credentials','remit_time','refund_no')->where(['id'=>$data['id']])->first();
        if(empty($returnorder)){
            return ['code' => 201 , 'msg' => '数据错误'];
        }
        $payvoucher=[];
        $imglist =[];
        if(!empty($returnorder)){
            if(!empty($returnorder['pay_credentials'])){
            $newarr1 = explode(',',$returnorder['pay_credentials']);
            foreach ($newarr1 as $kss=>$vss){
                $arr1=[
                    'pay_voucher_time' => $returnorder['remit_time'],
                    'order_no' => $returnorder['refund_no'],
                    'pay_voucher' => $vss,
                ];
                array_push($payvoucher,$arr1);
                array_push($imglist,$vss);
              }
            }
            if(!empty($returnorder['order_id'])){
                $orderid = explode(',',$returnorder['order_id']);
                foreach ($orderid as $k=>$v) {
                    $orderone = Pay_order_inside::where(['id' => $v])->first();
                    $arr=[
                        'pay_voucher_time' => $orderone['pay_voucher_time'],
                        'order_no' => $orderone['order_no'],
                        'pay_voucher' => $orderone['pay_voucher']
                    ];
                    array_push($payvoucher,$arr);
                    array_push($imglist,$orderone['pay_voucher']);
                }
            }
        }
        return ['code' => 200 , 'msg' => '获取成功','data'=>$payvoucher,'imglist'=>$imglist];
    }
}
