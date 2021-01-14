<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pay_order_apply extends Model
{
    //指定别的表名
    public $table = 'pay_order_apply';
    //时间戳设置
    public $timestamps = false;

    public static function applyList($data,$schoolarr){
        $where['del_flag']=0;
        //判断时间
        $begindata="2020-03-04";
        $enddate = date('Y-m-d');
        $statetime = !empty($data['state_time'])?$data['state_time']:$begindata;
        $endtime = !empty($data['end_time'])?$data['end_time']:$enddate;
        $state_time = $statetime." 00:00:00";
        $end_time = $endtime." 23:59:59";
        //学校id
        if(isset($data['school_id'])){
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
        if(isset($data['course_id'])){
            $where['course_id'] = $data['course_id'];
        }
        //每页显示的条数
        $pagesize = (int)isset($data['pagesize']) && $data['pagesize'] > 0 ? $data['pagesize'] : 20;
        $page     = isset($data['page']) && $data['page'] > 0 ? $data['page'] : 1;
        $offset   = ($page - 1) * $pagesize;

        //数据   流转订单 + 第三方支付订单
        $order = self::where(function($query) use ($data,$schoolarr) {
            if(isset($data['order_no']) && !empty($data['order_no'])){
                $query->where('order_no',$data['order_no'])
                    ->orwhere('name',$data['order_no'])
                    ->orwhere('mobile',$data['order_no']);
            }
            $query->whereIn('school_id',$schoolarr);
        })
        ->where($where)
        ->whereBetween('create_time', [$state_time, $end_time])
        ->offset($offset)->limit($pagesize)
        ->orderByDesc('id')
        ->get()->toArray();
        $count = 0;
        if(!empty($order)){
            foreach ($order as $k=>&$v){
                $count++;
                //查学校
                $school = School::where(['id'=>$v['school_id']])->first();
                if($school){
                    $v['school_name'] = $school['school_name'];
                }
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
                $v['confirm_order_type_text'] = '报名订单';
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
        return ['code' => 200 , 'msg' => '操作成功','data'=>$order,'page'=>$page];
    }

}
