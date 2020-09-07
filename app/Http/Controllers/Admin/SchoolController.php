<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;
use App\Models\Admin as Adminuser;
use App\Models\Roleauth;
use App\Models\Authrules;
use App\Models\School;
use App\Models\PaySet;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use App\Tools\CurrentAdmin;
use App\Models\AdminLog;
use App\Models\AuthMap;
use App\Models\FootConfig;
use Illuminate\Support\Facades\DB;
use App\Models\CouresSubject;
use Log;
class SchoolController extends Controller {
  

    public function schoolList(){
        $arr = School::where(['is_del'=>0,'is_open'=>0])->select('id','school_name')->get()->toArray();
        return response()->json(['code'=>200,'msg'=>'success','data'=>$arr]);
    }
     /*
     * @param  description 获取分校列表  
     * @param  参数说明       body包含以下参数[
     *     school_name       搜索条件
     *     school_dns        分校域名
     *     page         当前页码  
     *     limit        每页显示条数
     * ]
     * @param author    lys
     * @param ctime     2020-05-05
     */
    public function getSchoolList(){
        $schoolData = School::getList(self::$accept_data);
        return response()->json($schoolData);      
    }
    /*
     * @param  description 修改分校状态 (删除)
     * @param  参数说明       body包含以下参数[
     *     school_id      分校id
     * ]
     * @param author    lys
     * @param ctime     2020-05-06
     */
    public function doSchoolDel(){
        $data = self::$accept_data;
        $validator = Validator::make($data, 
                ['school_id' => 'required|integer'],
                School::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
        try{
            DB::beginTransaction();
            $school = School::find($data['school_id']);
            $school->is_del = 1; 
            if(!$school->save()){
                DB::rollBack();
                return response()->json(['code' => 203 , 'msg' => '删除失败,请重试']);
            }else{
                 AdminLog::insertAdminLog([
                'admin_id'       =>  isset(CurrentAdmin::user()['id'])?CurrentAdmin::user()['id']:0 ,
                'module_name'    =>  'School' ,
                'route_url'      =>  'admin/school/doSchoolDel' , 
                'operate_method' =>  'update' ,
                'content'        =>  json_encode($data),
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                    'create_at'      =>  date('Y-m-d H:i:s')
                ]);
                DB::commit();
                return response()->json(['code' => 200 , 'msg' => '删除成功']);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 203 , 'msg' => $ex->getMessage()]);
        }
    }

