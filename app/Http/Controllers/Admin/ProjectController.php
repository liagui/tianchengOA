<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Course;

class ProjectController extends Controller {
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
    public function doInsertProjectSubject() {
        //获取提交的参数
        try{
            $data = Project::doInsertProjectSubject(self::$accept_data);
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
    public function doUpdateProjectSubject() {
        //获取提交的参数
        try{
            $data = Project::doUpdateProjectSubject(self::$accept_data);
            if($data['code'] == 200){
                return response()->json(['code' => 200 , 'msg' => '修改成功']);
            } else {
                return response()->json(['code' => $data['code'] , 'msg' => $data['msg']]);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    
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
    public function doInsertCourse() {
        //获取提交的参数
        try{
            $data = Course::doInsertCourse(self::$accept_data);
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
     * @param  description   项目管理-修改课程方法
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
    public function doUpdateCourse() {
        //获取提交的参数
        try{
            $data = Course::doUpdateCourse(self::$accept_data);
            if($data['code'] == 200){
                return response()->json(['code' => 200 , 'msg' => '修改成功']);
            } else {
                return response()->json(['code' => $data['code'] , 'msg' => $data['msg']]);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    
    /*
     * @param  description   项目管理-项目筛选学科列表接口
     * @param author    dzj
     * @param ctime     2020-09-03
     * return string
     */
    public function getProjectSubjectList(){
        //获取提交的参数
        try{
            //获取全部项目列表
            $data = Project::getProjectSubjectList();
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
     * @param  description   项目管理-课程列表接口
     * @param  参数说明       body包含以下参数[
     *     parent_id        项目id
     *     child_id         学科id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-03
     * return string
     */
    public function getCourseList(){
        //获取提交的参数
        try{
            //获取全部项目列表
            $data = Course::getCourseList(self::$accept_data);
            if($data['code'] == 200){
                return response()->json(['code' => 200 , 'msg' => '获取列表成功' , 'data' => $data['data']]);
            } else {
                return response()->json(['code' => $data['code'] , 'msg' => $data['msg']]);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
}
