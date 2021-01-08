<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;

class CoursesubjectController extends Controller {
    /*
       * @param  根据学科查询课程
       * @param  $user_id     参数
       * @param  author  苏振文
       * @param  ctime   2021/1/8 10:54
       * return  array
       */
    public function SubjectToCourse(){
        $data = self::$accept_data;
        $where=[];
        if(isset($data['project'])){
            $subject = json_decode($data['project'],1);
            if(!empty($subject)){
                $where['category_one_id'] = $subject[0];
                if(!empty($subject[1])){
                    $where['category_tow_id'] = $subject[1];
                }
            }
        }
        $return = Course::select('id as value','course_name as label')->where($where)->where(['is_del'=>0,'is_hide'=>0])->get();
        return response()->json(['code'=>200,'msg'=>'获取成功','data'=>$return]);
    }
}
