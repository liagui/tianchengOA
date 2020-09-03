<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Teacher;
class TeacherController extends Controller
{
    //获取班主任列表
    public function getTeacherList(){
        //获取提交的参数
        try{
            $data = Teacher::getTeacherList(self::$accept_data);
            return response()->json($data);
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
}
