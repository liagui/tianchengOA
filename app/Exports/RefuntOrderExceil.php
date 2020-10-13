<?php
namespace App\Exports;

use App\Models\Admin;
use App\Models\Channel;
use App\Models\Course;
use App\Models\Education;
use App\Models\Major;
use App\Models\OfflinePay;
use App\Models\Pay_order_inside;
use App\Models\Project;
use App\Models\Refund_order;
use App\Models\School;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
class RefuntOrderExceil implements FromCollection, WithHeadings {

    protected $where;
    protected $schools;
    public function __construct($invoices,$schoolarr){
        $this->where = $invoices;
        $this->schools = $schoolarr;
    }
    public function collection() {
        $data = $this->where;
        $schoolarr = $this->schools;
        //退费状态
        $where=[];
        if(isset($data['confirm_status'])&& !empty($data['confirm_status'])){
            $where['confirm_status'] = $data['confirm_status'];
        }
        //打款状态
        if(isset($data['refund_plan']) && !empty($data['refund_plan'])){
            $where['refund_plan'] = $data['refund_plan'];
        }
        //学校id
        if(isset($data['school_id'])&& !empty($data['school_id'])){
            $where['school_id'] = $data['school_id'];
        }
        //判断时间
        $begindata="2020-03-04";
        $enddate = date('Y-m-d');
        $statetime = !empty($data['start_time'])?$data['start_time']:$begindata;
        $endtime = !empty($data['end_time'])?$data['end_time']:$enddate;
        $state_time = $statetime." 00:00:00";
        $end_time = $endtime." 23:59:59";
        //列表
        $order = Refund_order::where($where)->where(function($query) use ($data,$schoolarr) {
        if(isset($data['confirm_order_type'])){
            $query->where('confirm_order_type',$data['confirm_order_type']);
        }
        if(isset($data['order_on']) && !empty($data['order_on'])){
            $query->where('refund_no',$data['order_on'])
                ->orwhere('student_name',$data['order_on'])
                ->orwhere('phone',$data['order_on']);
        }
        $query->whereIn('school_id',$schoolarr);
        })
        ->whereBetween('create_time', [$state_time, $end_time])
        ->orderByDesc('id')->get()->toArray();
        //循环查询分类
        if(!empty($order)){
            foreach ($order as $k=>&$v){
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
                }else{
                    $v['confirm_status_text'] = '已确认';
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
        foreach ($order as $k=>$v){
            $newtuyadan = [
                'refund_no' => ' '.$v['refund_no'],
                'create_time' => $v['create_time'],
                'student_name' => $v['student_name'],
                'phone' => $v['phone'],
                'school_name' => $v['school_name'],
                'project_name' => $v['project_name'],
                'subject_name' => $v['subject_name'],
                'course_name' => $v['course_name'],
                'refund_Price' => $v['refund_Price'],
                'reality_price' => $v['reality_price'],
                'refund_reason' => $v['refund_reason'],
                'refund_cause' => $v['refund_cause'],
                'confirm_status_text' => $v['confirm_status_text'],
                'refund_plan_text' => $v['refund_plan_text'],
                'pay_credentials' => $v['pay_credentials'],
                'refund_credentials' => $v['refund_credentials'],
            ];
            $tuyadan[]=$newtuyadan;
        }
        return collect($tuyadan);
    }

    public function headings(): array{
        return [
            '退费订单',
            '退费发起时间',
            '姓名',
            '手机号',
            '所属分校',
            '项目',
            '学科',
            '课程',
            '退费金额',
            '实际退费金额',
            '退费原因',
            '驳回原因',
            '退费进度',
            '打款进度',
            '支付凭证',
            '退费凭证'
        ];
    }
}
