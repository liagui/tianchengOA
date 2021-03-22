<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pay_order_inside;
use App\Models\Student;

class StudentController extends Controller {
    //获取学员状态
    public function getStudentStatus(){
        //获取提交的参数
        try{
            $data = Student::getStudentStatus(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    //跟单
    public function documentary(){
        //获取提交的参数
        try{
            $data = Student::documentary(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    //获取跟单记录
    public function getdocumentary(){
        //获取提交的参数
        try{
            $data = Student::getdocumentary(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    //转单
    public function transferOrder(){
        //获取提交的参数
        try{
            $data = Student::transferOrder(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    //业绩查询 班主任自己的业绩
    public function getStudentPerformance(){
        //获取提交的参数
        try{
            $data = Student::getStudentPerformance(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    //学员总览 班主任自己的学员
    public function getStudent(){
        //获取提交的参数
        try{
            $data = Student::getStudent(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    //学员公海 被放入公海的所有学员订单
    public function getStudentSeas(){
        //获取提交的参数
        try{
            $data = Student::getStudentSeas(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    //更新资料收集状态
    public function updateConsigneeStatsu(){
        //获取提交的参数
        try{
            $data = Student::updateConsigneeStatsu(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }

    //班主任新增备注或修改备注
    public function haveremark(){
        //获取提交的参数
        try{
            $data = Student::haveremark(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
}
