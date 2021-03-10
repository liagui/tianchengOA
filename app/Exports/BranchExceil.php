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
    public function collection()
    {
        $body = $this->data;
        //新数组赋值
        $array = [];
        //学校id
        $school_id = [];
        if (isset($body['school_id'])) {
            $school_id[0]=$body['school_id'];
//            $school_id = School::select('id')->where('school_name', 'like', '%' . $body['school_name'] . '%')->where('is_del', 0)->get();
        }

        if (!empty($body['search_time'])) {
//            $create_time = json_decode($body['search_time'], true);
            $state_time = $body['search_time'][0] . " 00:00:00";
            $end_time = $body['search_time'][1] . " 23:59:59";
            // 获取数量
            $count1 = DB::table('school')->selectRaw("count(school.id) as t_count")
                ->leftjoin("pay_order_inside", function ($join) {
                    $join->on('school.id', '=', 'pay_order_inside.school_id');
                })
                ->where('school.is_del', 0)->where(function ($query) use ($body, $school_id, $state_time, $end_time) {
                    //判断分校id是否为空和合法
                    if (!empty($school_id)) {
                        $query->whereIn('school.id', $school_id);
                    }
                    //获取日期
                    $query->whereBetween('pay_order_inside.comfirm_time', [$state_time, $end_time]);
                })->groupBy(DB::raw('school.id'))->get()->count();
            $count2 = DB::table('school')->selectRaw("count(school.id) as t_count")
                ->leftjoin("refund_order", function ($join) {
                    $join->on('school.id', '=', 'refund_order.school_id');
                })->where('refund_order.refund_plan', 2)->where('school.is_del', 0)->where(function ($query) use ($body, $school_id, $state_time, $end_time) {
                    //判断分校id是否为空和合法
                    if (!empty($school_id)) {
                        $query->whereIn('school.id', $school_id);
                    }
                    //获取日期
                    $query->whereBetween('refund_order.refund_time', [$state_time, $end_time]);
                })->groupBy(DB::raw('school.id'))->get()->count();
            $count = $count1 + $count2;
            // 判断数量是否大于0
            if ($count >= 0) {
                //新数组赋值
                $array = [];
                //获取分校业绩列表
                $list = DB::table('school')->selectRaw('
               any_value(school.id) as school_id ,
               any_value(count(school.id)) as t_count ,
               any_value(school.one_extraction_ratio) as one_extraction_ratio ,
               any_value(school.two_extraction_ratio) as two_extraction_ratio ,
               any_value(school.school_name) as school_name ,
               any_value(school.level) as level ,
               any_value(school.tax_point) as tax_point ,
               any_value(school.commission) as commission ,
               any_value(school.deposit) as deposit ,
               any_value(sum(pay_order_inside.after_tax_amount)) as after_tax_amount,
               any_value(pay_order_inside.sum_Price) as sum_Price,
               any_value(sum(if(pay_order_inside.confirm_status = 2 ,
               pay_order_inside.pay_price , 0))) as pay_price,
               any_value(sum(pay_order_inside.agent_margin)) as agent_margin,
               any_value(pay_order_inside.first_out_of_amount) as first_out_of_amount,
               any_value(pay_order_inside.second_out_of_amount) as second_out_of_amount,
               any_value(pay_order_inside.education_id) as education_id,
               any_value(pay_order_inside.major_id) as major_id,
               any_value(sum(pay_order_inside.sign_Price)) as sign_Price'
                )->leftjoin("pay_order_inside", function ($join) {
                    $join->on('school.id', '=', 'pay_order_inside.school_id');
                })
                    ->where('school.is_del', 0)->where(function ($query) use ($body, $school_id) {
                        //判断分校id是否为空和合法
                        if (!empty($school_id)) {
                            $query->whereIn('school.id', $school_id);
                        }
                        //获取日期
                        if (isset($body['search_time']) && !empty($body['search_time'])) {
                            $state_time = $body['search_time'][0] . " 00:00:00";
                            $end_time = $body['search_time'][1] . " 23:59:59";
                            $query->whereBetween('pay_order_inside.comfirm_time', [$state_time, $end_time]);
                        }
                    })->orderByDesc('school.create_time')->groupBy(DB::raw('school.id'))->get()->toArray();
                if (empty($list)) {
                    $list = DB::table('school')->selectRaw('
                           any_value(school.id) as school_id ,
                           any_value(count(school.id)) as t_count ,
                           any_value(school.one_extraction_ratio) as one_extraction_ratio ,
                           any_value(school.two_extraction_ratio) as two_extraction_ratio ,
                           any_value(school.school_name) as school_name ,
                           any_value(school.level) as level ,
                           any_value(school.tax_point) as tax_point ,
                           any_value(school.commission) as commission ,
                           any_value(school.deposit) as deposit,
                           any_value(0) as after_tax_amount,
                           any_value(0) as sum_Price,
                           any_value(0) as pay_price,
                           any_value(0) as agent_margin,
                           any_value(0) as first_out_of_amount,
                           any_value(0) as second_out_of_amount,
                           any_value(0) as education_id,
                           any_value(0) as major_id,
                           any_value(0) as sign_Price'
                    )->leftjoin("refund_order", function ($join) {
                        $join->on('school.id', '=', 'refund_order.school_id');
                    })
                        ->where('refund_order.refund_plan', 2)->where('school.is_del', 0)->where(function ($query) use ($body, $school_id) {
                            //判断分校id是否为空和合法
                            if (!empty($school_id)) {
                                $query->whereIn('school.id', $school_id);
                            }
                            //获取日期
                            if (isset($body['search_time']) && !empty($body['search_time'])) {
                                $state_time = $body['search_time'][0] . " 00:00:00";
                                $end_time = $body['search_time'][1] . " 23:59:59";
                                $query->whereBetween('refund_order.refund_time', [$state_time, $end_time]);
                            }
                        })->orderByDesc('school.create_time')->groupBy(DB::raw('school.id'))->get()->toArray();
                }
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

                    //查询学校报名金额
                    $singschoolprice = Refund_order::where(['school_id' => $v['school_id'], 'refund_plan' => 2])->whereBetween('refund_time', [$state_time, $end_time])->sum('sing_price');
                    $v['singschoolprice'] = sprintf("%01.2f", $singschoolprice);
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
                    $enroll_number = Pay_order_inside ::where(function ($query) use ($body) {
                        //分校查询
                        $query->where('school_id', '=', $body['school_id'])->whereIn('confirm_order_type', [2, 3]);

                        //获取日期
                        if (isset($body['search_time']) && !empty($body['search_time'])) {
                            $state_time = $body['search_time'][0] . " 00:00:00";
                            $end_time = $body['search_time'][1] . " 23:59:59";
                            $query->whereBetween('pay_order_inside.comfirm_time', [$state_time, $end_time]);
                        }
                    })->count();
                    $chengben_number = Pay_order_inside::where(function ($query) use ($body) {
                        //分校查询
                        $query->where('school_id', '=', $body['school_id'])->where('education_id', '>', 0)->where('major_id', '>', 0);

                        //获取日期
                        if (isset($body['search_time']) && !empty($body['search_time'])) {
                            $state_time = $body['search_time'][0] . " 00:00:00";
                            $end_time = $body['search_time'][1] . " 23:59:59";
                            $query->whereBetween('pay_order_inside.comfirm_time', [$state_time, $end_time]);
                        }
                    })->count();
                    $order_number = $enroll_number + $chengben_number;
                    $sum_cost = $v['sign_Price'];

                    //实际到款=税后金额-成本
                    $actual_receipt = sprintf("%.2f", $after_tax_amount - $sum_cost);

                    //返佣比例=后台分校管理中佣金比例
                    $commission_rebate = $v['commission'];

                    //返佣金额=实际到款*返佣比例
                    $commission_money = sprintf("%.2f", $actual_receipt * ($commission_rebate / 100));

                    //保证金=返佣金额*后台分校管理中押金比例
                    $bond = sprintf("%.2f", $commission_money * ($v['deposit'] / 100));


                    //一级分校无一级抽离比例、押金和二级抽离比例、押金
                    if ($v['level'] == 1) {
                        $first_out_of_amount = '';
                        $second_out_of_amount = '';
                        $first_out_of_money = '';
                        $second_out_of_money = '';
                        //代理保证金
                        $agent_margin = 0;
                        //所有一级的抽离金额
                        $ononepricechouli = 0;

                        //一级学校的退费 分校的退费订单
                        $returnschoolprice = Refund_order::where(['school_id' => $v['school_id'], 'refund_plan' => 2])->whereBetween('refund_time', [$state_time, $end_time])->sum('reality_price');
                        $returnschoolprice = sprintf("%01.2f", $returnschoolprice * ($commission_rebate / 100));

                        //一级分校下面的所有二级分校
                        $seond_school_id = School::select('id', 'deposit', 'tax_point', 'one_extraction_ratio')->where('parent_id', $v['school_id'])->where('level', 2)->get()->toArray();
                        $seond_school_ids = array_column($seond_school_id, 'id');
                        if (!empty($seond_school_id)) {
                            //循环分校 查询每个分校一级抽离*押金比例
                            $firstprice = 0;
                            foreach ($seond_school_id as $onek => $onev) {
                                //到账
                                $oneprices = Pay_order_inside::where(['school_id' => $onev['id'], 'pay_status' => 1, 'confirm_status' => 2])->whereBetween('comfirm_time', [$state_time, $end_time])->sum('pay_price');
                                //扣税=到账金额*扣税比例
                                $tax_deductions = sprintf("%.2f", $oneprices * ($onev['tax_point'] / 100));
                                //税后金额=到账金额-扣税
                                $after_tax_amounts = $oneprices - $tax_deductions;
                                //成本
                                $sum_costs = Pay_order_inside::where(['school_id' => $onev['id'], 'pay_status' => 1, 'confirm_status' => 2])->whereBetween('comfirm_time', [$state_time, $end_time])->sum('sign_Price');
                                //实际到款=税后金额-成本
                                $actual_receiptss = sprintf("%.2f", $after_tax_amounts - $sum_costs);
                                //抽离金额
                                $oneschoolprice = $actual_receiptss * ($onev['one_extraction_ratio'] / 100);
                                $ononepricechouli = $ononepricechouli + $oneschoolprice;
                                //代理保证金
                                $onechouli = $oneschoolprice * ($onev['deposit'] / 100);
                                $firstprice = $firstprice + $onechouli;
                                //学校退费  * 一级抽离比例
                                $tworeturnschoolprice = Refund_order::where(['school_id' => $onev['id'], 'refund_plan' => 2])->whereBetween('refund_time', [$state_time, $end_time])->sum('reality_price');
                                $returnschoolprice = $returnschoolprice + sprintf("%01.2f", $tworeturnschoolprice * ($onev['one_extraction_ratio'] / 100));
                            }
                            $seedprice = 0;
                            $three_school_id = School::select('id', 'deposit', 'tax_point', 'one_extraction_ratio')->whereIn('parent_id', $seond_school_ids)->where('level', 3)->get()->toArray();
                            if (!empty($three_school_id)) {
                                foreach ($three_school_id as $twok => $twov) {
                                    //到账
                                    $onepricess = Pay_order_inside::where(['school_id' => $twov['id'], 'pay_status' => 1, 'confirm_status' => 2])->whereBetween('comfirm_time', [$state_time, $end_time])->sum('pay_price');
                                    //扣税=到账金额*扣税比例
                                    $tax_deductionss = sprintf("%.2f", $onepricess * ($twov['tax_point'] / 100));
                                    //税后金额=到账金额-扣税
                                    $after_tax_amountss = $onepricess - $tax_deductionss;
                                    //成本
                                    $sum_costss = Pay_order_inside::where(['school_id' => $twov['id'], 'pay_status' => 1, 'confirm_status' => 2])->whereBetween('comfirm_time', [$state_time, $end_time])->sum('sign_Price');
                                    //实际到款=税后金额-成本
                                    $actual_receipts = sprintf("%.2f", $after_tax_amountss - $sum_costss);
                                    //抽离金额
                                    $oneschoolprices = $actual_receipts * ($twov['one_extraction_ratio'] / 100);
                                    $ononepricechouli = $ononepricechouli + $oneschoolprices;
                                    //代理保证金
                                    $twoschoolprice = $oneschoolprices * ($twov['deposit'] / 100);
                                    $seedprice = $seedprice + $twoschoolprice;
                                    //学校退费  * 一级抽离比例
                                    $threereturnschoolprice = Refund_order::where(['school_id' => $twov['id'], 'refund_plan' => 2])->whereBetween('refund_time', [$state_time, $end_time])->sum('reality_price');
                                    $returnschoolprice = $returnschoolprice + sprintf("%01.2f", $threereturnschoolprice * ($twov['one_extraction_ratio'] / 100));
                                }
                            }
                            $agent_margin = sprintf("%01.2f", $firstprice) + sprintf("%01.2f", $seedprice);
                        }

                        //返佣 - 保证金-代理保证金 + 所有抽离金额
                        //实际到款金额是负数  （到账金额*（1-税点）-成本）*返佣比例+保证金+三级分校一级抽离金额+二级分校一级抽离金额-代理保证金-退费金额如果
                        if ($actual_receipt < 0) {
                            $suidian = 100 - $tax_deduction_ratio;
                            $onemoneys = sprintf("%01.2f", $payment_performance * ($suidian / 100) - $sum_cost);
                            $fanyongtwos = sprintf("%01.2f", $onemoneys * ($commission_rebate / 100));
                            $actual_commission_refund = sprintf("%01.2f", $fanyongtwos + $bond + $ononepricechouli - abs($agent_margin) - abs($ononepricechouli) - abs($returnschoolprice));
                        } else {
                            $actual_commission_refund = sprintf("%01.2f", $commission_money - abs($bond) - abs($agent_margin) + $ononepricechouli - abs($returnschoolprice));
                        }
                    } elseif ($v['level'] == 2) {
                        //二级分校的一级抽离比例=后台分校管理中一级抽离比例
                        //二级分校的一级抽离金额=二级分校的一级抽离比例*实际到款
                        //二级分校无二级抽离比例、押金

                        //二级分校的一级抽离比例一级抽离比例
                        $first_out_of_amount = $v['one_extraction_ratio'] && !empty($v['one_extraction_ratio']) ? $v['one_extraction_ratio'] : '';
                        $first_out_of_money = sprintf("%01.2f", $actual_receipt * ($first_out_of_amount / 100));
                        $second_out_of_amount = '';
                        $second_out_of_money = '';
                        //代理保证金
                        $agent_margin = 0;
                        //二级返佣金额
                        $twochouliprice = 0;
                        //二级分校退费金额   再乘返佣比例
                        $returnschoolprice = Refund_order::where(['school_id' => $v['school_id'], 'refund_plan' => 2])->whereBetween('refund_time', [$state_time, $end_time])->sum('reality_price');
                        $returnschoolprice = sprintf("%01.2f", $returnschoolprice * ($commission_rebate / 100));

                        //二级下面的所有三级分校
                        $three_school_id = School::select('id', 'deposit', 'tax_point', 'one_extraction_ratio', 'two_extraction_ratio')->where('parent_id', $v['school_id'])->where('level', 3)->get()->toArray();
                        if (!empty($three_school_id)) {
                            foreach ($three_school_id as $onek => $onev) {
                                //到账
                                $threeprice = Pay_order_inside::where(['school_id' => $onev['id'], 'pay_status' => 1, 'confirm_status' => 2])->whereBetween('comfirm_time', [$state_time, $end_time])->sum('pay_price');
                                //扣税=到账金额*扣税比例
                                $threetax_deductionss = sprintf("%.2f", $threeprice * ($onev['tax_point'] / 100));
                                //税后金额=到账金额-扣税
                                $threeafter_tax_amountss = $threeprice - $threetax_deductionss;
                                //成本
                                $threesum_costss = Pay_order_inside::where(['school_id' => $onev['id'], 'pay_status' => 1, 'confirm_status' => 2])->whereBetween('comfirm_time', [$state_time, $end_time])->sum('sign_Price');
                                //实际到款=税后金额-成本
                                $actual_receipts = sprintf("%.2f", $threeafter_tax_amountss - $threesum_costss);
                                //二级抽离金额
                                $twochouliprices = $actual_receipts * ($onev['two_extraction_ratio'] / 100);
                                $twochouliprice = $twochouliprice + $twochouliprices;
                                //代理保证金
                                $twoschoolprice = $twochouliprices * ($onev['deposit'] / 100);
                                $agent_margin = $agent_margin + sprintf("%01.2f", $twoschoolprice);
                                //算出每个三级分校的退费
                                $threereturnschoolprice = Refund_order::where(['school_id' => $onev['id'], 'refund_plan' => 2])->whereBetween('refund_time', [$state_time, $end_time])->sum('reality_price');
                                $returnschoolprice = $returnschoolprice + sprintf("%01.2f", $threereturnschoolprice * ($onev['two_extraction_ratio'] / 100));
                            }
                        }
                        //返佣 - 保证金-代理保证金 + 所有抽离金额
                        //到账金额是负数  （到账金额*（1-税点）-成本）*返佣比例+保证金+三级分校一级抽离金额-代理保证金-退费金额如果
                        if ($actual_receipt < 0) {
                            $suidian = 100 - $tax_deduction_ratio;
                            $onemoneys = sprintf("%01.2f", $payment_performance * ($suidian / 100) - $sum_cost);
                            $fanyongtwos = sprintf("%01.2f", $onemoneys * ($commission_rebate / 100));
                            $actual_commission_refund = sprintf("%01.2f", $fanyongtwos + $bond + $twochouliprice - abs($agent_margin) - abs($twochouliprice) - abs($returnschoolprice));
                        } else {
                            $actual_commission_refund = sprintf("%01.2f", $commission_money - abs($bond) - abs($agent_margin) + $twochouliprice - abs($returnschoolprice));
                        }
                    } elseif ($v['level'] == 3) {
                        //三级分校的一级抽离比例=后台分校管理中一级抽离比例
                        //三级分校的一级抽离金额=三级分校的一级抽离比例*实际到款
                        //三级分校的二级抽离比例=后台分校管理中二级抽离比例
                        //三级分校的二级抽离金额=三级分校的二级抽比例*实际到款
                        //三级分校的实际返佣=三级分校的返佣金额-三级分校的保证金-三级分校退费*三级分校返佣比例
                        $first_out_of_amount = $v['one_extraction_ratio'] && !empty($v['one_extraction_ratio']) ? $v['one_extraction_ratio'] : '';
                        $first_out_of_money = sprintf("%01.2f", $actual_receipt * ($first_out_of_amount / 100));

                        //二级抽离比例
                        $second_out_of_amount = $v['two_extraction_ratio'] && !empty($v['two_extraction_ratio']) ? $v['two_extraction_ratio'] : '';
                        $second_out_of_money = sprintf("%01.2f", $actual_receipt * ($second_out_of_amount / 100));
                        //三级分校无代理保证金
                        $agent_margin = '';
                        //返佣 - 保证金-代理保证金 + 所有抽离金额
                        //退费金额
                        $returnschoolprice = Refund_order::where(['school_id' => $v['school_id'], 'refund_plan' => 2])->whereBetween('refund_time', [$state_time, $end_time])->sum('reality_price');
                        //退费金额 * 返佣比例
                        $returnschoolprice = sprintf("%01.2f", $returnschoolprice * ($commission_rebate / 100));

                        //到账金额是负数  （到账金额*（1-税点）-成本）*返佣比例+保证金-退费金额如果
                        if ($actual_receipt < 0) {
                            $suidian = 100 - $tax_deduction_ratio;
                            $onemoneys = sprintf("%01.2f", $payment_performance * ($suidian / 100) - $sum_cost);
                            $fanyongtwos = sprintf("%01.2f", $onemoneys * ($commission_rebate / 100));
                            $actual_commission_refund = sprintf("%01.2f", $fanyongtwos + $bond - abs($returnschoolprice));
                        } else {
                            $actual_commission_refund = sprintf("%01.2f", $commission_money - abs($bond) - abs($returnschoolprice));
                        }
                    }


                    //数组赋值
                    $array[] = [
                        'first_school_name' => isset($first_school_name) ? $first_school_name : '', //一级分校
                        'two_school_name' => isset($two_school_name) ? $two_school_name : '', //二级分校
                        'three_school_name' => isset($three_school_name) ? $three_school_name : '',//三级分校
                        'payment_performance' => $payment_performance,//到款金额
                        'tax_deduction_ratio' => $tax_deduction_ratio,//扣税比例
                        'after_tax_amount' => $after_tax_amount,//税后金额
                        'order_number' => $order_number,//订单数
                        'sum_cost' => $sum_cost,//成本
                        'actual_receipt' => $actual_receipt,   //实际到款
                        'commission_rebate' => $commission_rebate,//返佣比例
                        'commission_money' => $commission_money,//返佣金额
                        'bond' => $bond,//保证金
                        'agent_margin' => $agent_margin,//代理保证金
                        'first_out_of_amount' => isset($first_out_of_amount) ? $first_out_of_amount : '',//一级抽离比例
                        'first_out_of_money' => isset($first_out_of_money) ? $first_out_of_money : '',//一级抽离金额
                        'second_out_of_amount' => isset($second_out_of_amount) ? $second_out_of_amount : '',//二级抽离比例
                        'second_out_of_money' => isset($second_out_of_money) ? $second_out_of_money : '',//二级抽离金额
                        'returnschoolprice' => $returnschoolprice,//课程金额退费
                        'actual_commission_refund' => $actual_commission_refund//实际返佣
                    ];
                }
                return collect($array);
            }
        }else{
            return collect($array);
        }
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
            '课程退费金额',
            '实际返佣'
        ];
    }

}
