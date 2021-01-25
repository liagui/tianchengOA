<?php

/*namespace App\Exports;

use App\Models\Chapters;
use Maatwebsite\Excel\Concerns\FromCollection;

class UsersExport implements FromCollection
{
    public function collection()
    {
        return Chapters::all();
    }
}*/


namespace App\Exports;
use App\Models\Major;
use App\Models\Pay_order_inside;
use App\Models\Refund_order;
use App\Models\School;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
class BranchExceil implements FromCollection, WithHeadings{
    protected $data;
    public function __construct($invoices){
        $this->data = $invoices;
    }
    public function collection() {
        $body = $this->data;
            //新数组赋值
            $array = [];
            //获取分校业绩列表
            $list = DB::table('school')->selectRaw('any_value(school.id) as school_id , any_value(count(school.id)) as t_count , any_value(school.one_extraction_ratio) as one_extraction_ratio , any_value(school.two_extraction_ratio) as two_extraction_ratio , any_value(school.school_name) as school_name , any_value(school.level) as level , any_value(school.tax_point) as tax_point , any_value(school.commission) as commission , any_value(school.deposit) as deposit , any_value(sum(pay_order_inside.after_tax_amount)) as after_tax_amount,any_value(pay_order_inside.sum_Price) as sum_Price,any_value(sum(if(pay_order_inside.confirm_status = 1 , pay_order_inside.pay_price , 0))) as pay_price,any_value(sum(pay_order_inside.agent_margin)) as agent_margin,any_value(pay_order_inside.first_out_of_amount) as first_out_of_amount,any_value(pay_order_inside.second_out_of_amount) as second_out_of_amount,any_value(pay_order_inside.education_id) as education_id,any_value(pay_order_inside.major_id) as major_id,any_value(sum(pay_order_inside.sign_Price)) as sign_Price')->leftjoin("pay_order_inside", function ($join) {
                $join->on('school.id', '=', 'pay_order_inside.school_id');
            })->where('school.is_del', 0)->where(function ($query) use ($body) {
                //判断分校id是否为空和合法
                if (isset($body['school_id']) && !empty($body['school_id']) && $body['school_id'] > 0) {
                    $query->where('school.id', '=', $body['school_id']);
                }
                //获取日期
                // if (isset($body['search_time']) && !empty($body['search_time'])) {
                //     $create_time = json_decode($body['search_time']);
                //     $state_time = $create_time[0] . " 00:00:00";
                //     $end_time = $create_time[1] . " 23:59:59";
                //     $query->whereBetween('pay_order_inside.comfirm_time', [$state_time, $end_time]);
                // }
                $state_time = $body['search_time'][0] . " 00:00:00";
                $end_time =$body['search_time'][1] . " 23:59:59";
                $query->whereBetween('pay_order_inside.comfirm_time', [$state_time, $end_time]);
            })->orderByDesc('school.create_time')->groupBy(DB::raw('school.id'))->get()->toArray();
            //循环获取相关信息
            foreach ($list as $k => $v) {
                $v = (array)$v;
                //获取是几级分校
                if ($v['level'] == 1) {
                    $first_school_name = $v['school_name'];
                    $two_school_name = '';
                    $three_school_name = '';
                } elseif ($v['level'] == 2) {
                    $first_school_name = '';
                    $two_school_name = $v['school_name'];
                    $three_school_name = '';
                } elseif ($v['level'] == 3) {
                    $first_school_name = '';
                    $two_school_name = '';
                    $three_school_name = $v['school_name'];
                }

                //到款业绩=到款金额
                $payment_performance = sprintf("%.2f", $v['pay_price']);

                //扣税比例
                $tax_deduction_ratio = $v['tax_point'];

                //扣税=到账金额*扣税比例
                $tax_deduction = sprintf("%.2f", $v['pay_price'] * ($tax_deduction_ratio / 100));

                //税后金额=到账金额-扣税
                $after_tax_amount = $v['pay_price'] > $tax_deduction ? sprintf("%.2f", $v['pay_price'] - $tax_deduction) : 0;

                $body['school_id'] = $v['school_id'];
                //单数=报名订单数量+含有学历成本的订单数量
                $enroll_number = Pay_order_inside::where(function ($query) use ($body) {
                    //分校查询
                    $query->where('school_id', '=', $body['school_id'])->whereIn('confirm_order_type', [2, 3]);

                    //获取日期
                    if (isset($body['search_time']) && !empty($body['search_time'])) {
                        $create_time = json_decode($body['search_time']);
                        $state_time = $create_time[0] . " 00:00:00";
                        $end_time = $create_time[1] . " 23:59:59";
                        $query->whereBetween('pay_order_inside.comfirm_time', [$state_time, $end_time]);
                    }
                })->count();
                $chengben_number = Pay_order_inside::where(function ($query) use ($body) {
                    //分校查询
                    $query->where('school_id', '=', $body['school_id'])->where('education_id', '>', 0)->where('major_id', '>', 0);

                    //获取日期
                    if (isset($body['search_time']) && !empty($body['search_time'])) {
                        $create_time = json_decode($body['search_time'], true);
                        $state_time = $create_time[0] . " 00:00:00";
                        $end_time = $create_time[1] . " 23:59:59";
                        $query->whereBetween('pay_order_inside.comfirm_time', [$state_time, $end_time]);
                    }
                })->count();
                $order_number = $enroll_number + $chengben_number;

                //成本=学历成本+报名费用
                $education_major_ids = Pay_order_inside::select('major_id')->where(function ($query) use ($body) {
                    //分校查询
                    $query->where('school_id', '=', $body['school_id']);

                    //获取日期
                    if (isset($body['search_time']) && !empty($body['search_time'])) {
                        $create_time = json_decode($body['search_time'], true);
                        $state_time = $create_time[0] . " 00:00:00";
                        $end_time = $create_time[1] . " 23:59:59";
                        $query->whereBetween('pay_order_inside.comfirm_time', [$state_time, $end_time]);
                    }
                })->get()->toArray();
                $major_ids = array_column($education_major_ids, 'major_id');
                $education_cost = Major::whereIn('id', $major_ids)->sum('price');
                $sum_cost = sprintf("%.2f", $education_cost + $v['sign_Price']);

                //实际到款=税后金额-成本
                $actual_receipt = $after_tax_amount > $v['sum_Price'] ? sprintf("%.2f", $after_tax_amount - $v['sum_Price']) : 0;

                //返佣比例=后台分校管理中佣金比例
                $commission_rebate = $v['commission'];

                //返佣金额=实际到款*返佣比例
                $commission_money = sprintf("%.2f", $actual_receipt * ($commission_rebate / 100));

                //保证金=返佣金额*后台分校管理中押金比例
                $bond = sprintf("%.2f", $commission_money * ($v['deposit'] / 100));

                $one_extraction_ratio = $v['one_extraction_ratio'] && !empty($v['one_extraction_ratio']) ? $v['one_extraction_ratio'] : 0;
                $two_extraction_ratio = $v['two_extraction_ratio'] && !empty($v['two_extraction_ratio']) ? $v['two_extraction_ratio'] : 0;

                //一级分校无一级抽离比例、押金和二级抽离比例、押金
                if ($v['level'] == 1) {
                    $first_out_of_amount = '';
                    $second_out_of_amount = '';
                    $first_out_of_money = '';
                    $second_out_of_money = '';
                    //代理保证金
                    $agent_margin = $v['agent_margin'] && !empty($v['agent_margin']) ? $v['agent_margin'] : 0;

                    //一级分校下面的所有二级分校
                    $seond_school_id = School::select('id')->where('parent_id', $v['school_id'])->where('level', 2)->get()->toArray();
                    $seond_school_ids = array_column($seond_school_id, 'id');

                    //二级下面的所有三级分校
                    $three_school_id = School::select('id')->whereIn('parent_id', $seond_school_ids)->where('level', 3)->get()->toArray();
                    $three_school_ids = array_column($three_school_id, 'id');

                    //一级分校的实际返佣=返佣金额-一级分校的保证金+（二级分校的一级抽离金额+三级分校的一级抽离金额）*（1-押金比例）-（一级分校退费*返佣比例+二级分校退费*二级分校1级抽离比例+三级分校退费*二级分校1级抽离比例）
                    //二级分校的一级抽离金额
                    $first_out_of_amount1 = Pay_order_inside::whereIn('school_id', $seond_school_ids)->sum('first_out_of_amount');

                    //三级分校的一级抽离金额
                    $first_out_of_amount2 = Pay_order_inside::whereIn('school_id', $three_school_ids)->sum('first_out_of_amount');

                    //一级分校退费金额
                    $first_refund_Price = Refund_order::where('school_id', $v['school_id'])->where('confirm_status', 1)->sum('refund_Price');
                    //二级分校退费金额
                    $send_refund_Price = Refund_order::whereIn('school_id', $seond_school_ids)->where('confirm_status', 1)->sum('refund_Price');
                    //三级分校退费金额
                    $three_refund_Price = Refund_order::whereIn('school_id', $three_school_ids)->where('confirm_status', 1)->sum('refund_Price');

                    //二级分校的一级抽离比例=后台分校管理中一级抽离比例  |  三级分校的一级抽离比例=后台分校管理中一级抽离比例
                    $actual_commission_refund = $commission_money - $bond + ($first_out_of_amount1 + $first_out_of_amount2) * (1 - $v['deposit']) - ($first_refund_Price * $v['commission'] + $send_refund_Price * $one_extraction_ratio + $three_refund_Price * $one_extraction_ratio);
                } elseif ($v['level'] == 2) {
                    //二级分校的一级抽离比例=后台分校管理中一级抽离比例
                    //二级分校的一级抽离金额=二级分校的一级抽离比例*实际到款
                    //二级分校无二级抽离比例、押金

                    //二级分校的一级抽离比例一级抽离比例
                    $first_out_of_amount = $v['one_extraction_ratio'] && !empty($v['one_extraction_ratio']) ? $v['one_extraction_ratio'] : '';
                    $first_out_of_money = !empty($v['first_out_of_amount']) && $v['first_out_of_amount'] > 0 ? $v['first_out_of_amount'] : '';
                    $second_out_of_amount = '';
                    $second_out_of_money = '';
                    //代理保证金
                    $agent_margin = $v['agent_margin'] && !empty($v['agent_margin']) ? $v['agent_margin'] : 0;

                    //二级下面的所有三级分校
                    $three_school_id = School::select('id')->where('parent_id', $v['school_id'])->where('level', 3)->get()->toArray();
                    $three_school_ids = array_column($three_school_id, 'id');

                    //三级分校的二级抽离金额
                    $second_out_of_amount2 = Pay_order_inside::whereIn('school_id', $three_school_ids)->sum('second_out_of_amount');

                    //二级分校退费金额
                    $send_refund_Price = Refund_order::where('school_id', $v['school_id'])->where('confirm_status', 1)->sum('refund_Price');
                    //三级分校退费金额
                    $three_refund_Price = Refund_order::whereIn('school_id', $three_school_ids)->where('confirm_status', 1)->sum('refund_Price');

                    //二级分校的实际返佣=二级分校的返佣金额-二级分校的保证金+三级分校的二级抽离金额*（1-押金比例）-（二级分校退费*返佣比例+三级分校退费*三级分校2级抽离比例）
                    $actual_commission_refund = $commission_money - $bond + $second_out_of_amount2 * (1 - $v['deposit']) - ($send_refund_Price * $v['commission'] + $three_refund_Price * $two_extraction_ratio);
                } elseif ($v['level'] == 3) {
                    //三级分校的一级抽离比例=后台分校管理中一级抽离比例
                    //三级分校的一级抽离金额=三级分校的一级抽离比例*实际到款
                    //三级分校的二级抽离比例=后台分校管理中二级抽离比例
                    //三级分校的二级抽离金额=三级分校的二级抽比例*实际到款
                    //三级分校的实际返佣=三级分校的返佣金额-三级分校的保证金-三级分校退费*三级分校返佣比例
                    $first_out_of_amount = $v['one_extraction_ratio'] && !empty($v['one_extraction_ratio']) ? $v['one_extraction_ratio'] : '';
                    $first_out_of_money = $v['first_out_of_amount'] && !empty($v['first_out_of_amount']) ? $v['first_out_of_amount'] : '';

                    //二级抽离比例
                    $second_out_of_amount = $v['two_extraction_ratio'] && !empty($v['two_extraction_ratio']) ? $v['two_extraction_ratio'] : '';
                    $second_out_of_money = !empty($v['second_out_of_amount']) && $v['second_out_of_amount'] > 0 ? $v['second_out_of_amount'] : '';
                    //三级分校无代理保证金
                    $agent_margin = '';

                    //三级分校退费金额
                    $three_refund_Price = Refund_order::where('school_id', $v['school_id'])->where('confirm_status', 1)->sum('refund_Price');

                    //三级分校的实际返佣=三级分校的返佣金额-三级分校的保证金-三级分校退费*三级分校返佣比例
                    $actual_commission_refund = $commission_money - $bond - $three_refund_Price * $v['commission'];
                }


                //数组赋值
                $array[] = [
                    'first_school_name' => isset($first_school_name)?$first_school_name:'',
                    'two_school_name' => isset($two_school_name)?$two_school_name:'',
                    'three_school_name' => isset($three_school_name)?$three_school_name:'',
                    'payment_performance' => $payment_performance,
                    'actual_receipt' => $actual_receipt,   //实际到款
                    'tax_deduction_ratio' => $tax_deduction_ratio,
                    'after_tax_amount' => $after_tax_amount,
                    'order_number' => $order_number,
                    'sum_cost' => $sum_cost,
                    'commission_rebate' => $commission_rebate,
                    'commission_money' => $commission_money,
                    'bond' => $bond,
                    'agent_margin' => $agent_margin,
                    'first_out_of_amount' => isset($first_out_of_amount)?$first_out_of_amount:'',
                    'first_out_of_money' => isset($first_out_of_money)?$first_out_of_money:'',
                    'second_out_of_amount' => isset($second_out_of_amount)?$second_out_of_amount:'',
                    'second_out_of_money' => isset($second_out_of_money)?$second_out_of_money:'',
                    'actual_commission_refund' => $actual_commission_refund
                ];
            }
            return collect($array);
    }

    public function headings(): array
    {
        return [
            '一级分校',
            '二级分校',
            '三级分校',
            '到款金额',
            '扣税（%）',
            '税后金额',
            '单数',
            '成本',
            '实际到款',
            '返佣比例',
            '返佣金额',
            '保证金（返佣5%）',
            '代理保证金',
            '抽离比例（一级）',
            '一级抽离金额',
            '抽离比例（二级）',
            '二级抽离金额',
            '实际返佣'
        ];
    }

}
