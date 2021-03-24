<?php
namespace App\Exports;

use App\Models\Admin;
use App\Models\AdminLog;
use App\Models\Channel;
use App\Models\Course;
use App\Models\Education;
use App\Models\Major;
use App\Models\OfflinePay;
use App\Models\Order;
use App\Models\Pay_order_external;
use App\Models\Pay_order_inside;
use App\Models\Project;
use App\Models\School;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
class OrderExport implements FromCollection, WithHeadings {

    protected $data;
    protected $school;
    public function __construct($invoices,$schoolarr){
        $this->data = $invoices;
        $this->school = $schoolarr;
    }
    public function collection() {
        $data = $this->data;
        $schoolarr = $this->school;
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
        //订单是否回访
        if(!empty($data['return_visit'])){
            $where['return_visit'] = $data['return_visit'];
        }
        //订单状态
        if(!empty($data['confirm_status'])){
            $where['confirm_status'] = $data['confirm_status'];
        }
        //学校id
        if(!empty($data['school_id'])){
            $where['school_id'] = $data['school_id'];
        }
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
        //课程id
        if(!empty($data['course_id'])){
            $where['course_id'] = $data['course_id'];
        }
        //数据   流转订单 + 第三方支付订单
        $order = Pay_order_inside::where(function($query) use ($data,$schoolarr) {
            if(isset($data['order_no']) && !empty($data['order_no'])){
                $query->where('order_no',$data['order_no'])
                    ->orwhere('name',$data['order_no'])
                    ->orwhere('mobile',$data['order_no']);
            }
            if(isset($data['classes']) && !empty($data['classes'])){
                $query->where('classes',$data['classes']);
            }
            if(isset($data['confirm_order_type']) && !empty($data['confirm_order_type'])){
                $query->where('confirm_order_type',$data['confirm_order_type']);
            }
            $query->whereIn('school_id',$schoolarr);
        })
            ->where($where)
            ->whereBetween('create_time', [$state_time, $end_time])
            ->orderByDesc('id')
            ->get()->toArray();
        $external = Pay_order_external::where(function($query) use ($data,$schoolarr) {
            if (isset($data['order_no']) && !empty($data['order_no'])) {
                $query->where('order_no', $data['order_no'])
                    ->orwhere('name', $data['order_no'])
                    ->orwhere('mobile', $data['order_no']);
              }
            $query->whereIn('school_id',$schoolarr);
           })->where($where)
            ->where(['pay_status'=>1,'status'=>0])
            ->whereBetween('create_time', [$state_time, $end_time])
            ->orderByDesc('id')
            ->get()->toArray();
        //分校只显示流转
        if(!empty($data['isBranchSchool']) && $data['isBranchSchool'] == true){
            $all = $order;
        }else{
            //两数组合并
            if (!empty($order) && !empty($external)) {
                $all = array_merge($order, $external);//合并两个二维数组
            } else {
                $all = !empty($order) ? $order : $external;
            }
        }
        $date = array_column($all, 'create_time');
        array_multisort($date, SORT_DESC, $all);
        if(!empty($all)){
            foreach ($all as $k=>&$v){
                //查学校
                if(empty($v['school_id']) || $v['school_id'] == 0){
                    $v['school_name'] = '';
                }else{
                    $school = School::where(['id'=>$v['school_id']])->first();
                    if($school){
                        $v['school_name'] = $school['school_name'];
                    }
                }
                if($v['pay_type'] <= 9){
                    if(!empty($v['offline_id'])){
                        $chnnel = Channel::where(['id'=>$v['offline_id']])->first();
                        if($v['pay_type'] == 1){
                            $v['pay_type_text'] = $chnnel['channel_name'].'-微信';
                        }else if ($v['pay_type'] == 2){
                            $v['pay_type_text'] = $chnnel['channel_name'].'-支付宝';
                        }else if ($v['pay_type'] == 3){
                            $v['pay_type_text'] = $chnnel['channel_name'].'-汇聚-微信';
                        }else if ($v['pay_type'] == 4){
                            $v['pay_type_text'] =$chnnel['channel_name'].'-汇聚-支付宝';
                        }else if ($v['pay_type'] == 5 ||$v['pay_type'] == 8||$v['pay_type'] == 9){
                            $v['pay_type_text'] =$chnnel['channel_name'].'-银联';
                        }else if ($v['pay_type'] == 6){
                            $v['pay_type_text'] =$chnnel['channel_name'].'-汇付';
                        }
                    }else{
                        $v['pay_type_text']='';
                    }
                }else{
                    if(!empty($v['offline_id'])){
                        $offline = OfflinePay::where(['id'=>$v['offline_id']])->first();
                        if ($v['pay_type'] == 10){
                            $v['pay_type_text'] = '银行卡支付-'.$offline['account_name'];
                        }else if ($v['pay_type'] == 11){
                            $v['pay_type_text'] = '对公转账-'.$offline['account_name'];
                        }else if ($v['pay_type'] == 12){
                            $v['pay_type_text'] = '支付宝账号对公-'.$offline['account_name'];
                        }
                    }else{
                        $v['pay_type_text']='';
                    }
                }
                if($v['pay_status'] == 0){
                    $v['pay_status_text'] = '未支付';
                }else if($v['pay_status'] == 1){
                    $v['pay_status_text'] = '已支付';
                }else if($v['pay_status'] == 2){
                    $v['pay_status_text'] = '支付失败';
                }else if($v['pay_status'] == 3){
                    $v['pay_status_text'] = '待审核';
                }
                if(!isset($v['return_visit'])){
                    $v['return_visit_text'] = '';
                }else{
                    if($v['return_visit'] == 0){
                        $v['return_visit_text'] = '否';
                    }else{
                        $v['return_visit_text'] = '是';
                    }
                }
                if(!isset($v['classes'])){
                    $v['classes_text'] = '';
                }else{
                    if( $v['classes'] == 0){
                        $v['classes_text'] = '否';
                    }else{
                        $v['classes_text'] = '是';
                    }
                }
                if(empty($v['confirm_order_type'])){
                    $v['confirm_order_type_text'] = '';
                }else{
                    if($v['confirm_order_type'] == 1){
                        $v['confirm_order_type_text'] = '课程订单';
                    }else if($v['confirm_order_type'] == 2){
                        $v['confirm_order_type_text'] = '报名订单';
                    }else if($v['confirm_order_type'] == 3){
                        $v['confirm_order_type_text'] = '课程+报名订单';
                    }
                }

                if(empty($v['first_pay'])){
                    $v['first_pay_text'] = '';
                }else{
                    if($v['first_pay'] == 1){
                        $v['first_pay_text'] = '全款';
                    }else if($v['first_pay'] == 2){
                        $v['first_pay_text'] = '定金';
                    }else if($v['first_pay'] == 3){
                        $v['first_pay_text'] = '部分尾款';
                    }else if($v['first_pay'] == 4){
                        $v['first_pay_text'] = '最后一笔尾款';
                    }
                }
                if(empty($v['confirm_status']) && $v['confirm_status'] != 0 ){
                    $v['confirm_status_text'] = '';
                }else{
                    if(!empty($v['status']) && $v['status'] == 0){
                        $v['confirm_status_text'] = '待提交';
                    }else if($v['confirm_status'] == 0){
                        $v['confirm_status_text'] = '待总校财务确认';
                    }else if($v['confirm_status'] == 1){
                        $v['confirm_status_text'] = '待总校确认';
                    }else if($v['confirm_status'] == 2){
                        $v['confirm_status_text'] = '已确认';
                    }else if($v['confirm_status'] == 3){
                        $v['confirm_status_text'] = '被财务驳回';
                    }else if($v['confirm_status'] == 4){
                        $v['confirm_status_text'] = '被总校驳回';
                    }
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
        $tuyadan = [];
        foreach ($all as $ks=>$vs){
            $newtuyadan = [
                'order_number' => ' '.$vs['order_no'],
                'create_time' => $vs['create_time'],
                'name' => $vs['name'],
                'mobile' => $vs['mobile'],
                'school_name' => $vs['school_name'],
                'project_name' => $vs['project_name'],
                'subject_name' => $vs['subject_name'],
                'course_name' => $vs['course_name'],
                'pay_type_text' => isset($vs['pay_type_text'])?$vs['pay_type_text']:'',
                'course_Price' => $vs['course_Price'],
                'sign_Price' => $vs['sign_Price'],
                'pay_price' => $vs['pay_price'],
                'pay_status_text' => $vs['pay_status_text'],
                'return_visit_text' => $vs['return_visit_text'],
                'classes_text' => $vs['classes_text'],
                'pay_time' => $vs['pay_time'],
                'sure_time' => $vs['sure_time'],
                'confirm_order_type_text' => $vs['confirm_order_type_text'],
                'first_pay_text' => $vs['first_pay_text'],
                'confirm_status_text' => $vs['confirm_status_text'],
            ];
            $tuyadan[]=$newtuyadan;
        }
        return collect($tuyadan);
    }

    public function headings(): array
    {
        return [
            '订单编号',
            '订单创建时间',
            '姓名',
            '手机号',
            '所属分校',
            '项目',
            '学科',
            '课程',
            '支付方式',
            '课程金额',
            '报名金额',
            '总金额',
            '支付状态',
            '是否回访',
            '是否开课',
            '支付成功时间',
            '订单确认时间',
            '订单类型',
            '缴费类型',
            '订单状态',
        ];
    }
}
