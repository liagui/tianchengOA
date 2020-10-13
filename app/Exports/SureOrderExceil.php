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
use App\Models\School;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
class SureOrderExceil implements FromCollection, WithHeadings {

    protected $where;
    protected $schools;
    public function __construct($invoices,$schoolarr){
        $this->where = $invoices;
        $this->schools = $schoolarr;
    }
    public function collection() {
        $data = $this->where;
        $schoolarr = $this->schools;
        $where=[];
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
        if(isset($data['school_id'])){
            $where['school_id'] = $data['school_id'];
        }
        if(isset($data['pay_type'])){
            $where['pay_type'] = $data['pay_type'];
        }
        if(isset($data['confirm_order_type']) ){
            $where['confirm_order_type'] = $data['confirm_order_type'];
        }
        if(isset($data['return_visit'])){
            $where['return_visit'] = $data['return_visit'];
        }
        if(isset($data['classes']) ){
            $where['classes'] = $data['classes'];
        }
        $order = Pay_order_inside::where(function($query) use ($data,$schoolarr) {
            if(isset($data['order_no']) && !empty($data['order_no'])){
                $query->where('order_no',$data['order_no'])
                    ->orwhere('name',$data['order_no'])
                    ->orwhere('mobile',$data['order_no']);
            }
            $query->whereIn('school_id',$schoolarr);
        })
        ->where('pay_status','<',2)
        ->where('confirm_status','=',1)
        ->where($where)
        ->orderByDesc('id')
        ->get()->toArray();
        if(!empty($order)){
            foreach ($order as $k=>&$v){
                if($v['pay_type'] <= 4){
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
                        }
                    }else{
                        $v['pay_type_text']='';
                    }
                }else{
                    if(!empty($v['offline_id'])){
                        $offline = OfflinePay::where(['id'=>$v['offline_id']])->first();
                        if ($v['pay_type'] == 5){
                            $v['pay_type_text'] = '银行卡支付-'.$offline['account_name'];
                        }else if ($v['pay_type'] == 6){
                            $v['pay_type_text'] = '对公转账-'.$offline['account_name'];
                        }else if ($v['pay_type'] == 7){
                            $v['pay_type_text'] = '支付宝账号对公-'.$offline['account_name'];
                        }
                    }else{
                        $v['pay_type_text']='';
                    }
                }
                if($v['pay_status'] == 0){
                    $v['pay_status_text'] = '待支付';
                }else if($v['pay_status'] == 1){
                    $v['pay_status_text'] = '已支付';
                }else if($v['pay_status'] == 2){
                    $v['pay_status_text'] = '支付失败';
                }else if($v['pay_status'] == 3){
                    $v['pay_status_text'] = '待审核';
                }
                if($v['return_visit'] == 0){
                    $v['return_visit_text'] = '否';
                }else{
                    $v['return_visit_text'] = '是';
                }
                if($v['classes'] == 0){
                    $v['classes_text'] = '否';
                }else{
                    $v['classes_text'] = '是';
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
        $tuyadan = [];
        foreach ($order as $k=>$v){
            $newtuyadan = [
                'order_number' => $v['order_no'],
                'create_time' => $v['create_time'],
                'name' => $v['name'],
                'mobile' => $v['mobile'],
                'school_name' => $v['school_name'],
                'project_name' => $v['project_name'],
                'subject_name' => $v['subject_name'],
                'course_name' => $v['course_name'],
                'pay_type_text' => $v['pay_type_text'],
                'course_Price' => $v['course_Price'],
                'sign_Price' => $v['sign_Price'],
                'pay_price' => $v['pay_price'],
                'return_visit_text' => $v['return_visit_text'],
                'classes_text' => $v['classes_text'],
                'pay_time' => $v['pay_time'],
                'confirm_order_type_text' => $v['confirm_order_type_text'],
                'first_pay_text' => $v['first_pay_text'],
                'pay_voucher' => $v['pay_voucher'],
            ];
            $tuyadan[]=$newtuyadan;
        }
        return $order;
    }

    public function headings(): array{
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
            '是否回访',
            '是否开课',
            '支付成功时间',
            '订单类型',
            '缴费类型',
            '凭证'
        ];
    }
}
