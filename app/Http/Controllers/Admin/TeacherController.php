<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Teacher;
use App\Models\AdminLog;
use Maatwebsite\Excel\Facades\Excel;
class TeacherController extends Controller
{
    //获取班主任列表分页
    public function getTeacherList(){
        //获取提交的参数
        try{
            $data = Teacher::getTeacherList(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    //获取班主任列表
    public function getTeacherListAll(){
        //获取提交的参数
        try{
            $data = Teacher::getTeacherListAll(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    //导出班主任业绩数据
    public function exportTeacherPerformance(){
        //获取提交的参数
        try{
            $data = self::$accept_data;
            $res = $this->underlingLook(isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0);
            $data['schoolids'] = $res['data'];
            return Excel::download(new \App\Exports\TeacherExport($data), '班主任业绩导出.xlsx');

        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    //创建班主任
    public function createTeacher(){
        //获取提交的参数
        try{
            $data = Teacher::createTeacher(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    //获取班主任详情
    public function GetTeacherOne(){
        //获取提交的参数
        try{
            $data = Teacher::GetTeacherOne(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    //更新班主任绑定分校
    public function UpdateTeacherSchool(){
        //获取提交的参数
        try{
            $data = Teacher::UpdateTeacherSchool(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    //更新班主任绑定项目
    public function UpdateTeacherCategory(){
        //获取提交的参数
        try{
            $data = Teacher::UpdateTeacherCategory(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    //更新班主任值班状态
    public function updateTeacherStatus(){
        //获取提交的参数
        try{
            $data = Teacher::updateTeacherStatus(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    //更新班主任值班状态
    public function updateTeacherSeasStatus(){
        //获取提交的参数
        try{
            $data = Teacher::updateTeacherSeasStatus(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }

    //获取班主任业绩
    public function getTeacherPerformance(){
        //获取提交的参数
        try{
            $data = Teacher::getTeacherPerformance(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    //获取班主任业绩详情
    public function getTeacherPerformanceOne(){
        //获取提交的参数
        try{
            $data = Teacher::getTeacherPerformanceOne(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
}
