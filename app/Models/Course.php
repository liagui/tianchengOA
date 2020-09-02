<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use App\Models\Project;

class Course extends Model {
    //指定别的表名
    public $table      = 'course';
    //时间戳设置
    public $timestamps = false;

    /*
     * @param  description   项目管理-添加课程方法
     * @param  参数说明       body包含以下参数[
     *     parent_id         项目id
     *     child_id          学科id
     *     course_name       课程名称
     *     course_price      课程价格
     *     hide_flag         是否显示/隐藏
     * ]
     * @param author    dzj
     * @param ctime     2020-09-02
     * return string
     */
    public static function doInsertCourse($body=[]){
        //判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }
        
        //判断分类父级id是否合法
        if(!isset($body['parent_id']) || empty($body['parent_id']) || $body['parent_id'] <= 0){
            return ['code' => 202 , 'msg' => '项目id不合法'];
        }
        
        //判断分类子级id是否合法
        if(!isset($body['child_id']) || empty($body['child_id']) || $body['child_id'] <= 0){
            return ['code' => 202 , 'msg' => '学科id不合法'];
        }

        //判断课程名称是否为空
        if(!isset($body['course_name']) || empty($body['course_name'])){
            return ['code' => 201 , 'msg' => '请输入课程名称'];
        }
        
        //判断课程价格是否为空
        if(!isset($body['course_price'])){
            return ['code' => 201 , 'msg' => '请输入课程价格'];
        }

        //判断是否展示是否选择
        if(isset($body['hide_flag']) && !in_array($body['hide_flag'] , [0,1])){
            return ['code' => 202 , 'msg' => '展示方式不合法'];
        }
        
        //判断父级id是否在表中是否存在
        $is_exists_parentId = Project::where('id' , $body['parent_id'])->where('parent_id' , 0)->where('status' , 0)->count();
        if(!$is_exists_parentId || $is_exists_parentId <= 0){
            return ['code' => 203 , 'msg' => '此项目名称不存在'];
        }
        
        //判断子级id是否在表中是否存在
        $is_exists_childId = Project::where('id' , $body['child_id'])->where('parent_id' , $body['parent_id'])->where('status' , 0)->count();
        if(!$is_exists_childId || $is_exists_childId <= 0){
            return ['code' => 203 , 'msg' => '此学科名称不存在'];
        }

        //判断课程名称是否存在
        $is_exists = self::where('course_name' , $body['course_name'])->where('del_flag' , 0)->count();
        if($is_exists && $is_exists > 0){
            return ['code' => 203 , 'msg' => '此课程名称已存在'];
        }
        
        //组装课程数组信息
        $course_array = [
            'category_one_id'     =>   isset($body['parent_id']) && $body['parent_id'] > 0 ? $body['parent_id'] : 0 ,
            'category_tow_id'     =>   isset($body['child_id']) && $body['child_id'] > 0 ? $body['child_id'] : 0 ,
            'course_name'         =>   $body['course_name'] ,
            'price'               =>   $body['course_price'] ,
            'hide_flag'           =>   isset($body['hide_flag']) && $body['hide_flag'] == 1 ? 1 : 0 ,
            'admin_id'            =>   0 ,
            'create_time'         =>   date('Y-m-d H:i:s')
        ];
        
        //开启事务
        DB::beginTransaction();

        //将数据插入到表中
        if(false !== self::insertGetId($course_array)){
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
     * @param  description   项目管理-修改课程方法
     * @param  参数说明       body包含以下参数[
     *     course_id         课程id
     *     parent_id         项目id
     *     child_id          学科id
     *     course_name       课程名称
     *     course_price      课程价格
     *     hide_flag         是否显示/隐藏
     *     del_flag          是否删除(是否删除1已删除)
     * ]
     * @param author    dzj
     * @param ctime     2020-09-02
     * return string
     */
    public static function doUpdateCourse($body=[]){
        //判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }
        
        //判断课程id是否合法
        if(!isset($body['course_id']) || empty($body['course_id']) || $body['course_id'] <= 0){
            return ['code' => 202 , 'msg' => '课程id不合法'];
        }
        
        //判断分类父级id是否合法
        if(!isset($body['parent_id']) || empty($body['parent_id']) || $body['parent_id'] <= 0){
            return ['code' => 202 , 'msg' => '项目id不合法'];
        }
        
        //判断分类子级id是否合法
        if(!isset($body['child_id']) || empty($body['child_id']) || $body['child_id'] <= 0){
            return ['code' => 202 , 'msg' => '学科id不合法'];
        }

        //判断课程名称是否为空
        if(!isset($body['course_name']) || empty($body['course_name'])){
            return ['code' => 201 , 'msg' => '请输入课程名称'];
        }
        
        //判断课程价格是否为空
        if(!isset($body['course_price'])){
            return ['code' => 201 , 'msg' => '请输入课程价格'];
        }

        //判断是否展示是否选择
        if(isset($body['hide_flag']) && !in_array($body['hide_flag'] , [0,1])){
            return ['code' => 202 , 'msg' => '展示方式不合法'];
        }
        
        //判断此课程得id是否存在此课程
        $is_exists_course = self::where('id' , $body['course_id'])->count();
        if(!$is_exists_course || $is_exists_course <= 0){
            return ['code' => 203 , 'msg' => '此课程不存在'];
        }
        
        //判断父级id是否在表中是否存在
        $is_exists_parentId = Project::where('id' , $body['parent_id'])->where('parent_id' , 0)->where('status' , 0)->count();
        if(!$is_exists_parentId || $is_exists_parentId <= 0){
            return ['code' => 203 , 'msg' => '此项目名称不存在'];
        }
        
        //判断子级id是否在表中是否存在
        $is_exists_childId = Project::where('id' , $body['child_id'])->where('parent_id' , $body['parent_id'])->where('status' , 0)->count();
        if(!$is_exists_childId || $is_exists_childId <= 0){
            return ['code' => 203 , 'msg' => '此学科名称不存在'];
        }

        //判断课程名称是否存在
        $is_exists = self::where('course_name' , $body['course_name'])->where('del_flag' , 0)->count();
        if($is_exists && $is_exists > 0){
            //组装课程数组信息
            $course_array = [
                'category_one_id'     =>   isset($body['parent_id']) && $body['parent_id'] > 0 ? $body['parent_id'] : 0 ,
                'category_tow_id'     =>   isset($body['child_id']) && $body['child_id'] > 0 ? $body['child_id'] : 0 ,
                'price'               =>   $body['course_price'] ,
                'hide_flag'           =>   isset($body['hide_flag']) && $body['hide_flag'] == 1 ? 1 : 0 ,
                'update_time'         =>   date('Y-m-d H:i:s')
            ];
        } else {
            //组装课程数组信息
            $course_array = [
                'category_one_id'     =>   isset($body['parent_id']) && $body['parent_id'] > 0 ? $body['parent_id'] : 0 ,
                'category_tow_id'     =>   isset($body['child_id']) && $body['child_id'] > 0 ? $body['child_id'] : 0 ,
                'course_name'         =>   $body['course_name'] ,
                'price'               =>   $body['course_price'] ,
                'hide_flag'           =>   isset($body['hide_flag']) && $body['hide_flag'] == 1 ? 1 : 0 ,
                'update_time'         =>   date('Y-m-d H:i:s')
            ];
        }
        
        //开启事务
        DB::beginTransaction();

        //根据课程id更新信息
        if(false !== self::where('id',$body['course_id'])->update($course_array)){
            //事务提交
            DB::commit();
            return ['code' => 200 , 'msg' => '修改成功'];
        } else {
            //事务回滚
            DB::rollBack();
            return ['code' => 203 , 'msg' => '修改失败'];
        }
    }
}
