<?php
namespace App\Exports;

use App\Models\Course;
use App\Models\Education;
use App\Models\Major;
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
        if(isset($data['confirm_status']) && !empty($data['confirm_status'])){
            $where['confirm_status'] = $data['confirm_status'];
        }
        //打款状态
        if(isset($data['refund_plan']) && !empty($data['refund_plan'])){
            $where['refund_plan'] = $data['refund_plan'];
        }
        //学校id
        $school_id=[];
        if(isset($data['school_name'])&& !empty($data['school_name'])){
            $school_id = School::select('id')->where('school_name','like','%'.$data['school_name'].'%')->where('is_del',0)->get();
        }
        //科目id&学科id
        if(isset($data['project_id']) && !empty($data['project_id'])){
            $parent = json_decode($data['project_id'], true);
            if(!empty($parent[0])){
                $where['project_id'] = $parent[0];
                if(!empty($parent[1])){
                    $where['subject_id'] = $parent[1];
                }
            }
        }
        if(isset($data['course_id']) && !empty($data['course_id'])){
            $where['course_id'] = $data['course_id'];
        }
        //判断时间
        $begindata="2020-03-04";
        $enddate = date('Y-m-d');
        $statetime = !empty($data['start_time'])?$data['start_time']:$begindata;
        $endtime = !empty($data['end_time'])?$data['end_time']:$enddate;
        $state_time = $statetime." 00:00:00";
        $end_time = $endtime." 23:59:59";
        //列表
        $order = Refund_order::where($where)->where(function($query) use ($data,$schoolarr,$school_id) {
            if(isset($data['confirm_order_type']) && !empty($data['confirm_order_type'])){
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
        $tuyadan = [];
        foreach ($order as $ks=>$vs){
            $newtuyadan = [
                'refund_no' => ' '.$vs['refund_no'],
                'create_time' => $vs['create_time'],
                'student_name' => $vs['student_name'],
                'phone' => $vs['phone'],
                'project_name' => $vs['project_name'],
                'subject_name' => $vs['subject_name'],
                'course_name' => $vs['course_name'],
                'school_name' => $vs['school_name'],
                'refund_Price' => $vs['refund_Price'],
                'sing_price' => $vs['sing_price'],
                'reality_price' => $vs['reality_price'],
                'reality_sing_price' => $vs['reality_sing_price'],
                'bank_card' => $vs['bank_card'],
                'bank_name' => $vs['bank_name'],
                'openbank_name' => $vs['openbank_name'],
                'refund_reason' => $vs['refund_reason'],
                'refund_cause' => $vs['refund_cause'],
                'confirm_status_text' => $vs['confirm_status_text'],
                'finance_text' => $vs['finance_text'],
                'refund_plan_text' => $vs['refund_plan_text'],
                'remit_remark' => $vs['remit_remark'],
            ];
            $tuyadan[]=$newtuyadan;
        }
        return collect($tuyadan);
    }

    public function headings(): array{
        return [
            '退费订单号',
            '退费发起时间',
            '姓名',
            '手机号',
            '学科大类',
            '学科小类',
            '课程名称',
            '分校',
            '课程退费金额',
            '报名退费金额',
            '实际课程退费金额',
            '实际报名退费金额',
            '银行卡号',
            '开户行',
            '开户名称',
            '退费原因',
            '驳回原因',
            '退费状态',
            '财务审核',
            '打款状态',
            '备注'
        ];
    }
}
