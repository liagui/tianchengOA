<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Entey_fee;
use App\Models\Project;
use App\Models\Course;
use App\Models\RegionFee;
use App\Models\CategoryRegion;
use App\Models\Education;
use App\Models\CategoryEducation;
use App\Models\Major;

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
     * @param  description   项目管理-项目/学科详情方法
     * @param  参数说明       body包含以下参数[
     *     info_id         项目/学科id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-07
     * return string
     */
    public function getProjectSubjectInfoById(){
        //获取提交的参数
        try{
            //获取项目学科详情
            $data = Project::getProjectSubjectInfoById(self::$accept_data);
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
     * @param  description   项目管理-课程详情方法
     * @param  参数说明       body包含以下参数[
     *     course_id         课程id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-07
     * return string
     */
    public function getCourseInfoById(){
        //获取提交的参数
        try{
            //获取课程详情
            $data = Course::getCourseInfoById(self::$accept_data);
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
     * @param  description   项目管理-项目筛选学科列表接口
     * @param author    dzj
     * @param ctime     2020-09-03
     * return string
     */
    public function getProjectSubjectList(){
        //获取提交的参数
        try{
            //获取全部项目列表
            $data = Project::getProjectSubjectList(self::$accept_data);
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
     * @param  description   项目管理-根据项目id获取学科列表
     * @param  参数说明       body包含以下参数[
     *     project_id        项目id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-03
     * return string
     */
    public static function getSubjectList($body=[]) {
        //获取提交的参数
        try{
            //获取全部项目列表
            $data = Project::getSubjectList(self::$accept_data);
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

    /*
     * @param  description   项目管理-课程列表接口
     * @param author    dzj
     * @param ctime     2020-09-15
     * return string
     */
    public function getCourseAllList(){
        //获取提交的参数
        try{
            //获取全部项目列表
            $data = Course::getCourseAllList(self::$accept_data);
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
    public function doInsertRegion() {
        //获取提交的参数
        try{
            $data = RegionFee::doInsertRegion(self::$accept_data);
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
    public function doUpdateRegion() {
        //获取提交的参数
        try{
            $data = RegionFee::doUpdateRegion(self::$accept_data);
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
     * @param  description   项目管理-地区报名费详情方法
     * @param  参数说明       body包含以下参数[
     *     region_id         地区id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-07
     * return string
     */
    public function getRegionInfoById(){
        //获取提交的参数
        try{
            //获取地区报名费详情
            $data = RegionFee::getRegionInfoById(self::$accept_data);
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
     * @param  description   项目管理-地区列表接口
     * @param  参数说明       body包含以下参数[
     *     project_id        项目id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-03
     * return string
     */
    public function getRegionList(){
        //获取提交的参数
        try{
            //获取地区列表
            $data = RegionFee::getRegionList(self::$accept_data);
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
     * @param  description   项目管理-地区关联项目添加方法
     * @param  参数说明       body包含以下参数[
     *     project_id        项目id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-04
     * return string
     */
    public function doInsertCategoryRegion() {
        //获取提交的参数
        try{
            $data = CategoryRegion::doInsertCategoryRegion(self::$accept_data);
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
     * @param  description   项目管理-地区所有项目列表接口
     * @param  参数说明       body包含以下参数[
     *     project_id        项目id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-19
     * return string
     */
    public function getRegionProjectList(){
        //获取提交的参数
        try{
            //获取地区列表
            $data = CategoryRegion::getRegionProjectList(self::$accept_data);
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
     * @param  description   项目管理-学历成本关联项目添加方法
     * @param  参数说明       body包含以下参数[
     *     project_id        项目id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-04
     * return string
     */
    public function doInsertCategoryEducation() {
        //获取提交的参数
        try{
            $data = CategoryEducation::doInsertCategoryEducation(self::$accept_data);
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
     * @param  description   项目管理-学历成本所有项目列表接口
     * @param author    dzj
     * @param ctime     2020-09-19
     * return string
     */
    public function getEducationProjectList(){
        //获取提交的参数
        try{
            //获取学历提升成本项目列表
            $data = CategoryEducation::getEducationProjectList(self::$accept_data);
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
     * @param  description   项目管理-添加院校方法
     * @param  参数说明       body包含以下参数[
     *     parent_id         项目id
     *     child_id          学科id
     *     education_name    院校名称
     *     is_hide           是否显示/隐藏
     * ]
     * @param author    dzj
     * @param ctime     2020-09-02
     * return string
     */
    public function doInsertEducation() {
        //获取提交的参数
        try{
            $data = Education::doInsertEducation(self::$accept_data);
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
     * @param  description   项目管理-修改院校方法
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
    public function doUpdateEducation() {
        //获取提交的参数
        try{
            $data = Education::doUpdateEducation(self::$accept_data);
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
     * @param  description   项目管理-院校详情方法
     * @param  参数说明       body包含以下参数[
     *     school_id         院校id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-07
     * return string
     */
    public function getSchoolInfoById(){
        //获取提交的参数
        try{
            //获取院校详情
            $data = Education::getSchoolInfoById(self::$accept_data);
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
     * @param  description   项目管理-院校列表接口
     * @param  参数说明       body包含以下参数[
     *     project_id        项目id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-03
     * return string
     */
    public function getEducationList(){
        //获取提交的参数
        try{
            //获取院校列表
            $data = Education::getEducationList(self::$accept_data);
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
     * @param  description   项目管理-添加专业方法
     * @param  参数说明       body包含以下参数[
     *     education_id        院校id
     *     major_name          专业名称
     *     price               成本价格
     *     is_hide             是否隐藏(1是0否)
     * ]
     * @param author    dzj
     * @param ctime     2020-09-07
     * return string
     */
    public function doInsertMajor() {
        //获取提交的参数
        try{
            $data = Major::doInsertMajor(self::$accept_data);
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
     * @param  description   项目管理-添加专业方法
     * @param  参数说明       body包含以下参数[
     *     education_id        院校id
     *     major_name          专业名称
     *     price               成本价格
     *     is_hide             是否隐藏(1是0否)
     *     is_del              是否删除(1是)
     * ]
     * @param author    dzj
     * @param ctime     2020-09-07
     * return string
     */
    public function doUpdateMajor() {
        //获取提交的参数
        try{
            $data = Major::doUpdateMajor(self::$accept_data);
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
     * @param  description   项目管理-专业详情方法
     * @param  参数说明       body包含以下参数[
     *     major_id          专业id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-07
     * return string
     */
    public function getNajorInfoById(){
        //获取提交的参数
        try{
            //获取专业详情
            $data = Major::getNajorInfoById(self::$accept_data);
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
     * @param  description   项目管理-专业列表接口
     * @param  参数说明       body包含以下参数[
     *     education_id         院校id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-07
     * return string
     */
    public function getMajorList(){
        //获取提交的参数
        try{
            //获取专业列表
            $data = Major::getMajorList(self::$accept_data);
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
     * @param  description   项目管理-修改地区关联的项目
     * @param  参数说明       body包含以下参数[
     *     project_id        项目id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-04
     * return string
     */
    public function doUpdateCategoryRegion() {
        //获取提交的参数
        try{
            $data = CategoryRegion::doUpdateCategoryRegion(self::$accept_data);
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
     * @param  description   项目管理-修改学历成本关联的项目
     * @param  参数说明       body包含以下参数[
     *     project_id        项目id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-04
     * return string
     */
    public function doUpdateCategoryEducation() {
        //获取提交的参数
        try{
            $data = CategoryEducation::doUpdateCategoryEducation(self::$accept_data);
            if($data['code'] == 200){
                return response()->json(['code' => 200 , 'msg' => '修改成功']);
            } else {
                return response()->json(['code' => $data['code'] , 'msg' => $data['msg']]);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }

    //通过专业列表查询地区报名费
    public function academicList(){
        $body= self::$accept_data;
        //判断专业的id是否为空
        if(!isset($body['education_id']) || $body['education_id'] <= 0){
            return ['code' => 202 , 'msg' => '院校id不合法'];
        }
        //通过id获取地区报名费
        $major_list = Entey_fee::select('id as value','entry_name as label' , 'cost')->where('education_id' , $body['education_id'])->where('is_del' , 0)->get();
        return ['code' => 200 , 'msg' => '获取地区列表成功' , 'data' => $major_list];
    }
    /*
         * @param  单条详情
         * @param  $id     参数
         * @param  author  苏振文
         * @param  ctime   2021/1/20 15:00
         * return  array
         */
    public function enteyListOne(){
        $data = self::$accept_data;
        $one = Entey_fee::where(['id'=>$data['id'],'is_show'=>0,'is_del'=>0])->first();
        if($one){
            return response()->json(['code' => 200 , 'msg' => '查询成功','data'=>$one]);
        }else{
            return response()->json(['code' => 202 , 'msg' => '暂无信息']);
        }
    }
    /*
         * @param  修改
         * @param  id     参数
         * @param  education_id
         * @param  entry_name
         * @param  cost
         * @param  is_show
         * @param  author  苏振文
         * @param  ctime   2021/1/20 15:03
         * return  array
         */
    public function enteyUp(){
        $data = self::$accept_data;
        unset($data['/admin/project/enteyUp']);
        $data['update_time'] = date('Y-m-d H:i:s');
        $one = Entey_fee::where(['id'=>$data['id']])->update($data);
        if($one){
            return response()->json(['code' => 200 , 'msg' => '修改成功','data'=>$one]);
        }else{
            return response()->json(['code' => 202 , 'msg' => '修改失败']);
        }
    }
    /*
         * @param  删除
         * @param  id     参数
         * @param  author  苏振文
         * @param  ctime   2021/1/20 15:25
         * return  array
         */
    public function enteyDel(){
        $data = self::$accept_data;
        $data['update_time'] = date('Y-m-d H:i:s');
        $data['is_del'] = 1;
        $one = Entey_fee::where(['id'=>$data['id']])->update($data);
        if($one){
            return response()->json(['code' => 200 , 'msg' => '修改成功','data'=>$one]);
        }else{
            return response()->json(['code' => 202 , 'msg' => '修改失败']);
        }
    }
    /*
         * @param  添加
         * @param  author  苏振文
         * @param  ctime   2021/1/20 15:25
         * return  array
         */
    public function enteyAdd(){
        $data = self::$accept_data;
        unset($data['/admin/project/enteyAdd']);
        $data['create_time'] = date('Y-m-d H:i:s');
        $one = Entey_fee::insert($data);
        if($one){
            return response()->json(['code' => 200 , 'msg' => '修改成功','data'=>$one]);
        }else{
            return response()->json(['code' => 202 , 'msg' => '修改失败']);
        }
    }

}
