<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class RegionFee extends Model {
    //指定别的表名
    public $table      = 'region_registration_fee';
    //时间戳设置
    public $timestamps = false;

    /*
     * @param  description   项目管理-添加地区方法
     * @param  参数说明       body包含以下参数[
     *     project_id        项目id
     *     region_name       地区名称
     *     cost              报名费价格
     *     is_hide           是否显示/隐藏
     * ]
     * @param author    dzj
     * @param ctime     2020-09-04
     * return string
     */
    public static function doInsertRegion($body=[]){
        //判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }
        
        //判断项目id是否合法
        if(!isset($body['project_id']) || empty($body['project_id']) || $body['project_id'] <= 0){
            return ['code' => 202 , 'msg' => '项目id不合法'];
        }

        //判断地区名称是否为空
        if(!isset($body['region_name']) || empty($body['region_name'])){
            return ['code' => 201 , 'msg' => '请输入地区名称'];
        }
        
        //判断课程价格是否为空
        if(!isset($body['cost'])){
            return ['code' => 201 , 'msg' => '请输入报名价格'];
        }

        //判断是否展示是否选择
        if(isset($body['is_hide']) && !in_array($body['is_hide'] , [0,1])){
            return ['code' => 202 , 'msg' => '展示方式不合法'];
        }
        
        //判断父级id是否在表中是否存在
        $is_exists_parentId = Project::where('id' , $body['project_id'])->where('parent_id' , 0)->where('is_del' , 0)->count();
        if(!$is_exists_parentId || $is_exists_parentId <= 0){
            return ['code' => 203 , 'msg' => '此项目名称不存在'];
        }

        //判断地区名称是否存在
        $is_exists = self::where('category_id' , $body['project_id'])->where('region_name' , $body['region_name'])->where('is_del' , 0)->count();
        if($is_exists && $is_exists > 0){
            return ['code' => 203 , 'msg' => '此地区名称已存在'];
        }
        
        //组装课程数组信息
        $region_array = [
            'category_id'         =>   isset($body['project_id']) && $body['project_id'] > 0 ? $body['project_id'] : 0 ,
            'region_name'         =>   $body['region_name'] ,
            'cost'                =>   $body['cost'] ,
            'is_hide'             =>   isset($body['is_hide']) && $body['is_hide'] == 1 ? 1 : 0 ,
            'admin_id'            =>   0 ,
            'create_time'         =>   date('Y-m-d H:i:s')
        ];
        
        //开启事务
        DB::beginTransaction();

        //将数据插入到表中
        if(false !== self::insertGetId($region_array)){
            //事务提交
            DB::commit();
            return ['code' => 200 , 'msg' => '添加成功'];
        } else {
            //事务回滚
            DB::rollBack();
            return ['code' => 203 , 'msg' => '添加失败'];
        }
    }
    
    /*
     * @param  description   项目管理-修改地区方法
     * @param  参数说明       body包含以下参数[
     *     region_id         地区id
     *     region_name       地区名称
     *     cost              报名费价格
     *     is_hide           是否显示/隐藏
     *     is_del            是否删除
     * ]
     * @param author    dzj
     * @param ctime     2020-09-04
     * return string
     */
    public static function doUpdateRegion($body=[]){
        //判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }
        
        //判断地区id是否合法
        if(!isset($body['region_id']) || empty($body['region_id']) || $body['region_id'] <= 0){
            return ['code' => 202 , 'msg' => '地区id不合法'];
        }

        //判断地区名称是否为空
        if(!isset($body['region_name']) || empty($body['region_name'])){
            return ['code' => 201 , 'msg' => '请输入地区名称'];
        }
        
        //判断报名费价格是否为空
        if(!isset($body['cost'])){
            return ['code' => 201 , 'msg' => '请输入报名费价格'];
        }

        //判断是否展示是否选择
        if(isset($body['is_hide']) && !in_array($body['is_hide'] , [0,1])){
            return ['code' => 202 , 'msg' => '展示方式不合法'];
        }
        
        //判断此地区得id是否存在此地区
        $is_exists_region = self::where('id' , $body['region_id'])->count();
        if(!$is_exists_region || $is_exists_region <= 0){
            return ['code' => 203 , 'msg' => '此地区不存在'];
        }

        //判断地区名称是否存在
        /*$is_exists = self::where('region_name' , $body['region_name'])->where('is_del' , 0)->count();
        if($is_exists && $is_exists > 0){
            //组装地区数组信息
            $region_array = [
                'cost'                =>   $body['cost'] ,
                'is_hide'             =>   isset($body['is_hide']) && $body['is_hide'] == 1 ? 1 : 0 ,
                'is_del'              =>   isset($body['is_del']) && $body['is_del'] == 1 ? 1 : 0 ,
                'update_time'         =>   date('Y-m-d H:i:s')
            ];
        } else {
            //组装地区数组信息
            $region_array = [
                'region_name'         =>   $body['region_name'] ,
                'cost'                =>   $body['cost'] ,
                'is_hide'             =>   isset($body['is_hide']) && $body['is_hide'] == 1 ? 1 : 0 ,
                'is_del'              =>   isset($body['is_del']) && $body['is_del'] == 1 ? 1 : 0 ,
                'update_time'         =>   date('Y-m-d H:i:s')
            ];
        }*/
        //组装地区数组信息
        $region_array = [
            'region_name'         =>   $body['region_name'] ,
            'cost'                =>   $body['cost'] ,
            'is_hide'             =>   isset($body['is_hide']) && $body['is_hide'] == 1 ? 1 : 0 ,
            'is_del'              =>   isset($body['is_del']) && $body['is_del'] == 1 ? 1 : 0 ,
            'update_time'         =>   date('Y-m-d H:i:s')
        ];
        
        //开启事务
        DB::beginTransaction();

        //根据地区id更新信息
        if(false !== self::where('id',$body['region_id'])->update($region_array)){
            //事务提交
            DB::commit();
            return ['code' => 200 , 'msg' => '修改成功'];
        } else {
            //事务回滚
            DB::rollBack();
            return ['code' => 203 , 'msg' => '修改失败'];
        }
    }
    
    /*
     * @param  description   项目管理-地区列表接口
     * @param  参数说明       body包含以下参数[
     *     project_id        项目id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-03
     * return string
     */
    public static function getRegionList($body=[]){
        //判断项目的id是否为空
        if(!isset($body['project_id']) || $body['project_id'] <= 0){
            return ['code' => 202 , 'msg' => '项目id不合法'];
        }
        
        //通过项目的id获取地区列表
        $region_list = self::select('id as region_id' , 'region_name')->where('category_id' , $body['project_id'])->where('is_del' , 0)->get();
        return ['code' => 200 , 'msg' => '获取地区列表成功' , 'data' => $region_list];
    }
}
