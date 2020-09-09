<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentDatum extends Model {
 	//指定别的表名   权限表
    public $table = 'student_information';
    //时间戳设置
    public $timestamps = false;

    public static function getList($body){
    	$StudentDatumArr = [];
    	 //每页显示的条数
        $pagesize = (int)isset($body['pageSize']) && $body['pageSize'] > 0 ? $body['pageSize'] : 20;
        $page     = isset($body['page']) && $body['page'] > 0 ? $body['page'] : 1;
        $offset   = ($page - 1) * $pagesize;
        if(isset($body['subject']) && !empty($body['subject'])){
        	$subject = json_decode($body['subject'],1);
        	$oneSubject = $subject[0];
        	$twoSubject = isset($subject[1]) && $subject[1]>0 ?$subject[1]:0;
    	}
        $count = self::leftJoin('student_information.order_id','=','pay_order_inside.id')
        	->leftJoin('student.id','=','student_information.student_id')
        	->where(function($query) use ($body) {
        		if(isset($body['school_id']) && !empty($body['school_id'])){ //所属学校
                	$query->where('student_information.school_id',$body['school_id']);
            	}
            	if(isset($body['audit_state']) && !empty($body['audit_state'])){ //所属审核状态
                	$query->where('student_information.audit_status',$body['audit_state']);
            	}
            	if(isset($body['gather_state']) && !empty($body['gather_state'])){
                	$query->where('student_information.consignee_status',$body['gather_state']);
            	}
            	if(isset($body['search']) && !empty($body['search'])){
                	$query->where('student.user_name','like','%'.$body['search'].'%')
                		->orWhere('student.mobile','like','%'.$body['search'].'%');
            	}
            	if(isset($body['subject']) && !empty($body['subject'])){
                	$query->where('student_information.project_id',$oneSubject);
                	$query->where('student_information.subject_id',$twoSubject)
            	}
        	})->count();
    	if($count >0){
    		$adminArr = Admin::where(['is_del'=>1,'is_forbid'=>1])->select('id','real_name')->get()->toArray();
    		if(!empty($adminArr)){
    			$adminArr  = array_column($adminArr,'real_name','id');
    		}
    		$courseArr = Course::where(['is_del'=>1])->select('id','course_name')->get()->toArray();
    		if(!empty($courseArr)){
    			$courseArr  = array_column($courseArr,'course_name','id');
    		}
    		$schoolArr = School::where(['is_del'=>0,'is_open'=>0])->select('id','school_name')->get()->toArray();
    		if(!empty($schoolArr)){
    			$schoolArr  = array_column($schoolArr,'school_name','id');
    		}
    		$StudentDatumArr  = self::leftJoin('student_information.order_id','=','pay_order_inside.id')
	        	->leftJoin('student.id','=','student_information.student_id')
	        	->where(function($query) use ($body) {
	        		if(isset($body['school_id']) && !empty($body['school_id'])){ //所属学校
	                	$query->where('student_information.school_id',$body['school_id']);
	            	}
	            	if(isset($body['audit_state']) && !empty($body['audit_state'])){ //所属审核状态
	                	$query->where('student_information.audit_status',$body['audit_state']);
	            	}
	            	if(isset($body['gather_state']) && !empty($body['gather_state'])){
	                	$query->where('student_information.consignee_status',$body['gather_state']);
	            	}
	            	if(isset($body['search']) && !empty($body['search'])){
	                	$query->where('student.user_name','like','%'.$body['search'].'%')
	                		->orWhere('student.mobile','like','%'.$body['search'].'%');
	            	}
	            	if(isset($body['subject']) && !empty($body['subject'])){
	                	$query->where('student_information.project_id',$oneSubject);
	                	$query->where('student_information.subject_id',$twoSubject)
	            	}
	        	})->offset($offset)->limit($limit)->get();
	        foreach($StudentDatum as $k=>&$v){
	        	$v['school_name'] = isset($schoolArr[$v['school_id']]) ? $schoolArr[$v['school_id']] :'';
	        	$v['project_name'] = isset($courseArr[$v['project_id']]) ? $courseArr[$v['project_id']] :'';
	        	$v['subject_name'] = isset($courseArr[$v['subject_id']]) ? $courseArr[$v['subject_id']] :'';
	        	$v['audit_name'] = isset($adminArr[$v['audit_id']]) ? $adminArr[$v['audit_id']] :'';
	        	$v['gather_name'] = isset($adminArr[$v['gather_id']]) ? $adminArr[$v['gather_id']] :'';
	        	$v['initiator_name'] = isset($adminArr[$v['initiator_name']]) ? $adminArr[$v['initiator_name']] :'';
	        } 
    	}
    	return ['code'=>200,'msg'=>'success','data'=>$StudentDatumArr,'total'=>$count];
    }

    public static function doStudentDatumInsert($body=[]){
    	//判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }
        //判断分校id是否为空
        if((!isset($body['datum_id']) || empty($body['datum_id'])) && $body['datum_id'] <= 0 ){
            return ['code' => 201 , 'msg' => 'datum_id不合法'];
        }
        //判断分校id是否为空
        if((!isset($body['school_id']) || empty($body['school_id'])) && $body['school_id'] <= 0 ){
            return ['code' => 201 , 'msg' => '学校id不合法'];
        }
        
        //判断报考月份是否为空
        if(!isset($body['month']) || empty($body['month'])){
            return ['code' => 201 , 'msg' => '请输入报考月份'];
        }
        
        //判断报考地区是否为空
        if(!isset($body['sign_region']) || empty($body['sign_region'])){
            return ['code' => 201 , 'msg' => '请输入报考地区'];
        }
        
        //判断备考地区是否为空
        if(!isset($body['reference_region']) || empty($body['reference_region'])){
            return ['code' => 201 , 'msg' => '请输入备考地区'];
        }
        //判断文化程度是否为空
        if(!isset($body['culture']) || empty($body['culture'])){
            return ['code' => 201 , 'msg' => '请输入文化程度'];
        }
        //判断毕业学院是否为空
        if(!isset($body['graduated_school']) || empty($body['graduated_school'])){
            return ['code' => 201 , 'msg' => '请输入毕业学院'];
        }
        //判断毕业专业是否为空
        if(!isset($body['professional']) || empty($body['professional'])){
            return ['code' => 201 , 'msg' => '请输入毕业专业'];
        }
        //判断毕业年月是否为空
        if(!isset($body['years']) || empty($body['years'])){
            return ['code' => 201 , 'msg' => '请输入毕业年月'];
        }
    
        //判断毕业年月是否为空
        if(!isset($body['xx_account']) || empty($body['xx_account'])){
            return ['code' => 201 , 'msg' => '请输入税点比例'];
        }
        //判断毕业年月是否为空
        if(!isset($body['years']) || empty($body['years'])){
            return ['code' => 201 , 'msg' => '请输入税点比例'];
        }
        //判断学信网账户是否为空
        if(!isset($body['xx_account']) || empty($body['xx_account'])){
            return ['code' => 201 , 'msg' => '请输入税点比例'];
        }
        //判断学信网密码是否为空
        if(!isset($body['xx_password']) || empty($body['xx_password'])){
            return ['code' => 201 , 'msg' => '请输入税点比例'];
        }
        //判断2寸白底照片是否为空
        if(!isset($body['photo']) || empty($body['photo'])){
            return ['code' => 201 , 'msg' => '请输入税点比例'];
        }
        //判断身份证正面照片是否为空
        if(!isset($body['card_photo_front']) || empty($body['card_photo_front'])){
            return ['code' => 201 , 'msg' => '请输入税点比例'];
        }
        //判断身份证背面照片是否为空
        if(!isset($body['card_photo_contrary']) || empty($body['card_photo_contrary'])){
            return ['code' => 201 , 'msg' => '请输入税点比例'];
        }
        //判断身份证正反面扫描是否为空
        if(!isset($body['card_photo_scanning']) || empty($body['card_photo_scanning'])){
            return ['code' => 201 , 'msg' => '请输入税点比例'];
        }
         //判断毕业证照片是否为空
        if(!isset($body['diploma_photo']) || empty($body['diploma_photo'])){
            return ['code' => 201 , 'msg' => '请输入税点比例'];
        }
         //判断毕业证扫描是否为空
        if(!isset($body['diploma_scanning']) || empty($body['diploma_scanning'])){
            return ['code' => 201 , 'msg' => '请输入税点比例'];
        }
         //判断本人手持身份证照片是否为空
        if(!isset($body['my_photo']) || empty($body['my_photo'])){
            return ['code' => 201 , 'msg' => '请输入税点比例'];
        }
         //判断毕业证扫描是否为空
        if(!isset($body['diploma_scanning']) || empty($body['diploma_scanning'])){
            return ['code' => 201 , 'msg' => '请输入税点比例'];
        }
         //判断学员姓名是否为空
        if(!isset($body['student_name']) || empty($body['student_name'])){
            return ['code' => 201 , 'msg' => '请输入税点比例'];
        }
         //判断学员手机号是否为空
        if(!isset($body['student_phone']) || empty($body['student_phone'])){
            return ['code' => 201 , 'msg' => '请输入税点比例'];
        }
         //判断学员身份证号是否为空
        if(!isset($body['student_card']) || empty($body['student_card'])){
            return ['code' => 201 , 'msg' => '请输入税点比例'];
        }
         //判断户籍地址是否为空
        if(!isset($body['address']) || empty($body['address'])){
            return ['code' => 201 , 'msg' => '请输入税点比例'];
        }
        //判断是否查看下属分校数据
        if(!isset($body['student_sex']) || !in_array($body['student_sex'] , [0,1])){
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
    }

}