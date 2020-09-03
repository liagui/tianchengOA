<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\MaterialListing;
use App\Models\TeacherSchool;
use App\Models\TeacherCategory;
use Illuminate\Support\Facades\DB;
class Teacher extends Model {
    //指定别的表名
    public $table = 'admin';
    //时间戳设置
    public $timestamps = false;

    public static function getTeacherList($data){
        //每页显示的条数
        $pagesize = (int)isset($data['pageSize']) && $data['pageSize'] > 0 ? $data['pageSize'] : 20;
        $page     = isset($data['page']) && $data['page'] > 0 ? $data['page'] : 1;
        $offset   = ($page - 1) * $pagesize;
        $count = self::where('admin.role_id',3)->where(function($query) use ($data){
            if(isset($data['school_id']) && !empty(isset($data['school_id']))){
                $query->where('admin.school_id','like','%'.$data['school_id'].'%');
            }
            if(isset($data['category_id']) && !empty(isset($data['category_id']))){
                $query->where('admin.category_id','like','%'.$data['category_id'].'%');
            }
            if(isset($data['status']) && $data['status'] != -1){
                $query->where(['admin.status'=>$data['status']]);
            }
            if(isset($data['keyword']) && !empty(isset($data['keyword']))){
                $query->where('admin.real_name','like','%'.$data['keyword'].'%')->orWhere('admin.mobile','like','%'.$data['keyword'].'%')->orWhere('admin.wx','like','%'.$data['keyword'].'%');
            }
        })->count();
        $data = self::where('admin.role_id',3)->where(function($query) use ($data){
            if(isset($data['school_id']) && !empty(isset($data['school_id']))){
                $query->where('admin.school_id','like','%'.$data['school_id'].'%');
            }
            if(isset($data['category_id']) && !empty(isset($data['category_id']))){
                $query->where('admin.category_id','like','%'.$data['category_id'].'%');
            }
            if(isset($data['status']) && $data['status'] != -1){
                $query->where(['admin.status'=>$data['status']]);
            }
            if(isset($data['keyword']) && !empty(isset($data['keyword']))){
                $query->where('admin.real_name','like','%'.$data['keyword'].'%')->orWhere('admin.mobile','like','%'.$data['keyword'].'%')->orWhere('admin.wx','like','%'.$data['keyword'].'%');
            }
        })->offset($offset)->limit($pagesize)->get();
        foreach($data as $k=>&$v){
            $school = explode(",",$v['school_id']);
            //查询分校名称
            $v['school'] = DB::table("school")->select("school_name")->whereIn('id',$school)->get()->toArray();
            //查询项目名称
            $category = explode(",",$v['category_id']);
            $v['category'] = DB::table("category")->select("name")->whereIn('id',$category)->get()->toArray();
        }
        foreach($data as $k=>$vv){
            $data[$k]['school'] = implode(',',array_column($vv['school'] , 'school_name'));
            $data[$k]['category'] = implode(',',array_column($vv['category'] , 'name'));
        }
        $page=[
            'pageSize'=>$pagesize,
            'page' =>$page,
            'total'=>$count
        ];
        if($data){
            return ['code' => 200, 'msg' => '查询成功', 'data' => $data,'page'=>$page];
        }else{
            return ['code' => 202, 'msg' => '查询暂无数据'];
        }
    }
    public static function createTeacher($data){
        //创建班主任账号
        if($data['password'] != $data['verifypassword']){
            return ['code' => 202, 'msg' => '两次密码输入不一致'];
        }
        $teacher['username'] = $data['username'];
        //账户唯一验证
        $admin = self::where('username',$data['username'])->first();
        if($admin){
            return ['code' => 200 , 'msg' => '账户已存在'];
        }
        $teacher['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $teacher['real_name'] = $data['real_name'];
        $teacher['create_time'] = date("Y-m-d H:i:s");
        $teacher['role_id'] = 3;
        $teacher['create_id'] = 1;
        $teacher['school_id'] = implode(',',json_decode($data['teacher_school']));
        $teacher['category_id'] = implode(',',json_decode($data['teacher_category']));
        $res = self::insert($teacher);
        if($res){
            return ['code' => 200 , 'msg' => '创建班主任成功'];
        }else{
            return ['code' => 202 , 'msg' => '创建班主任失败'];
        }
    }
    public static function updateTeacherStatus($data){
        $teacher = self::where(['id'=>$data['teacher_id'],'role_id'=>3])->first();
        if(empty($teacher)){
            return ['code' => 202 , 'msg' => '请检查账号是否存在'];
        }
        if($teacher['status'] == 1){
            $update['status'] = 0;
        }else{
            $update['status'] = 1;
        }
        $res = self::where('id',$data['teacher_id'])->update($update);
        if($res){
            return ['code' => 200 , 'msg' => '更新值班状态成功'];
        }else{
            return ['code' => 202 , 'msg' => '更新值班状态失败'];
        }
    }


}
