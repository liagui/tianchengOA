<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

    public function getSchoolByIdLevel(){
        try{
            $data = School::getSchoolByIdLevel(self::$accept_data);
            if($data['code'] == 200){
                return response()->json($data);
            } else {
                return response()->json($data);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }      
    }


    public function getList(){
            //获取提交的参数
        try{
            $body = self::$accept_data;

            $school_id =  isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0;
            if(!isset($body['school_id']) || empty($body['school_id'])){  
                $schoolArr = $this->underlingLook($school_id);
                $body['school_id']   = $schoolArr['data'];
            }else{  
                $body['school_id'] =explode(',',$body['school_id']);
            }
            $data = School::getList($body);
            if($data['code'] == 200){
                return response()->json($data);
            } else {
                return response()->json($data);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
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
    public function doInsertSchool() {
     
        //获取提交的参数
        try{
            $data = School::doInsertSchool(self::$accept_data);
            if($data['code'] == 200){
                return response()->json(['code' => 200 , 'msg' => '添加成功']);
            } else {
                return response()->json(['code' => $data['code'] , 'msg' => $data['msg']]);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
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
    public function doUpdateSchool() {
        //获取提交的参数
        try{
           
            $data = School::doUpdateSchool(self::$accept_data);
            if($data['code'] == 200){
                return response()->json(['code' => 200 , 'msg' => $data['msg']]);
            } else {
                return response()->json(['code' => $data['code'] , 'msg' => $data['msg']]);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    


    public function getSchoolInfoById(){
        //获取提交的参数
        try{
            //获取分校详情
            $data = School::getSchoolInfoById(self::$accept_data);
            if($data['code'] == 200){
                return response()->json(['code' => 200 , 'msg' => '获取详情成功' , 'data' => $data['data']]);
            } else {
                return response()->json(['code' => $data['code'] , 'msg' => $data['msg']]);
            }  
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
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

     public function getSchoolListByLevels(){
        //获取提交的参数
        try{
            //获取分校列表
            $data = School::getSchoolListByLevels(self::$accept_data);
            if($data['code'] == 200){
                return response()->json(['code' => 200 , 'msg' => '获取列表成功' , 'data' => $data['data']]);
            } else {
                return response()->json(['code' => $data['code'] , 'msg' => $data['msg']]);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }


    public function getSchoolListByLevel(){
        //获取提交的参数
        try{
            //获取分校列表
            $data = School::getSchoolListByLevel(self::$accept_data);
            if($data['code'] == 200){
                return response()->json(['code' => 200 , 'msg' => '获取列表成功' , 'data' => $data['data']]);
            } else {
                return response()->json(['code' => $data['code'] , 'msg' => $data['msg']]);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
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
    public function getSchoolList(){
        //获取提交的参数
        try{
            //获取分校列表
            $data = School::getSchoolList(self::$accept_data);
            if($data['code'] == 200){
                return response()->json(['code' => 200 , 'msg' => '获取列表成功' , 'data' => $data['data']]);
            } else {
                return response()->json(['code' => $data['code'] , 'msg' => $data['msg']]);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    /*
     * @param  description   分校删除列表
     * @param  参数说明       body包含以下参数[
     *     school_id      分校标识
     * ]
     * @param author    lys
     * @param ctime     2020-09-07
     * return string
     */
    public function doDelSchool(){
        //获取提交的参数
        try{
            //获取分校列表
            $data = self::$accept_data;
            //判断分校id是否合法
            if(!isset($data['school_id']) || empty($data['school_id']) || $data['school_id'] <= 0){
                return ['code' => 202 , 'msg' => '分校id不合法'];
            }
            $schoolData  = School::where(['id'=>$data['school_id'],'is_del'=>0])->first();
            if(!empty($schoolData)){
                DB::beginTransaction();
                $res = School::where(['id'=>$data['school_id'],'is_del'=>0])->update(['is_del'=>1,'update_time'=>date('Y-m-d H:i:s')]);
                if($res){
                    DB::commit();
                    return response()->json(['code' => 200 , 'msg' => '删除成功']);
                }else{
                    DB::rollBack();
                    return response()->json(['code' => 203 , 'msg' => '删除失败，请重试']);
                }
            } else {
                return response()->json(['code' => 201, 'msg' => '学校不存在或已删除']);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    /*
     * @param  description   是否启动关闭
     * @param  参数说明       body包含以下参数[
     *     school_id      分校标识
     * ]
     * @param author    lys
     * @param ctime     2020-09-07
     * return string
     */
    public function doOpenSchool(){
        //获取提交的参数
        try{
            //获取分校列表
            $data = self::$accept_data;
            //判断分校id是否合法
            if(!isset($data['school_id']) || empty($data['school_id']) || $data['school_id'] <= 0){
                return ['code' => 202 , 'msg' => '分校id不合法'];
            }
            $schoolData  = School::where(['id'=>$data['school_id'],'is_del'=>0])->select('id','is_open')->first();
            if(!empty($schoolData)){
                if($schoolData['is_open'] != 0){
                    $is_open = 0;
                }else{
                    $is_open = 1;
                }
                DB::beginTransaction();         
                $res = School::where(['id'=>$data['school_id'],'is_del'=>0])->update(['is_open'=>$is_open,'update_time'=>date('Y-m-d H:i:s')]);
                if($res){
                    DB::commit();
                    return response()->json(['code' => 200 , 'msg' => '更新成功']);
                }else{
                    DB::rollBack();
                    return response()->json(['code' => 203 , 'msg' => '更新失败，请重试']);
                }
            } else {
                return response()->json(['code' => 201, 'msg' => '学校不存在或已删除']);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }    
    /*
     * @param  description  是否查看下属分校
     * @param  参数说明       body包含以下参数[
     *     school_id      分校标识
     * ]
     * @param author    lys
     * @param ctime     2020-09-07
     * return string
     */
    public function doLookSchool(){
        //获取提交的参数
        try{
            //获取分校列表
            $data = self::$accept_data;
            //判断分校id是否合法
            if(!isset($data['school_id']) || empty($data['school_id']) || $data['school_id'] <= 0){
                return ['code' => 202 , 'msg' => '分校id不合法'];
            }
            $schoolData  = School::where(['id'=>$data['school_id'],'is_del'=>0])->first();
            if(!empty($schoolData)){
                if($schoolData['look_all_flag'] != 0){
                    $look_all_flag = 0;
                }else{
                    $look_all_flag = 1;
                }
                DB::beginTransaction();
                $res = School::where(['id'=>$data['school_id'],'is_del'=>0])->update(['look_all_flag'=>$look_all_flag,'update_time'=>date('Y-m-d H:i:s')]);
                if($res){
                    DB::commit();
                    return response()->json(['code' => 200 , 'msg' => '更新成功']);
                }else{
                    DB::rollBack();
                    return response()->json(['code' => 203 , 'msg' => '更新失败，请重试']);
                }
            } else {
                return response()->json(['code' => 201, 'msg' => '学校不存在或已删除']);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }        



}
