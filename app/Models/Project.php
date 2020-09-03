<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class Project extends Model {
    //指定别的表名
    public $table      = 'category';
    //时间戳设置
    public $timestamps = false;

    /*
     * @param  description   项目管理-添加项目/学科方法
     * @param  参数说明       body包含以下参数[
     *     project_id        项目id
     *     name              项目/学科名称
     *     hide_flag         是否显示/隐藏
     * ]
     * @param author    dzj
     * @param ctime     2020-09-02
     * return string
     */
    public static function doInsertProjectSubject($body=[]){
        //判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }

        //判断项目id是否合法
        if(isset($body['project_id']) && $body['project_id'] > 0){
            //判断项目/学科名称是否为空
            if(!isset($body['name']) || empty($body['name'])){
                return ['code' => 201 , 'msg' => '请输入学科名称'];
            }

            //判断是否展示是否选择
            if(isset($body['hide_flag']) && !in_array($body['hide_flag'] , [0,1])){
                return ['code' => 202 , 'msg' => '展示方式不合法'];
            }
        
            //判断学科名称是否存在
            $is_exists = self::where('name' , $body['name'])->where('parent_id' , $body['project_id'])->where('is_del' , 0)->count();
            if($is_exists && $is_exists > 0){
                return ['code' => 203 , 'msg' => '此学科名称已存在'];
            }
        } else {
            //判断项目/学科名称是否为空
            if(!isset($body['name']) || empty($body['name'])){
                return ['code' => 201 , 'msg' => '请输入项目名称'];
            }

            //判断是否展示是否选择
            if(isset($body['hide_flag']) && !in_array($body['hide_flag'] , [0,1])){
                return ['code' => 202 , 'msg' => '展示方式不合法'];
            }
        
            //判断项目名称是否存在
            $is_exists = self::where('name' , $body['name'])->where('parent_id' , 0)->where('is_del' , 0)->count();
            if($is_exists && $is_exists > 0){
                return ['code' => 203 , 'msg' => '此项目名称已存在'];
            }
        }
        
        //组装项目数组信息
        $project_array = [
            'parent_id'     =>   isset($body['project_id']) && $body['project_id'] > 0 ? $body['project_id'] : 0 ,
            'name'          =>   $body['name'] ,
            'is_hide'       =>   isset($body['hide_flag']) && $body['hide_flag'] == 1 ? 1 : 0 ,
            'admin_id'      =>   0 ,
            'create_time'   =>   date('Y-m-d H:i:s')
        ];
        
        //开启事务
        DB::beginTransaction();

        //将数据插入到表中
        if(false !== self::insertGetId($project_array)){
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
     * @param  description   项目管理-修改项目/学科方法
     * @param  参数说明       body包含以下参数[
     *     prosub_id         项目/学科id
     *     name              项目/学科名称
     *     hide_flag         是否显示/隐藏(前台隐藏0正常 1隐藏)
     *     is_del            是否删除(是否删除1已删除)
     * ]
     * @param author    dzj
     * @param ctime     2020-09-02
     * return string
     */
    public static function doUpdateProjectSubject($body=[]){
        //判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }
        
        //判断项目/学科id是否合法
        if(!isset($body['prosub_id']) || empty($body['prosub_id']) || $body['prosub_id'] <= 0){
            return ['code' => 202 , 'msg' => 'id不合法'];
        }
        
        //判断项目/学科名称是否为空
        if(!isset($body['name']) || empty($body['name'])){
            return ['code' => 201 , 'msg' => '请输入名称'];
        }

        //判断是否展示是否选择
        if(isset($body['hide_flag']) && !in_array($body['hide_flag'] , [0,1])){
            return ['code' => 202 , 'msg' => '展示方式不合法'];
        }
        
        //根据id获取信息
        $info = self::where('id' , $body['prosub_id'])->first();
        if(!$info || empty($info)){
            return ['code' => 203 , 'msg' => '此信息不存在'];
        }
        
        //判断是项目还是学科
        if($info['parent_id'] && $info['parent_id'] > 0){
            //判断学科名称是否存在
            $is_exists = self::where('name' , $body['name'])->where('parent_id' , $info['parent_id'])->where('is_del' , 0)->count();
            if($is_exists && $is_exists > 0){
                //组装项目数组信息
                $project_array = [
                    'is_hide'       =>   isset($body['hide_flag']) && $body['hide_flag'] == 1 ? 1 : 0 ,
                    'is_del'        =>   isset($body['is_del']) && $body['is_del'] == 1 ? 1 : 0 ,
                    'update_time'   =>   date('Y-m-d H:i:s')
                ];
            } else {
                //组装项目数组信息
                $project_array = [
                    'name'          =>   $body['name'] ,
                    'is_hide'       =>   isset($body['hide_flag']) && $body['hide_flag'] == 1 ? 1 : 0 ,
                    'is_del'        =>   isset($body['is_del']) && $body['is_del'] == 1 ? 1 : 0 ,
                    'update_time'   =>   date('Y-m-d H:i:s')
                ];
            }
        } else {
            //判断项目名称是否存在
            $is_exists = self::where('name' , $body['name'])->where('parent_id' , 0)->where('is_del' , 0)->count();
            if($is_exists && $is_exists > 0){
                //组装项目数组信息
                $project_array = [
                    'is_hide'       =>   isset($body['hide_flag']) && $body['hide_flag'] == 1 ? 1 : 0 ,
                    'is_del'        =>   isset($body['is_del']) && $body['is_del'] == 1 ? 1 : 0 ,
                    'update_time'   =>   date('Y-m-d H:i:s')
                ];
            } else {
                //组装项目数组信息
                $project_array = [
                    'name'          =>   $body['name'] ,
                    'is_hide'       =>   isset($body['hide_flag']) && $body['hide_flag'] == 1 ? 1 : 0 ,
                    'is_del'        =>   isset($body['is_del']) && $body['is_del'] == 1 ? 1 : 0 ,
                    'update_time'   =>   date('Y-m-d H:i:s')
                ];
            }
        }
        
        //开启事务
        DB::beginTransaction();

        //根据项目/学科id更新信息
        if(false !== self::where('id',$body['prosub_id'])->update($project_array)){
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
     * @param  description   项目管理-项目筛选学科列表接口
     * @param  参数说明       body包含以下参数[
     *     project_id        项目id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-03
     * return string
     */
    public static function getProjectSubjectList() {
        //项目列表
        $project_list = self::select('id','name')->where('parent_id' , 0)->where('is_del' , 0)->orderByDesc('create_time')->get()->toArray();
        if($project_list && !empty($project_list)){
            foreach($project_list as $k=>$v){
                //获取学科得列表
                $subject_list = self::select('id','name')->where('parent_id' , $v['id'])->where('is_del' , 0)->orderByDesc('create_time')->get()->toArray();
                if($subject_list && !empty($subject_list)){
                    //根据项目得id获取学科得列表
                    $project_list[$k]['subject_list'] = $subject_list && !empty($subject_list) ? $subject_list : [];
                }
            }
            return ['code' => 200 , 'msg' => '获取列表成功' , 'data' => $project_list];
        } else {
            return ['code' => 200 , 'msg' => '获取列表成功' , 'data' => []];
        }
    }
}
