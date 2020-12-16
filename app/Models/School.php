<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use App\Models\AdminLog;
use App\Models\Admin;
#use App\Tools\CurrentAdmin;
class School extends Model {
    //指定别的表名
    public $table = 'school';
    //时间戳设置
    public $timestamps = false;

    public function lessons() {
        return $this->belongsToMany('App\Models\Lesson', 'ld_lesson_schools', 'school_id');
    }

    public function admins() {
        return $this->hasMany('App\Models\Admin');
    }
    //错误信息
     public static function message(){

        return [

        ];
    }
    public static function getList($body){
        $school_name = isset($body['search']) && !empty($body['search']) ? $body['search'] :'';
        $schoolData = self::where(['is_del'=>0,'is_open'=>0])
        ->where(function($query) use ($body,$school_name){
            //判断分校名称是否为空
            if(isset($body['search']) && !empty($body['search'])){
                $query->where('school_name','like','%'.$school_name.'%');
            }else{
                if(isset($body['school_id']) && !empty($body['school_id'])){
                    $query->whereIn('id',$body['school_id']);
                }
            }

        })->select('id','school_name','id as value','school_name as label')->get();
        return ['code'=>200,'msg'=>'Success','data'=>$schoolData];
    }
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
        if(!isset($body['commission']) || strlen($body['commission']) <=0){
            return ['code' => 201 , 'msg' => '请输入佣金比例'];
        }

        //判断押金比例是否为空
        if(!isset($body['deposit']) || strlen($body['deposit']) <=0){
            return ['code' => 201 , 'msg' => '请输入押金比例'];
        }

