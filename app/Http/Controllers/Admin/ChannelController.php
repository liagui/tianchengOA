<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Admin as Adminuser;
use App\Models\Roleauth;
use App\Models\Authrules;
use App\Models\School;
use App\Models\PaySet;
use App\Models\Channel;
use Illuminate\Support\Facades\Redis;
use App\Tools\CurrentAdmin;
use Illuminate\Support\Facades\Validator;
use App\Models\AdminLog;
use Illuminate\Support\Facades\DB;
class ChannelController extends Controller {

     /*
     * @param  description   获取支付配置列表
     * @param  参数说明       body包含以下参数[
     *     search       搜索条件 （非必填项）
     *     page         当前页码 （不是必填项）
     *     pagesize     每页显示条件 （不是必填项）
     * ]
     * @param author    lys
     * @param ctime     2020-05-27
     */
    public function getList(){
        $result = Channel::getList(self::$accept_data);
        if($result['code'] == 200){
            return response()->json($result);
        }else{
            return response()->json($result);
        }
    }
    /*
     * @param  description   添加支付通道
     * @param  参数说明       body包含以下参数[
     *    aislename       通过类型
     *     type         支付类型
     * ]
     * @param author    lys
     * @param ctime     2020-09-02
     */
    public function doChannelInsert(){
        $data = self::$accept_data;
        $validator = Validator::make($data,
                [
                    'channel_name' => 'required',
                    'channel_type'=>'required',
                ],
                Channel::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
        $result = Channel::doChannelInsert($data);
        if($result['code'] == 200){
            return response()->json($result);
        }else{
            return response()->json($result);
        }
    }
     /*
     * @param  description   编辑支付通道（获取）
     * @param  参数说明       body包含以下参数[
     *     id     通道id
     *
     * ]
     * @param author    lys
     * @param ctime     2020-09-02
     */
   public function getChannelPayById(){
        $data = self::$accept_data;
        $validator = Validator::make($data,
                [
                    'id' => 'required|integer',
                ],
                Channel::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
        $result = Channel::getChannelPayById($data);
        if($result['code'] == 200){
            return response()->json($result);
        }else{
            return response()->json($result);
        }
    }
    /*
     * @param  description   编辑支付通道
     * @param  参数说明       body包含以下参数[
     *     id     通道id
     * ]
     * @param author    lys
     * @param ctime     2020-09-02
     */
   public function doUpdateChannelPay(){
        $data = self::$accept_data;
        $validator = Validator::make($data,
                [
                    'id' => 'required|integer',
                    'channel_name' => 'required',
                    'channel_type'=>'required',
                ],
                Channel::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
        $result = Channel::doUpdateChannelPay($data);
        if($result['code'] == 200){
            return response()->json($result);
        }else{
            return response()->json($result);
        }
    }
    /*
     * @param  description   选中支付通道
     * @param  参数说明       body包含以下参数[
     *     id     通道id
     * ]
     * @param author    lys
     * @param ctime     2020-09-03
     */
   public function doUseChannelPay(){
        $data = self::$accept_data;
        $validator = Validator::make($data,
                [
                   'id' => 'required|integer',
                ],
                Channel::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
        DB::beginTransaction();
        try{
            $count = Channel::where(['is_del'=>0,'is_forbid'=>0])->where('id','!=',$data['id'])->count();
            if($count>=1){
                $noUseRes = Channel::where('id','!=',$data['id'])->where(['is_del'=>0,'is_forbid'=>0])->update(['is_use'=>1,'update_time'=>date('Y-m-d H:i:s')]);
                if(!$noUseRes){
                    DB::rollback();
                    return response()->json(['code'=>203,'msg'=>'选择失败']);
                }
            }
        	$UseRes = Channel::where(['id'=>$data['id'],'is_del'=>0,'is_forbid'=>0])->update(['is_use'=>0,'update_time'=>date('Y-m-d H:i:s')]);
        	if(!$UseRes){
        		DB::rollback();
        		return response()->json(['code'=>203,'msg'=>'选择失败']);
        	}else{
                DB::commit();
                return response()->json(['code'=>200,'msg'=>'选择成功']);
            }
    	} catch (Exception $e) {
            return ['code' => 500 , 'msg' => $ex->getMessage()];
        }
    }


	/*
	 * @param  description   选中支付通道
	 * @param  参数说明       body包含以下参数[
	 *     id     通道id
	 * ]
	 * @param author    lys
	 * @param ctime     2020-09-03
	 */
    public function getPaywayList(){
        $arr = [
                [
                    'id'=>1,
                    'logo_url' =>'http://longdeapi.oss-cn-beijing.aliyuncs.com/upload/2020-11-10/160497962990485faa0bade8ad0.png',
                    'payway' =>'支付宝支付',
                ],
                [
                    'id'=>2,
                    'logo_url' =>'http://longdeapi.oss-cn-beijing.aliyuncs.com/upload/2020-11-10/160497965917615faa0bcb6ffce.png',
                    'payway' =>'微信支付',
                ],
                [
                    'id'=>3,
                    'logo_url' =>'http://longdeapi.oss-cn-beijing.aliyuncs.com/upload/2020-11-10/160497969336945faa0bed2f06f.png',
                    'payway' =>'汇聚支付',
                ],
                [
                    'id'=>4,
                    'logo_url' =>'http://longdeapi.oss-cn-beijing.aliyuncs.com/upload/2020-11-10/160497974528305faa0c21b3a74.jpg',
                    'payway' =>'银联支付',
                ],
                // [
                //     'id'=>5,
                //     'logo_url' =>'http://longdeapi.oss-cn-beijing.aliyuncs.com/upload/2020-11-10/16049797766645faa0c4014cec.png',
                //     'payway' =>'汇付支付',
                // ]
        ];
        return response()->json(['code'=>200,'msg'=>'success','data'=>$arr]);
    }

}
