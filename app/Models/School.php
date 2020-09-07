<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
#use App\Tools\CurrentAdmin;
class School extends Model {
    //指定别的表名
    public $table = 'school';
    //时间戳设置
    public $timestamps = false;
    
    /*
     * @param  description   分校管理-添加分校方法
     * @param  参数说明       body包含以下参数[
     *     school_name       分校名称
     *     commission        佣金比例
     *     deposit           押金比例
     *     tax_point         税点比例
     *     look_all_flag     查看下属分校数据(0否1是)
     *     level             分校级别
     *     parent_id         父级分校id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-07
     * return string
     */
    public static function doInsertSchool($body=[]){
        //判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }
        
        //判断分校名称是否为空
        if(!isset($body['school_name']) || empty($body['school_name'])){
            return ['code' => 201 , 'msg' => '请输入分校名称'];
        }
        
        //判断佣金比例是否为空
        if(!isset($body['commission']) || empty($body['commission'])){
            return ['code' => 201 , 'msg' => '请输入佣金比例'];
        }
        
        //判断押金比例是否为空
        if(!isset($body['deposit']) || empty($body['deposit'])){
            return ['code' => 201 , 'msg' => '请输入押金比例'];
        }
        
        //判断税点比例是否为空
        if(!isset($body['tax_point']) || empty($body['tax_point'])){
            return ['code' => 201 , 'msg' => '请输入税点比例'];
        }

        //判断是否查看下属分校数据
        if(!isset($body['look_all_flag']) || !in_array($body['look_all_flag'] , [0,1])){
            return ['code' => 202 , 'msg' => '查看方式不合法'];
        }
        
        //判断分校级别是否合法
        if(!isset($body['level']) || !in_array($body['level'] , [1,2,3])){
            return ['code' => 202 , 'msg' => '分校级别不合法'];
        }
        
        //判断上级分校的数据是否合法
        if((isset($body['level']) && $body['level'] > 1) && isset($body['parent_id']) && $body['parent_id'] <= 0){
            return ['code' => 202 , 'msg' => '上级分校id不合法'];
        }
        
        //判断分校级别下面的分校名称是否存在
        $is_exists_school = self::where('level' , $body['level'])->where('school_name' , $body['school_name'])->where('is_del' , 0)->count();
        if($is_exists_school && $is_exists_school > 0){
            return ['code' => 203 , 'msg' => '此分校已存在'];
        }
        
        //判断上一级分校的级别是否对应
        if($body['level'] > 1){
            $info = self::where('id' , $body['parent_id'])->first();
            $level= bcadd($info['level'],1);
            if($body['level'] != $level){
                return ['code' => 203 , 'msg' => '级别不对应'];
            }
        }
        
        //组装分校数组信息
        $school_array = [
            'school_name'   =>   $body['school_name'] ,
            'level'         =>   isset($body['level']) && in_array($body['level'] , [1,2,3]) ? $body['level'] : 1 ,
            'parent_id'     =>   isset($body['level']) && $body['level'] > 1 && isset($body['parent_id']) && $body['parent_id'] > 0 ? $body['parent_id'] : 0 ,
            'tax_point'     =>   $body['tax_point'] ,
            'commission'    =>   $body['commission'] ,
            'deposit'       =>   $body['deposit'] ,
            'look_all_flag' =>   isset($body['look_all_flag']) && $body['look_all_flag'] == 1 ? 1 : 0 ,
            'create_id'     =>   0 ,
            'create_time'   =>   date('Y-m-d H:i:s')
        ];
        
        //开启事务
        DB::beginTransaction();

        //将数据插入到表中
        if(false !== self::insertGetId($school_array)){
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
     * @param  description   分校管理-修改分校方法
     * @param  参数说明       body包含以下参数[
     *     school_id         分校id
     *     school_name       分校名称
     *     commission        佣金比例
     *     deposit           押金比例
     *     tax_point         税点比例
     *     look_all_flag     查看下属分校数据(0否1是)
     *     level             分校级别
     *     parent_id         父级分校id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-07
     * return string
     */
    public static function doUpdateSchool($body=[]){
        //判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }
        
        //判断分校id是否合法
        if(!isset($body['school_id']) || empty($body['school_id']) || $body['school_id'] <= 0){
            return ['code' => 202 , 'msg' => '分校id不合法'];
        }
        
        //判断分校名称是否为空
        if(!isset($body['school_name']) || empty($body['school_name'])){
            return ['code' => 201 , 'msg' => '请输入分校名称'];
        }
        
        //判断佣金比例是否为空
        if(!isset($body['commission']) || empty($body['commission'])){
            return ['code' => 201 , 'msg' => '请输入佣金比例'];
        }
        
        //判断押金比例是否为空
        if(!isset($body['deposit']) || empty($body['deposit'])){
            return ['code' => 201 , 'msg' => '请输入押金比例'];
        }
        
        //判断税点比例是否为空
        if(!isset($body['tax_point']) || empty($body['tax_point'])){
            return ['code' => 201 , 'msg' => '请输入税点比例'];
        }

        //判断是否查看下属分校数据
        if(!isset($body['look_all_flag']) || !in_array($body['look_all_flag'] , [0,1])){
            return ['code' => 202 , 'msg' => '查看方式不合法'];
        }
        
        //判断分校级别是否合法
        if(!isset($body['level']) || !in_array($body['level'] , [1,2,3])){
            return ['code' => 202 , 'msg' => '分校级别不合法'];
        }
        
        //判断上级分校的数据是否合法
        if((isset($body['level']) && $body['level'] > 1) && isset($body['parent_id']) && $body['parent_id'] <= 0){
            return ['code' => 202 , 'msg' => '上级分校id不合法'];
        }
        
        //根据分校的id获取分校信息
        $school_info = self::where('id' , $body['school_id'])->where('is_del' , 0)->first();
        if(!$school_info || empty($school_info)){
            return ['code' => 203 , 'msg' => '此分校不存在或已删除'];
        }
        
        //判断上一级分校的级别是否对应
        if($body['level'] > 1){
            $info = self::where('id' , $body['parent_id'])->first();
            $level= bcadd($info['level'],1);
            if($body['level'] != $level){
                return ['code' => 203 , 'msg' => '级别不对应'];
            }
        }
        
        //判断分校级别下面的分校名称是否存在
        $is_exists_school = self::where('level' , $body['level'])->where('school_name' , $body['school_name'])->where('is_del' , 0)->count();
        if($is_exists_school && $is_exists_school > 0){
            //组装分校数组信息
            $school_array = [
                'tax_point'     =>   $body['tax_point'] ,
                'commission'    =>   $body['commission'] ,
                'deposit'       =>   $body['deposit'] ,
                'look_all_flag' =>   isset($body['look_all_flag']) && $body['look_all_flag'] == 1 ? 1 : 0 ,
                'update_time'   =>   date('Y-m-d H:i:s')
            ];
        } else {
            //组装分校数组信息
            $school_array = [
                'school_name'   =>   $body['school_name'] ,
                'level'         =>   isset($body['level']) && in_array($body['level'] , [1,2,3]) ? $body['level'] : 1 ,
                'parent_id'     =>   isset($body['level']) && $body['level'] > 1 && isset($body['parent_id']) && $body['parent_id'] > 0 ? $body['parent_id'] : 0 ,
                'tax_point'     =>   $body['tax_point'] ,
                'commission'    =>   $body['commission'] ,
                'deposit'       =>   $body['deposit'] ,
                'look_all_flag' =>   isset($body['look_all_flag']) && $body['look_all_flag'] == 1 ? 1 : 0 ,
                'update_time'   =>   date('Y-m-d H:i:s')
            ];
        }
        
        //开启事务
        DB::beginTransaction();

        //根据分校id更新信息
        if(false !== self::where('id',$body['school_id'])->update($school_array)){
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
     * @param  description   分校管理-分校详情方法
     * @param  参数说明       body包含以下参数[
     *     school_id         分校id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-07
     * return string
     */
    public static function getSchoolInfoById($body=[]){
        //判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }
        
        //判断分校id是否合法
        if(!isset($body['school_id']) || empty($body['school_id']) || $body['school_id'] <= 0){
            return ['code' => 202 , 'msg' => '分校id不合法'];
        }
        
        //根据id获取分校的详情
        $info = self::select('school_name', 'level' , 'tax_point','commission' , 'deposit' , 'look_all_flag' , 'parent_id')->where('id' , $body['school_id'])->where('is_del' , 0)->first();
        if($info && !empty($info)){
            return ['code' => 200 , 'msg' => '获取详情成功' , 'data' => $info];
        } else {
            return ['code' => 203 , 'msg' => '此分校不存在或已删除'];
        }
    }
}