        //判断税点比例是否为空
        if(!isset($body['tax_point']) || strlen($body['tax_point']) <=0 ){
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
            }else{
                if($body['level'] == 2 && in_array(2,[2,3])){
                    if(!isset($body['one_extraction_ratio']) || strlen($body['one_extraction_ratio']) <=0){
                        return ['code'=>201,'msg'=>'请输入一级抽离比例'];
                    }
                }
                if($body['level'] == 3 && in_array(3,[2,3])){
                    if(!isset($body['one_extraction_ratio']) || strlen($body['one_extraction_ratio']) <=0){
                        return ['code'=>201,'msg'=>'请输入一级抽离比例'];
                    }
                    if(!isset($body['two_extraction_ratio']) || strlen($body['one_extraction_ratio']) <=0){
                        return ['code'=>201,'msg'=>'请输入二级级抽离比例'];
                    }
                }
            }
        }
        //获取后端的操作员id
        $admin_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;

        // //组装分校数组信息
        $school_array = [
            'school_name'   =>   $body['school_name'] ,
            'level'         =>   isset($body['level']) && in_array($body['level'] , [1,2,3]) ? $body['level'] : 1 ,
            'parent_id'     =>   isset($body['level']) && $body['level'] > 1 && isset($body['parent_id']) && $body['parent_id'] > 0 ? $body['parent_id'] : 0 ,
            'tax_point'     =>   $body['tax_point'] ,
            'commission'    =>   $body['commission'] ,
            'deposit'       =>   $body['deposit'] ,
            'look_all_flag' =>   isset($body['look_all_flag']) && $body['look_all_flag'] == 1 ? 1 : 0 ,
            'create_id'     =>   $admin_id ,
            'create_time'   =>   date('Y-m-d H:i:s'),
            'one_extraction_ratio' => isset($body['one_extraction_ratio'])?$body['one_extraction_ratio']:'',
            'two_extraction_ratio' => isset($body['two_extraction_ratio'])?$body['two_extraction_ratio']:'',

        ];
        $schoolIdsArr = School::where(['is_del'=>0])->pluck('id')->toArray();
        $adminArr = Admin::where(['is_del'=>1])->select('id','school_id')->get()->toArray();

        //开启事务
        DB::beginTransaction();
        //将数据插入到表中
        $schoolId = self::insertGetId($school_array);
        if($schoolId >0){
            foreach($adminArr as $key =>$v){
                 $school =  empty($v['school_id'])&&strlen($v['school_id'])<=0?[]:explode(",",$v['school_id']);

                 if(!empty($school)){
                    if(empty(array_diff($schoolIdsArr,$school)) | (in_array($school[0],[0]) && isset($school[1])) ){

                        $school = array_merge($school,[$schoolId]);
                        $school = implode(',',$school);
                        $res = Admin::where('id',$v['id'])->update(['school_id'=>$school,'update_time'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
                        if(!$res){
//                            DB::rollBack();
                            return ['code' => 203 , 'msg' => '添加失败!'];
                        }
//                         DB::commit();
                    }
                }
            }

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
        if(!isset($body['commission']) || strlen($body['commission']) <=0){
            return ['code' => 201 , 'msg' => '请输入佣金比例'];
        }

        //判断押金比例是否为空
        if(!isset($body['deposit']) || strlen($body['deposit']) <=0){
            return ['code' => 201 , 'msg' => '请输入押金比例'];
        }

        //判断税点比例是否为空
        if(!isset($body['tax_point']) || strlen($body['tax_point']) <=0){
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
            }else{
                if($body['level'] == 2 && in_array(2,[2,3])){
                    if(!isset($body['one_extraction_ratio'])){
                        return ['code'=>201,'msg'=>'请输入一级抽离比例'];
                    }
                }
                if($body['level'] == 3 && in_array(3,[2,3])){
                    if(!isset($body['one_extraction_ratio'])){
                        return ['code'=>201,'msg'=>'请输入一级抽离比例'];
                    }
                    if(!isset($body['two_extraction_ratio'])){
                        return ['code'=>201,'msg'=>'请输入二级级抽离比例'];
                    }
                }
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
                'update_time'   =>   date('Y-m-d H:i:s'),
                'one_extraction_ratio' => isset($body['one_extraction_ratio'])?$body['one_extraction_ratio']:'',
                'two_extraction_ratio' => isset($body['two_extraction_ratio'])?$body['two_extraction_ratio']:'',
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
                'update_time'   =>   date('Y-m-d H:i:s'),
                'one_extraction_ratio' => isset($body['one_extraction_ratio'])?$body['one_extraction_ratio']:'',
                'two_extraction_ratio' => isset($body['two_extraction_ratio'])?$body['two_extraction_ratio']:'',

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
        $info = self::select('school_name', 'level' , 'tax_point','commission' , 'deposit' , 'look_all_flag' , 'parent_id','one_extraction_ratio','two_extraction_ratio')->where('id' , $body['school_id'])->where('is_del' , 0)->first();
        if($info && !empty($info)){
            return ['code' => 200 , 'msg' => '获取详情成功' , 'data' => $info];
        } else {
            return ['code' => 203 , 'msg' => '此分校不存在或已删除'];
        }
    }
    /*
     * @param  description   分校管理-上级分校列表方法
     * @param  参数说明       body包含以下参数[
     *     level         分校级别[1,2,3]
     * ]
     * @param author    lys
     * @param ctime     2020-09-07
     * return string
     */
    public static function getSchoolListByLevels($body=[]){
        //判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }

        //判断分校级别是否合法
        if(!isset($body['level']) || !in_array($body['level'] , [1,2,3])){
            return ['code' => 201 , 'msg' => '分校级别不合法'];
        }
        //判断分校级别是否合法
        if(!isset($body['school_id']) || empty($body['school_id']) ){
            $level = $body['level'] - 1;
            $school_list = self::select('id as school_id' , 'school_name')->where('level' , $level)->where('is_del' , 0)->get();
            return ['code' => 200 , 'msg' => '获取列表成功' , 'data' => $school_list];
        }
        //根据分校的级别获取分校列表
        if($body['level'] > 1){
            $level = $body['level'] - 1;
            $school_list = self::select('id as school_id' , 'school_name')->where('level' , $level)->where('is_del' , 0)->get();
            if(!empty($school_list)){
                foreach($school_list as $k=>$v){
                    if($v['school_id'] == $body['school_id']){
                        unset($school_list[$k]);
                    }
                }
            }
            return ['code' => 200 , 'msg' => '获取列表成功' , 'data' => $school_list];
        } else {
            return ['code' => 200 , 'msg' => '获取列表成功' , 'data' => []];
        }
    }

    /*
     * @param  description   分校管理-上级分校列表方法
     * @param  参数说明       body包含以下参数[
     *     level         分校级别[1,2,3]
     * ]
     * @param author    dzj
     * @param ctime     2020-09-07
     * return string
     */
    public static function getSchoolListByLevel($body=[]){
        //判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }

        //判断分校级别是否合法
        if(!isset($body['level']) || !in_array($body['level'] , [1,2,3])){
            return ['code' => 202 , 'msg' => '分校级别不合法'];
        }
        //根据分校的级别获取分校列表
        if($body['level'] > 1){
            $level = $body['level'] - 1;
            $school_list = self::select('id as school_id' , 'school_name')->where('level' , $level)->where('is_del' , 0)->get();
            return ['code' => 200 , 'msg' => '获取列表成功' , 'data' => $school_list];
        } else {
            return ['code' => 200 , 'msg' => '获取列表成功' , 'data' => []];
        }
    }


    /*
     * @param  description   分校管理列表接口
     * @param  参数说明       body包含以下参数[
     *     school_name       分校名称
     * ]
     * @param author    dzj
     * @param ctime     2020-09-07
     * return string
     */
    public static function getSchoolList($body=[]) {
        //每页显示的条数
        $pagesize = isset($body['pagesize']) && $body['pagesize'] > 0 ? $body['pagesize'] : 20;
        $page     = isset($body['page']) && $body['page'] > 0 ? $body['page'] : 1;
        $offset   = ($page - 1) * $pagesize;

        //获取分校的总数量
        $school_count = self::where(function($query) use ($body){
            //判断分校名称是否为空
            if(isset($body['school_name']) && !empty($body['school_name'])){
                $query->where('school_name','like','%'.$body['school_name'].'%');
            }
        })->where('is_del' , 0)->count();

        if($school_count > 0){
            //新数组赋值
            $school_array = [];

            //获取分校列表
            $school_list = self::select('id as school_id' , 'parent_id' , 'school_name' , 'tax_point' , 'commission' , 'deposit' , 'level' , 'look_all_flag' , 'is_open','one_extraction_ratio','two_extraction_ratio')->where(function($query) use ($body){
                //判断分校名称是否为空
                if(isset($body['school_name']) && !empty($body['school_name'])){
                    $query->where('school_name','like','%'.$body['school_name'].'%');
                }
            })->where('is_del' , 0)->orderByDesc('create_time')->offset($offset)->limit($pagesize)->get()->toArray();

            //循环获取相关信息
            foreach($school_list as $k=>$v){
                //获取上级分校的名称
                if($v['level'] == 2){
                    $prev_school_name =  self::where('id' , $v['parent_id'])->value('school_name');
                } else if($v['level'] == 3){
                    //2级的id
                    $two_parent_id = $v['parent_id'];
                    //获取1级的id
                    $one_parent_id = self::where('id' , $two_parent_id)->value('parent_id');

                    //通过1级的id获取名称
                    $one_school_name =  self::where('id' , $one_parent_id)->value('school_name');
                    //通过2级的id获取名称
                    $two_school_name =  self::where('id' , $two_parent_id)->value('school_name');
                    $prev_school_name=  $one_school_name.'-'.$two_school_name;
                } else {
                    $prev_school_name = '';
                }

                //分校数组管理赋值
                $school_array[] = [
                    'school_id'        =>  $v['school_id'] ,
                    'school_name'      =>  $v['school_name'] ,
                    'tax_point'        =>  $v['tax_point'] && $v['tax_point'] > 0 ? $v['tax_point'].'%' : 0 ,
                    'commission'       =>  $v['commission'] && $v['commission'] > 0 ? $v['commission'].'%' : 0 ,
                    'deposit'          =>  $v['deposit'] && $v['deposit'] > 0 ? $v['deposit'].'%' : 0 ,
                    'level'            =>  (int)$v['level'] ,
                    'prev_school_name' =>  $prev_school_name ,
                    'look_all_name'    =>  $v['look_all_flag'] && $v['look_all_flag'] > 0 ? '开启' : '关闭' ,
                    'look_all_flag'    =>  $v['look_all_flag'] ,
                    'is_open'          =>  $v['is_open'] ,
                    'is_open_name'     =>  $v['is_open'] && $v['is_open'] > 0 ? '关闭' : '开启' ,
                    'admin_name'       =>  'admin',
                    'one_extraction_ratio'=>$v['one_extraction_ratio'] && $v['one_extraction_ratio'] > 0 ? $v['one_extraction_ratio'].'%' : 0,
                    'two_extraction_ratio' =>$v['two_extraction_ratio'] && $v['two_extraction_ratio'] > 0 ? $v['two_extraction_ratio'].'%' : 0,
                ];
            }
            return ['code' => 200 , 'msg' => '获取分校列表成功' , 'data' => ['school_list' => $school_array , 'total' => $school_count , 'pagesize' => $pagesize , 'page' => $page]];
        }
        return ['code' => 200 , 'msg' => '获取分校列表成功' , 'data' => ['school_list' => [] , 'total' => 0 , 'pagesize' => $pagesize , 'page' => $page]];
    }
}