    /*
     * @param  description 修改分校状态 (启禁)
     * @param  参数说明       body包含以下参数[
     *     school_id      分校id
     * ]
     * @param author    lys
     * @param ctime     2020-05-06
     */
    public function doSchoolForbid(){
        $data = self::$accept_data;
        $validator = Validator::make($data, 
                ['school_id' => 'required|integer'],
                School::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
        try{
            DB::beginTransaction();
            $school = School::where(['id'=>$data['school_id'],'is_del'=>0])->first();
            if($school['is_open'] != 1){
                $is_open = 1; 
            }else{
                $is_open = 0; 
            }   
            if(!School::where('id',$school['id'])->update(['update_time'=>date('Y-m-d H:i:s'),'is_open'=>$is_open])){
                DB::rollBack();
                return response()->json(['code' => 203 , 'msg' => '更新失败']);
            }else{
                AdminLog::insertAdminLog([
                    'admin_id'       =>   isset(CurrentAdmin::user()['id'])?CurrentAdmin::user()['id']:0 ,
                    'module_name'    =>  'School' ,
                    'route_url'      =>  'admin/school/doSchoolForbid' , 
                    'operate_method' =>  'update',
                    'content'        =>  json_encode($data),
                    'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                    'create_at'      =>  date('Y-m-d H:i:s')
                ]);
                DB::commit();
                return response()->json(['code' => 200 , 'msg' => '更新成功']);
            }

        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }

    }
      /*
     * @param  description 修改分校状态 (启禁)
     * @param  参数说明       body包含以下参数[
     *     school_id      分校id
     * ]
     * @param author    lys
     * @param ctime     2020-05-06
     */
    public function doSchoolLook(){
        $data = self::$accept_data;
        $validator = Validator::make($data, 
                ['school_id' => 'required|integer'],
                School::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
        try{
            DB::beginTransaction();
            $school = School::where(['id'=>$data['school_id'],'is_del'=>0])->first();
            if($school['is_look'] != 1){
                $look = 1; 
            }else{
                $look = 0; 
            }   
            if(!School::where('id',$school['id'])->update(['update_time'=>date('Y-m-d H:i:s'),'is_look'=>$look])){
                DB::rollBack();
                return response()->json(['code' => 203 , 'msg' => '更新失败']);
            }else{
                AdminLog::insertAdminLog([
                    'admin_id'       =>   isset(CurrentAdmin::user()['id'])?CurrentAdmin::user()['id']:0 ,
                    'module_name'    =>  'School' ,
                    'route_url'      =>  'admin/school/doSchoolLook' , 
                    'operate_method' =>  'update',
                    'content'        =>  json_encode($data),
                    'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                    'create_at'      =>  date('Y-m-d H:i:s')
                ]);
                DB::commit();
                return response()->json(['code' => 200 , 'msg' => '更新成功']);
            }

        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }

    }
    /*
     * @param  description 学校添加 
     * @param  参数说明       body包含以下参数[
     *  'name' =>分校名称
        'dns' =>分校域名
        'logo_url' =>分校logo
        'introduce' =>分校简介
        'username' =>登录账号
        'password' =>登录密码
        'pwd' =>确认密码
        'realname' =>联系人(真实姓名)
        'mobile' =>联系方式
     * ]
     * @param author    lys
     * @param ctime     2020-05-06
     */
    public function doInsertSchool(){
        $user_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
        $data = self::$accept_data;
        $validator = Validator::make(
                $data, 
                ['name' => 'required',
                 'tax_point' => 'required',
                 'commission'=>'required',
                 'deposit'=>'required',
                 'look'=>'required',
                 'level'=>'required',
                ],
                School::message());

        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
    
        $count  = School::where('school_name',$data['name'])->where('is_del',0)->count();
        if($count>0){
            return response()->json(['code'=>205,'msg'=>'网校名称已存在']);
        }
        if($data['level'] == 2 || $data['level'] == 3){
            if(!isset($data['parent_id']) && $data['parent_id'] <=0){
                return ['code'=>203,'msg'=>'缺少父级id'];
            }
        }
        $date = date('Y-m-d H:i:s');
        try{
            DB::beginTransaction();
            $school = [
                'school_name' =>$data['name'],
                'tax_point'  =>$data['tax_point']*100,
                'commission'  =>$data['commission']*100,
                'deposit'  =>$data['deposit']*100,
                'create_id'  => isset(CurrentAdmin::user()['id'])?CurrentAdmin::user()['id']:0,
                'is_look'=> $data['look'],
                'level'=> $data['level'],
                'parent_id'=> !isset($data['parent_id']) && $data['parent_id'] <=0?0:$data['parent_id'],
                'create_time'=>$date
            ];
            $school_id = School::insertGetId($school);
            if($school_id <1){
                DB::rollBack();
                return response()->json(['code'=>203,'msg'=>'创建学校未成功']);  
            }else{
                AdminLog::insertAdminLog([
                    'admin_id'       =>   CurrentAdmin::user()['id'] ,
                    'module_name'    =>  'School' ,
                    'route_url'      =>  'admin/school/doInsertSchool' , 
                    'operate_method' =>  'update',
                    'content'        =>  json_encode($data),
                    'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                    'create_at'      =>  date('Y-m-d H:i:s')
                ]);
                DB::commit();
                return response()->json(['code' => 200 , 'msg' => '创建学校成功']);
            }
          
         
                
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    /*
     * @param  description 获取学校信息 
     * @param  参数说明       body包含以下参数[
     *  'school_id' =>学校id
     * ]
     * @param author    lys
     * @param ctime     2020-05-06
     */
    public function getSchoolUpdate(){
        $data = self::$accept_data;
        $validator = Validator::make(
                $data, 
                ['school_id' => 'required|integer'],
                School::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
        $school = School::where('id',$data['school_id'])->select('id','school_name','level','parent_id','tax_point','commission','deposit','is_look')->first();
        if(!empty($school)){
            $school['tax_point'] = $school['tax_point'] <= 0 ?0:sprintf("%.2f",(int)$school['tax_point']/100);
            $school['commission'] = $school['tax_point'] <= 0 ?0:sprintf("%.2f",(int)$school['commission']/100);
            $school['deposit'] = $school['tax_point'] <= 0 ?0:sprintf("%.2f",(int)$school['deposit']/100);
        }
        return response()->json(['code' => 200 , 'msg' => 'Success','data'=>$school]);
    }
    /*
     * @param  description 修改分校信息 
     * @param  参数说明       body包含以下参数[
     *  'id'=>分校id
        'name' =>分校名称
        'dns' =>分校域名
        'logo_url' =>分校logo
        'introduce' =>分校简介
     * ]
     * @param author    lys
     * @param ctime     2020-05-06
     */
    public function doSchoolUpdate(){
        $data = self::$accept_data;

        $validator = Validator::make(
                $data, 
                [
                    'name' => 'required',
                    'tax_point' => 'required',
                    'commission'=>'required',
                    'deposit'=>'required',
                    'look'=>'required',
                    'level'=>'required',
                ],
                School::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
        if(School::where(['school_name'=>$data['name'],'is_del'=>0])->where('id','!=',$data['id'])->count()>0){
             return response()->json(['code' => 422 , 'msg' => '学校已存在']);
        }
        if($data['level'] == 2 || $data['level'] == 3){
            if(!isset($data['parent_id']) && $data['parent_id'] <=0){
                return ['code'=>203,'msg'=>'缺少父级id'];
            }
        }
        if(isset($data['/admin/school/doSchoolUpdate'])){
            unset($data['/admin/school/doSchoolUpdate']);
        }
        $update = [
            'school_name' => $data['name'],
            'tax_point' => $data['tax_point']*100,
            'commission' => $data['commission']*100,
            'deposit' => $data['deposit']*100,
            'is_look' => $data['look'],
            'level' => $data['level'],
            'parent_id'=>!isset($data['parent_id']) && $data['parent_id'] <=0 ?0:$data['parent_id']

        ];
        $data['update_time'] = date('Y-m-d H:i:s');
        if(School::where('id',$data['id'])->update($update)){
                AdminLog::insertAdminLog([
                    'admin_id'       =>   isset(CurrentAdmin::user()['id'])?CurrentAdmin::user()['id']:0 ,
                    'module_name'    =>  'School' ,
                    'route_url'      =>  'admin/school/doSchoolUpdate' , 
                    'operate_method' =>  'update',
                    'content'        =>  json_encode($data),
                    'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                    'create_at'      =>  date('Y-m-d H:i:s')
                ]);
            return response()->json(['code' => 200 , 'msg' => '更新成功']);
        }else{
            return response()->json(['code' => 200 , 'msg' => '更新成功']);
        }
    }
   

}
