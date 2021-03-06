<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use App\Models\Project;
use App\Models\AdminLog;

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
     *     is_hide           是否显示/隐藏
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
        if(isset($body['is_hide']) && !in_array($body['is_hide'] , [0,1])){
            return ['code' => 202 , 'msg' => '展示方式不合法'];
        }
        
        //判断父级id是否在表中是否存在
        $is_exists_parentId = Project::where('id' , $body['parent_id'])->where('parent_id' , 0)->where('is_del' , 0)->count();
        if(!$is_exists_parentId || $is_exists_parentId <= 0){
            return ['code' => 203 , 'msg' => '此项目名称不存在'];
        }
        
        //判断子级id是否在表中是否存在
        $is_exists_childId = Project::where('id' , $body['child_id'])->where('parent_id' , $body['parent_id'])->where('is_del' , 0)->count();
        if(!$is_exists_childId || $is_exists_childId <= 0){
            return ['code' => 203 , 'msg' => '此学科名称不存在'];
        }

        //判断课程名称是否存在
        $is_exists = self::where('category_one_id' , $body['parent_id'])->where('category_tow_id' , $body['child_id'])->where('course_name' , $body['course_name'])->where('is_del' , 0)->count();
        if($is_exists && $is_exists > 0){
            return ['code' => 203 , 'msg' => '此课程名称已存在'];
        }
        
        //获取后端的操作员id
        $admin_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
        
        //组装课程数组信息
        $course_array = [
            'category_one_id'     =>   isset($body['parent_id']) && $body['parent_id'] > 0 ? $body['parent_id'] : 0 ,
            'category_tow_id'     =>   isset($body['child_id']) && $body['child_id'] > 0 ? $body['child_id'] : 0 ,
            'course_name'         =>   $body['course_name'] ,
            'price'               =>   $body['course_price'] ,
            'is_hide'             =>   isset($body['is_hide']) && $body['is_hide'] == 1 ? 1 : 0 ,
            'admin_id'            =>   $admin_id ,
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
     *     course_name       课程名称
     *     course_price      课程价格
     *     is_hide           是否显示/隐藏
     *     is_del            是否删除(是否删除1已删除)
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

        //判断课程名称是否为空
        if(!isset($body['course_name']) || empty($body['course_name'])){
            return ['code' => 201 , 'msg' => '请输入课程名称'];
        }
        
        //判断课程价格是否为空
        if(!isset($body['course_price'])){
            return ['code' => 201 , 'msg' => '请输入课程价格'];
        }

        //判断是否展示是否选择
        if(isset($body['is_hide']) && !in_array($body['is_hide'] , [0,1])){
            return ['code' => 202 , 'msg' => '展示方式不合法'];
        }
        
        //判断此课程得id是否存在此课程
        $is_exists_course = self::where('id' , $body['course_id'])->first();
        if(!$is_exists_course || empty($is_exists_course)){
            return ['code' => 203 , 'msg' => '此课程不存在'];
        }

        //判断课程名称是否存在
        $is_exists = self::where('category_one_id' , $is_exists_course['category_one_id'])->where('category_tow_id' , $is_exists_course['category_tow_id'])->where('course_name' , $body['course_name'])->where('is_del' , 0)->count();
        if($is_exists && $is_exists > 0){
            //组装课程数组信息
            $course_array = [
                'price'               =>   $body['course_price'] ,
                'is_hide'             =>   isset($body['is_hide']) && $body['is_hide'] == 1 ? 1 : 0 ,
                'is_del'              =>   isset($body['is_del']) && $body['is_del'] == 1 ? 1 : 0 ,
                'update_time'         =>   date('Y-m-d H:i:s')
            ];
        } else {
            //组装课程数组信息
            $course_array = [
                'course_name'         =>   $body['course_name'] ,
                'price'               =>   $body['course_price'] ,
                'is_hide'             =>   isset($body['is_hide']) && $body['is_hide'] == 1 ? 1 : 0 ,
                'is_del'              =>   isset($body['is_del']) && $body['is_del'] == 1 ? 1 : 0 ,
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
    
    /*
     * @param  description   项目管理-项目/学科详情方法
     * @param  参数说明       body包含以下参数[
     *     course_id         课程id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-07
     * return string
     */
    public static function getCourseInfoById($body=[]){
        //判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }
        
        //判断课程id是否合法
        if(!isset($body['course_id']) || empty($body['course_id']) || $body['course_id'] <= 0){
            return ['code' => 202 , 'msg' => '课程id不合法'];
        }
        
        //根据id获取课程的详情
        $info = self::select('course_name','price','is_hide','is_del')->where('id' , $body['course_id'])->where('is_del' , 0)->first();
        if($info && !empty($info)){
            return ['code' => 200 , 'msg' => '获取详情成功' , 'data' => $info];
        } else {
            return ['code' => 203 , 'msg' => '此课程不存在或已删除'];
        }
    }
    
    /*
     * @param  description   项目管理-课程列表接口
     * @param  参数说明       body包含以下参数[
     *     parent_id        项目id
     *     child_id         学科id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-03
     * return string
     */
    public static function getCourseList($body=[]){
        //判断项目的id是否为空
        if(!isset($body['parent_id']) || $body['parent_id'] <= 0){
            return ['code' => 202 , 'msg' => '项目id不合法'];
        }
        
        //判断学科id是否传递
        if(!isset($body['child_id']) || $body['child_id'] <= 0){
            //通过项目的id获取课程列表
            $course_list = self::select('id as course_id' , 'course_name' , 'course_name as label' , 'id as value')->where('category_one_id' , $body['parent_id'])->where('is_del' , 0)->get();
        } else {
            //通过项目的id获取课程列表
            $course_list = self::select('id as course_id' , 'course_name' , 'course_name as label' , 'id as value')->where('category_one_id' , $body['parent_id'])->where('category_tow_id' , $body['child_id'])->where('is_del' , 0)->get();
        }
        return ['code' => 200 , 'msg' => '获取课程列表成功' , 'data' => $course_list];
    }
    
    /*
     * @param  description   项目管理-课程列表接口
     * @param author    dzj
     * @param ctime     2020-09-15
     * return string
     */
    public static function getCourseAllList($body=[]){
        //通过项目的id获取课程列表
        $course_list = self::select('course_name as label' , 'id as value')->where('is_del' , 0)->get();
        return ['code' => 200 , 'msg' => '获取课程列表成功' , 'data' => $course_list];
    }
}
