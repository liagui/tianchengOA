<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StudentDatum extends Model {
 	//指定别的表名   权限表
    public $table = 'student_information';
    //时间戳设置
    public $timestamps = false;

    public static function getStudentDatumList($body){
    	$StudentDatumArr =  [];
        $oneSubject  = $twoSubject ='';
    	 //每页显示的条数
        $pagesize = (int)isset($body['pagesize']) && $body['pagesize'] > 0 ? $body['pagesize'] : 20;
        $page     = isset($body['page']) && $body['page'] > 0 ? $body['page'] : 1;
        $offset   = ($page - 1) * $pagesize;
        if(isset($body['project_id'])){
        	$subject = json_decode($body['project_id'],1);
            if(!empty($subject)){
                $oneSubject = $subject[0];
                if(!empty($subject[1])){
                    $twoSubject = $subject[1];
                }
            }else{
                unset($body['project_id']);
            }
    	}
        //学校id
        $school_id=[];
        if(isset($body['school_name'])){
            $school_id = School::select('id')->where('school_name','like','%'.$body['school_name'].'%')->where('is_del',0)->get();
        }

        $count = self::leftJoin('pay_order_inside','student_information.order_id','=','pay_order_inside.id')
        	->leftJoin('student','student.id','=','student_information.student_id')

        	->where(function($query) use ($body,$oneSubject,$twoSubject,$school_id) {
                if(!empty($school_id)){
                    $query->whereIn('student_information.school_id',$school_id);
                }else{
                    $query->whereIn('student_information.school_id',$body['school_ids']['data']);
                }
            	if(isset($body['audit_state']) && strlen($body['audit_state'])>0){ //所属审核状态
                	$query->where('student_information.audit_status',$body['audit_state']);
            	}
            	if(isset($body['gather_state']) && strlen($body['gather_state'])>0){
                	$query->where('pay_order_inside.consignee_status',$body['gather_state']);
            	}
                if(isset($body['course_id']) && strlen($body['course_id'])>0){
                    $query->where('pay_order_inside.course_id',$body['course_id']);
                }
            	if(isset($body['search']) && !empty($body['search'])){
                	$query->where('student.user_name','like','%'.$body['search'].'%')
                		->orWhere('student.mobile','like','%'.$body['search'].'%');
            	}
            	if(isset($body['project_id']) && !empty($body['project_id'])){
                    if(!empty($oneSubject)){
                        $query->where('student_information.project_id',$oneSubject);
                    }
                    if(!empty($twoSubject)){
                       $query->where('student_information.subject_id',$twoSubject);
                    }
                }

        	})->count();

    	if($count >0){
    		$adminArr = Admin::where(['is_del'=>1,'is_forbid'=>1])->select('id','real_name')->get()->toArray();
    		if(!empty($adminArr)){
    			$adminArr  = array_column($adminArr,'real_name','id');
    		}
    		$courseArr = Course::where(['is_del'=>0])->select('id','course_name')->get()->toArray();
    		if(!empty($courseArr)){
    			$courseArr  = array_column($courseArr,'course_name','id');
    		}
    		$schoolArr = School::where(['is_del'=>0,'is_open'=>0])->select('id','school_name')->get()->toArray();
    		if(!empty($schoolArr)){
    			$schoolArr  = array_column($schoolArr,'school_name','id');
    		}
            $categoryArr = Category::where(['is_del'=>0])->select('id','name')->get()->toArray();
            if(!empty($categoryArr)){
                $categoryArr  = array_column($categoryArr,'name','id');
            }
    		$StudentDatumArr  = self::leftJoin('pay_order_inside','student_information.order_id','=','pay_order_inside.id')
	        	->leftJoin('student','student.id','=','student_information.student_id')
	        	->where(function($query) use ($body,$oneSubject,$twoSubject,$school_id) {
                    if(!empty($school_id)){
                        $query->whereIn('student_information.school_id',$school_id);
                    }else{
                        $query->whereIn('student_information.school_id',$body['school_ids']['data']);
                    }
	            	if(isset($body['audit_state']) && strlen($body['audit_state'])>0){ //所属审核状态
	                	$query->where('student_information.audit_status',$body['audit_state']);
	            	}
	            	if(isset($body['gather_state']) && strlen($body['gather_state'])>0){
	                	$query->where('pay_order_inside.consignee_status',$body['gather_state']);
	            	}
	            	if(isset($body['search']) && !empty($body['search'])){
	                	$query->where('student.user_name','like','%'.$body['search'].'%')
	                		->orWhere('student.mobile','like','%'.$body['search'].'%');
	            	}
                    if(isset($body['course_id']) && strlen($body['course_id'])>0){
                        $query->where('pay_order_inside.course_id',$body['course_id']);
                    }
	            	if(isset($body['project_id']) && !empty($body['project_id'])){
    	                if(!empty($oneSubject)){
                            $query->where('student_information.project_id',$oneSubject);
                        }
                        if(!empty($twoSubject)){
                           $query->where('student_information.subject_id',$twoSubject);
                        }
	            	}
	        	})->select('student_information.student_id','student_information.project_id','student_information.subject_id','student_information.audit_id','student_information.gather_id','student_information.initiator_id','student_information.datum_create_time','student.mobile','student.user_name as student_name','pay_order_inside.consignee_status','student_information.audit_status','student_information.id','student_information.course_id','student_information.school_id','student_information.information_id','student_information.audit_desc')->orderBy('datum_create_time','desc')->offset($offset)->limit($pagesize)->get();
	        foreach($StudentDatumArr as $k=>&$v){
	        	$v['school_name'] = isset($schoolArr[$v['school_id']]) ? $schoolArr[$v['school_id']] :'';
	        	$v['project_name'] = isset($categoryArr[$v['project_id']]) ? $categoryArr[$v['project_id']] :'';
	        	$v['subject_name'] = isset($categoryArr[$v['subject_id']]) ? $categoryArr[$v['subject_id']] :'';
	        	$v['audit_name'] = isset($adminArr[$v['audit_id']]) ? $adminArr[$v['audit_id']] :'';
	        	$v['gather_name'] = isset($adminArr[$v['gather_id']]) ? $adminArr[$v['gather_id']] :'';
	        	$v['initiator_name'] = isset($adminArr[$v['initiator_id']]) ? $adminArr[$v['initiator_id']] :'';
                $v['course_name'] = isset($courseArr[$v['course_id']]) ? $courseArr[$v['course_id']] :'';
                $v['audit'] =  $v['audit_status'] <=0 ?'待审核':($v['audit_status']==1?'审核通过':'驳回');
                $v['consignee'] =  $v['consignee_status'] <=0 ?'待收集':($v['consignee_status']==1?'收集中':($v['consignee_status']==2?'已收集':"重新收集"));
                //类型
                if($v['project_name'] == '消防设施操作员初级' || $v['project_name'] == '消防设施操作员中级' || $v['project_name'] == '消防设施操作员高级'){
                     $v['moban_type'] =1;
                }else if($v['project_name'] == '学历提升'|| $v['project_name'] == '成考'|| $v['project_name'] == '高起专'|| $v['project_name'] == '专升本'|| $v['project_name'] == '高起本'){
                    $v['moban_type'] =2;

                }else if($v['project_name'] == '中专'){
                    $v['moban_type'] =3;
                }else if($v['project_name'] == '健康管理师'){
                    $v['moban_type'] =4;
                }
	        }
    	}
    	return ['code'=>200,'msg'=>'success','data'=>$StudentDatumArr,'total'=>$count];
    }

    public static function doStudentDatumInsert($body=[]){
    	//判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }
        //判断学员资料关系表的id是否为空
        if(!isset($body['id']) || empty($body['id']) || $body['id'] <= 0 ){
            return ['code' => 201 , 'msg' => 'id不合法'];
        }
        // if(!isset($body['moban_type']) || empty($body['moban_type'])){
        //     $body['moban_type'] = 4;
        // }

        // //1消防2学历3中专4健康
        // if($body['moban_type'] == 1){
        //     $body['type'] = 1;
        //     if(!isset($body['student_name']) || empty($body['student_name'])){
        //         return ['code' => 201 , 'msg' => '请输入学员姓名'];
        //     }
        //     if(!isset($body['student_sex']) || !in_array($body['student_sex'] , [0,1])){
        //         return ['code' => 201, 'msg' => '请选择性别'];
        //     }
        //     if(!isset($body['student_age']) || empty($body['student_age'])){
        //         return ['code' => 201 , 'msg' => '请输入学员年龄'];
        //     }
        //     if(!isset($body['student_region']) || empty($body['student_region'])){
        //         return ['code' => 201 , 'msg' => '请输入学员户籍'];
        //     }
        //     if(!isset($body['student_birth']) || empty($body['student_birth'])){
        //         return ['code' => 201 , 'msg' => '请输入出生年月'];
        //     }
        //     if(!isset($body['student_card']) || empty($body['student_card'])){
        //         return ['code' => 201 , 'msg' => '请输入学员身份证号'];
        //     }
        //     if(!isset($body['student_phone']) || empty($body['student_phone'])){
        //         return ['code' => 201 , 'msg' => '请输入手机号'];
        //     } else if(!preg_match('#^13[\d]{9}$|^14[\d]{9}$|^15[\d]{9}$|^17[\d]{9}$|^18[\d]{9}|^16[\d]{9}|^19[\d]{9}$#', $body['student_phone'])) {
        //         return ['code' => 201 , 'msg' => '手机号不合法'];
        //     }
        //     if(!isset($body['student_lovedone']) || empty($body['student_lovedone'])){
        //         return ['code' => 201 , 'msg' => '请输入学员亲人电话'];
        //     }
        //     if(!isset($body['national']) || empty($body['national'])){
        //         return ['code' => 201 , 'msg' => '请输入民族'];
        //     }
        //     if(!isset($body['political']) || empty($body['political'])){
        //         return ['code' => 201 , 'msg' => '请输入政治面貌'];
        //     }
        //     if(!isset($body['culture']) || empty($body['culture'])){
        //         return ['code' => 201 , 'msg' => '请输入文化程度'];
        //     }
        //     if(!isset($body['graduated_school']) || empty($body['graduated_school'])){
        //         return ['code' => 201 , 'msg' => '请输入毕业院校'];
        //     }
        //     if(!isset($body['professional']) || empty($body['professional'])){
        //         return ['code' => 201 , 'msg' => '请输入专业'];
        //     }
        //     if(!isset($body['student_region']) || empty($body['student_region'])){
        //         return ['code' => 201 , 'msg' => '请输入常住地'];
        //     }
        //     if(!isset($body['source']) || empty($body['source'])){
        //         return ['code' => 201 , 'msg' => '请输入考生来源'];
        //     }
        //     if(!isset($body['work_units']) || empty($body['work_units'])){
        //         return ['code' => 201 , 'msg' => '请输入工作单位'];
        //     }
        //     if(!isset($body['work_age']) || empty($body['work_age'])){
        //         return ['code' => 201 , 'msg' => '请输入工作年限'];
        //     }
        //     if(!isset($body['work_shebao']) || empty($body['work_shebao'])){
        //         return ['code' => 201 , 'msg' => '请选择是否有社保'];
        //     }
        //     if(!isset($body['typework']) || empty($body['typework'])){
        //         return ['code' => 201 , 'msg' => '请输入报考工种'];
        //     }
        //     if(!isset($body['typeworklevl']) || empty($body['typeworklevl'])){
        //         return ['code' => 201 , 'msg' => '请输入鉴定等级'];
        //     }
        //     $id = $body['id'];
        //     unset($body['id']);
        //     if(isset($body['/admin/datum/doDatumInsert'])){
        //         unset($body['/admin/datum/doDatumInsert']);
        //     }
        //     $body['create_time']=date('Y-m-d H:i:s');
        //     $body['type']= 3;
        //     $admin_name = isset(AdminLog::getAdminInfo()->admin_user->real_name) ? AdminLog::getAdminInfo()->admin_user->real_name : '';
        //     $StudentDatumArr = self::where(['id'=>$id])->first();
        //     if(empty($StudentDatumArr)){
        //         return ['code'=>201,'msg'=>'暂无数据信息'];
        //     }else{
        //         if($StudentDatumArr['audit_status'] == 2 && $StudentDatumArr['audit_id']>0 && $StudentDatumArr['gather_id']>0 ){
        //             //正常流程走完一边（驳回）
        //             $datumDelRes=Datum::where('id',$StudentDatumArr['id'])->update(['is_del'=>0,'update_time'=>date('Y-m-d H:i:s')]);
        //             if(!$datumDelRes){
        //                 DB::rollBack();
        //                 return ['code'=>203,'msg'=>' 资料提交失败，请重试 '];
        //             }
        //             $OrderUpdate = ['consignee_name'=>$admin_name,'update_time'=>date('Y-m-d H:i:s')]; //修改订单状态
        //         }else{
        //             $OrderUpdate = ['consignee_name'=>$admin_name,'consignee_status'=>2,'update_time'=>date('Y-m-d H:i:s')]; //consignee_status 2是已收集 0 待收集  1 收集中  3 重新收集
        //         }
        //         //走第一遍流程
        //         $datumId = Datum::insertGetId($body);  //添加资料
        //         if($datumId<=0){
        //             DB::rollBack();
        //             return ['code'=>203,'msg'=>'资料提交失败，请重试'];
        //         }
        //         $update = [
        //             'information_id'=>$datumId,
        //             'gather_id' => isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0,
        //             'datum_create_time'=>$body['create_time'],
        //             'update_time'=> date('Y-m-d H:i:s')
        //         ];
        //         $res = self::where('id',$id)->update($update); //修改学员资料订单关系表的内容
        //         if(!$res){
        //             DB::rollBack();
        //             return ['code'=>203,'msg'=>'资料提交失败,请重试！'];
        //         }

        //         $orderRes = Pay_order_inside::where('id',$StudentDatumArr['order_id'])->update($OrderUpdate); //修改订单表 资料收集状态

        //         if($orderRes){
        //             DB::commit();
        //             return ['code'=>200,'msg'=>'资料提交成功'];
        //         }else{
        //             DB::rollBack();
        //             return ['code'=>203,'msg'=>'资料提交失败,请重试！！'];
        //         }
        //     }


        // }else if($body['moban_type'] == 2){
        //     $body['type'] = 2;
        //     if(!isset($body['consultant']) || empty($body['consultant'])){
        //         return ['code' => 201 , 'msg' => '请输入咨询师'];
        //     }
        //     if(!isset($body['pay_time']) || empty($body['pay_time'])){
        //         return ['code' => 201 , 'msg' => '请输入付款日期'];
        //     }
        //     if(!isset($body['pay_price']) || empty($body['pay_price'])){
        //         return ['code' => 201 , 'msg' => '请输入付款金额'];
        //     }
        //     if(!isset($body['student_name']) || empty($body['student_name'])){
        //         return ['code' => 201 , 'msg' => '请输入学员姓名'];
        //     }
        //     if(!isset($body['student_card']) || empty($body['student_card'])){
        //         return ['code' => 201 , 'msg' => '请输入学员身份证号'];
        //     }
        //     //判断是否查看下属分校数据
        //     if(!isset($body['student_sex']) || !in_array($body['student_sex'] , [0,1])){
        //         return ['code' => 201, 'msg' => '请选择性别'];
        //     }
        //     //判断出生年月是否为空
        //     if(!isset($body['student_birth']) || empty($body['student_birth'])){
        //         return ['code' => 201 , 'msg' => '请输入出生年月'];
        //     }
        //     if(!isset($body['national']) || empty($body['national'])){
        //         return ['code' => 201 , 'msg' => '请输入民族'];
        //     }
        //     if(!isset($body['political']) || empty($body['political'])){
        //         return ['code' => 201 , 'msg' => '请输入政治面貌'];
        //     }
        //     if(!isset($body['student_region']) || empty($body['student_region'])){
        //         return ['code' => 201 , 'msg' => '生源地区'];
        //     }
        //     if(!isset($body['graduated_school']) || empty($body['graduated_school'])){
        //         return ['code' => 201 , 'msg' => '请输入报考学院'];
        //     }
        //     if(!isset($body['level']) || empty($body['level'])){
        //         return ['code' => 201 , 'msg' => '请输入层次'];
        //     }
        //     if(!isset($body['professional']) || empty($body['professional'])){
        //         return ['code' => 201 , 'msg' => '请输入专业'];
        //     }
        //     if(!isset($body['student_phone']) || empty($body['student_phone'])){
        //         return ['code' => 201 , 'msg' => '请输入手机号'];
        //     } else if(!preg_match('#^13[\d]{9}$|^14[\d]{9}$|^15[\d]{9}$|^17[\d]{9}$|^18[\d]{9}|^16[\d]{9}|^19[\d]{9}$#', $body['student_phone'])) {
        //         return ['code' => 201 , 'msg' => '手机号不合法'];
        //     }
        //     if(!isset($body['email']) || empty($body['email'])){
        //         return ['code' => 201 , 'msg' => '请输入邮箱'];
        //     }
        //     if(!isset($body['student_region']) || empty($body['student_region'])){
        //         return ['code' => 201 , 'msg' => '请输入联系地址'];
        //     }
        //     if(!isset($body['zipcode']) || empty($body['zipcode'])){
        //         return ['code' => 201 , 'msg' => '请输入邮编'];
        //     }
        //     if(!isset($body['branch_name']) || empty($body['branch_name'])){
        //         return ['code' => 201 , 'msg' => '请输入分校名称'];
        //     }
        //     $id = $body['id'];
        //     unset($body['id']);
        //     if(isset($body['/admin/datum/doDatumInsert'])){
        //         unset($body['/admin/datum/doDatumInsert']);
        //     }
        //     $body['create_time']=date('Y-m-d H:i:s');
        //     $body['type']= 3;
        //     $admin_name = isset(AdminLog::getAdminInfo()->admin_user->real_name) ? AdminLog::getAdminInfo()->admin_user->real_name : '';
        //     $StudentDatumArr = self::where(['id'=>$id])->first();
        //     if(empty($StudentDatumArr)){
        //         return ['code'=>201,'msg'=>'暂无数据信息'];
        //     }else{
        //         if($StudentDatumArr['audit_status'] == 2 && $StudentDatumArr['audit_id']>0 && $StudentDatumArr['gather_id']>0 ){
        //             //正常流程走完一边（驳回）
        //             $datumDelRes=Datum::where('id',$StudentDatumArr['id'])->update(['is_del'=>0,'update_time'=>date('Y-m-d H:i:s')]);
        //             if(!$datumDelRes){
        //                 DB::rollBack();
        //                 return ['code'=>203,'msg'=>' 资料提交失败，请重试 '];
        //             }
        //             $OrderUpdate = ['consignee_name'=>$admin_name,'update_time'=>date('Y-m-d H:i:s')]; //修改订单状态
        //         }else{
        //             $OrderUpdate = ['consignee_name'=>$admin_name,'consignee_status'=>2,'update_time'=>date('Y-m-d H:i:s')]; //consignee_status 2是已收集 0 待收集  1 收集中  3 重新收集
        //         }
        //         //走第一遍流程
        //         $datumId = Datum::insertGetId($body);  //添加资料
        //         if($datumId<=0){
        //             DB::rollBack();
        //             return ['code'=>203,'msg'=>'资料提交失败，请重试'];
        //         }
        //         $update = [
        //             'information_id'=>$datumId,
        //             'gather_id' => isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0,
        //             'datum_create_time'=>$body['create_time'],
        //             'update_time'=> date('Y-m-d H:i:s')
        //         ];
        //         $res = self::where('id',$id)->update($update); //修改学员资料订单关系表的内容
        //         if(!$res){
        //             DB::rollBack();
        //             return ['code'=>203,'msg'=>'资料提交失败,请重试！'];
        //         }

        //         $orderRes = Pay_order_inside::where('id',$StudentDatumArr['order_id'])->update($OrderUpdate); //修改订单表 资料收集状态

        //         if($orderRes){
        //             DB::commit();
        //             return ['code'=>200,'msg'=>'资料提交成功'];
        //         }else{
        //             DB::rollBack();
        //             return ['code'=>203,'msg'=>'资料提交失败,请重试！！'];
        //         }
        //     }
        // }else if($body['moban_type'] == 3){
        //     //判断学员姓名是否为空
        //     if(!isset($body['student_name']) || empty($body['student_name'])){
        //         return ['code' => 201 , 'msg' => '请输入学员姓名'];
        //     }
        //     //判断手机号是否为空
        //     if(!isset($body['student_phone']) || empty($body['student_phone'])){
        //         return ['code' => 201 , 'msg' => '请输入手机号'];
        //     } else if(!preg_match('#^13[\d]{9}$|^14[\d]{9}$|^15[\d]{9}$|^17[\d]{9}$|^18[\d]{9}|^16[\d]{9}|^19[\d]{9}$#', $body['student_phone'])) {
        //         return ['code' => 201 , 'msg' => '手机号不合法'];
        //     }
        //      //判断学员身份证号是否为空
        //     if(!isset($body['student_card']) || empty($body['student_card'])){
        //         return ['code' => 201 , 'msg' => '请输入学员身份证号'];
        //     }
        //     //判断出生年月是否为空
        //     if(!isset($body['student_birth']) || empty($body['student_birth'])){
        //         return ['code' => 201 , 'msg' => '请输入出生年月'];
        //     }
        //      //判断毕业学院是否为空
        //      if(!isset($body['graduated_school']) || empty($body['graduated_school'])){
        //         return ['code' => 201 , 'msg' => '请输入报考学院'];
        //     }
        //     //判断毕业专业是否为空
        //     if(!isset($body['professional']) || empty($body['professional'])){
        //         return ['code' => 201 , 'msg' => '请输入专业'];
        //     }
        //     //判断毕业年月是否为空
        //     if(!isset($body['startyears']) || empty($body['startyears'])){
        //         return ['code' => 201 , 'msg' => '请输入入学年月'];
        //     }
        //     //判断毕业年月是否为空
        //     if(!isset($body['years']) || empty($body['years'])){
        //         return ['code' => 201 , 'msg' => '请输入毕业年月'];
        //     }
        //     //判断毕业年月是否为空
        //     if(!isset($body['branch_name']) || empty($body['branch_name'])){
        //         return ['code' => 201 , 'msg' => '请输入分校名称'];
        //     }
        //     //判断毕业年月是否为空
        //     if(!isset($body['student_region']) || empty($body['student_region'])){
        //         return ['code' => 201 , 'msg' => '请输入毕业证寄回地址'];
        //     }
        //     $id = $body['id'];
        //     unset($body['id']);
        //     if(isset($body['/admin/datum/doDatumInsert'])){
        //         unset($body['/admin/datum/doDatumInsert']);
        //     }
        //     $body['create_time']=date('Y-m-d H:i:s');
        //     $body['type']= 3;
        //     $admin_name = isset(AdminLog::getAdminInfo()->admin_user->real_name) ? AdminLog::getAdminInfo()->admin_user->real_name : '';
        //     $StudentDatumArr = self::where(['id'=>$id])->first();
        //     if(empty($StudentDatumArr)){
        //         return ['code'=>201,'msg'=>'暂无数据信息'];
        //     }else{
        //         if($StudentDatumArr['audit_status'] == 2 && $StudentDatumArr['audit_id']>0 && $StudentDatumArr['gather_id']>0 ){
        //             //正常流程走完一边（驳回）
        //             $datumDelRes=Datum::where('id',$StudentDatumArr['id'])->update(['is_del'=>0,'update_time'=>date('Y-m-d H:i:s')]);
        //             if(!$datumDelRes){
        //                 DB::rollBack();
        //                 return ['code'=>203,'msg'=>' 资料提交失败，请重试 '];
        //             }
        //             $OrderUpdate = ['consignee_name'=>$admin_name,'update_time'=>date('Y-m-d H:i:s')]; //修改订单状态
        //         }else{
        //             $OrderUpdate = ['consignee_name'=>$admin_name,'consignee_status'=>2,'update_time'=>date('Y-m-d H:i:s')]; //consignee_status 2是已收集 0 待收集  1 收集中  3 重新收集
        //         }
        //         //走第一遍流程
        //         $datumId = Datum::insertGetId($body);  //添加资料
        //         if($datumId<=0){
        //             DB::rollBack();
        //             return ['code'=>203,'msg'=>'资料提交失败，请重试'];
        //         }
        //         $update = [
        //             'information_id'=>$datumId,
        //             'gather_id' => isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0,
        //             'datum_create_time'=>$body['create_time'],
        //             'update_time'=> date('Y-m-d H:i:s')
        //         ];
        //         $res = self::where('id',$id)->update($update); //修改学员资料订单关系表的内容
        //         if(!$res){
        //             DB::rollBack();
        //             return ['code'=>203,'msg'=>'资料提交失败,请重试！'];
        //         }

        //         $orderRes = Pay_order_inside::where('id',$StudentDatumArr['order_id'])->update($OrderUpdate); //修改订单表 资料收集状态

        //         if($orderRes){
        //             DB::commit();
        //             return ['code'=>200,'msg'=>'资料提交成功'];
        //         }else{
        //             DB::rollBack();
        //             return ['code'=>203,'msg'=>'资料提交失败,请重试！！'];
        //         }
        //     }
        // }else if($body['moban_type'] == 4){
            $body['type']= 4;
            //判断学员资料关系表的id是否为空
            if(!isset($body['branch_school']) || empty($body['branch_school']) || $body['branch_school'] <= 0 ){
                return ['code' => 201 , 'msg' => '学校标识不合法'];
            }
            //判断学员姓名是否为空
            if(!isset($body['student_name']) || empty($body['student_name'])){
                return ['code' => 201 , 'msg' => '请输入学员姓名'];
            }
            //判断是否查看下属分校数据
            if(!isset($body['student_sex']) || !in_array($body['student_sex'] , [0,1])){
                return ['code' => 201, 'msg' => '请选择性别'];
            }
            //判断手机号是否为空
            if(!isset($body['student_phone']) || empty($body['student_phone'])){
                return ['code' => 201 , 'msg' => '请输入手机号'];
            } else if(!preg_match('#^13[\d]{9}$|^14[\d]{9}$|^15[\d]{9}$|^17[\d]{9}$|^18[\d]{9}|^16[\d]{9}|^19[\d]{9}$#', $body['student_phone'])) {
                return ['code' => 201 , 'msg' => '手机号不合法'];
            }
            //判断学员身份证号是否为空
            if(!isset($body['student_card']) || empty($body['student_card'])){
                return ['code' => 201 , 'msg' => '请输入学员身份证号'];
            }
            //判断户籍地址是否为空
            if(!isset($body['address']) || empty($body['address'])){
                return ['code' => 201 , 'msg' => '请输入户籍地址'];
            }
            $address = json_decode($body['address'],1);
            if(!isset($address[0])){
                return ['code' => 201 , 'msg' => '请选择户籍地址省份'];
            }else{
                if(!isset($address[1])){
                    return ['code' => 201 , 'msg' => '请选择户籍地址市区'];
                }
            }
            $body['address_province_id'] = $address[0];
            $body['address_city_id'] = $address[1];
            unset($body['address']);
            //判断报考月份是否为空
            if(!isset($body['month']) || empty($body['month'])){
                return ['code' => 201 , 'msg' => '请选择报考月份'];
            }
            //判断报考地区是否为空
            if(!isset($body['sign_region']) || empty($body['sign_region'])){   //报考地区数组
                return ['code' => 201 , 'msg' => '请选择报考地区'];
            }
            $sign_region_address = json_decode($body['sign_region'],1);
            if(!isset($sign_region_address[0])){
                return ['code' => 201 , 'msg' => '请选择报考地区省份'];
            }else{
                if(!isset($sign_region_address[1])){
                    return ['code' => 201 , 'msg' => '请选择报考地区市区'];
                }
            }
            $body['sign_region_province_id'] = $sign_region_address[0];
            $body['sign_region_city_id'] = $sign_region_address[1];
            unset($body['sign_region']);
            //判断备考地区是否为空
            if(!isset($body['reference_region']) || empty($body['reference_region'])){  //备考地区数组
                return ['code' => 201 , 'msg' => '请选择备考地区'];
            }
            $reference_region_address = json_decode($body['reference_region'],1);
            if(!isset($reference_region_address[0])){
                return ['code' => 201 , 'msg' => '请选择备考地区省份'];
            }else{
                if(!isset($reference_region_address[1])){
                    return ['code' => 201 , 'msg' => '请选择备考地区市区'];
                }
            }
            $body['reference_region_province_id'] = $reference_region_address[0];
            $body['reference_region_city_id'] = $reference_region_address[1];
            unset($body['reference_region']);

            // if($body['sign_region_id'] != $body['reference_region_id']){
            //     return ['code' => 201 , 'msg' => '报考地区与备考地区不一致！'];
            // }
            //判断文化程度是否为空
            if(!isset($body['culture']) || empty($body['culture'])){
                return ['code' => 201 , 'msg' => '请选择文化程度'];
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
            //判断学信网账号是否为空
            if(!isset($body['xx_account']) || empty($body['xx_account'])){
                return ['code' => 201 , 'msg' => '请输入学信网账号'];
            }
            //判断学信网密码是否为空
            if(!isset($body['xx_password']) || empty($body['xx_password'])){
                return ['code' => 201 , 'msg' => '请输入学信网密码'];
            }
            //判断2寸白底照片是否为空
            if(!isset($body['photo']) || empty($body['photo'])){
                return ['code' => 201 , 'msg' => '请上传2寸白底照片'];
            }
            //判断身份证正面照片是否为空
            if(!isset($body['card_photo_front']) || empty($body['card_photo_front'])){
                return ['code' => 201 , 'msg' => '请上传身份证正面照片'];
            }
            //判断身份证背面照片是否为空
            if(!isset($body['card_photo_contrary']) || empty($body['card_photo_contrary'])){
                return ['code' => 201 , 'msg' => '请上传身份证背面照片'];
            }
            //判断身份证正反面扫描是否为空
            if(!isset($body['card_photo_scanning']) || empty($body['card_photo_scanning'])){
                return ['code' => 201 , 'msg' => '请上传份证正反面扫描'];
            }
            //判断毕业证照片是否为空
            if(!isset($body['diploma_photo']) || empty($body['diploma_photo'])){
                return ['code' => 201 , 'msg' => '请上传毕业证照片'];
            }
            //判断毕业证扫描是否为空
            if(!isset($body['diploma_scanning']) || empty($body['diploma_scanning'])){
                return ['code' => 201 , 'msg' => '请上传毕业证扫描'];
            }
            $id = $body['id'];
            unset($body['id']);
            if(isset($body['/admin/datum/doDatumInsert'])){
                unset($body['/admin/datum/doDatumInsert']);
            }


            $body['create_time']=date('Y-m-d H:i:s');
            DB::beginTransaction();
            $admin_name = isset(AdminLog::getAdminInfo()->admin_user->real_name) ? AdminLog::getAdminInfo()->admin_user->real_name : '';
            $StudentDatumArr = self::where(['id'=>$id])->first();
            if(empty($StudentDatumArr)){
                return ['code'=>201,'msg'=>'暂无数据信息'];
            }else{
                if($StudentDatumArr['audit_status'] == 2 && $StudentDatumArr['audit_id']>0 && $StudentDatumArr['gather_id']>0 ){
                    //正常流程走完一边（驳回）
                    $datumDelRes=Datum::where('id',$StudentDatumArr['id'])->update(['is_del'=>0,'update_time'=>date('Y-m-d H:i:s')]);
                    if(!$datumDelRes){
                        DB::rollBack();
                        return ['code'=>203,'msg'=>' 资料提交失败，请重试 '];
                    }
                    $OrderUpdate = ['consignee_name'=>$admin_name,'update_time'=>date('Y-m-d H:i:s')]; //修改订单状态
                }else{
                    $OrderUpdate = ['consignee_name'=>$admin_name,'consignee_status'=>2,'update_time'=>date('Y-m-d H:i:s')]; //consignee_status 2是已收集 0 待收集  1 收集中  3 重新收集
                }
                //走第一遍流程
                $datumId = Datum::insertGetId($body);  //添加资料
                if($datumId<=0){
                    DB::rollBack();
                    return ['code'=>203,'msg'=>'资料提交失败，请重试'];
                }
                $update = [
                    'information_id'=>$datumId,
                    'gather_id' => isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0,
                    'datum_create_time'=>$body['create_time'],
                    'update_time'=> date('Y-m-d H:i:s')
                ];
                $res = self::where('id',$id)->update($update); //修改学员资料订单关系表的内容
                if(!$res){
                    DB::rollBack();
                    return ['code'=>203,'msg'=>'资料提交失败,请重试！'];
                }

                $orderRes = Pay_order_inside::where('id',$StudentDatumArr['order_id'])->update($OrderUpdate); //修改订单表 资料收集状态

                if($orderRes){
                    DB::commit();
                    return ['code'=>200,'msg'=>'资料提交成功'];
                }else{
                    DB::rollBack();
                    return ['code'=>203,'msg'=>'资料提交失败,请重试！！'];
                }
            }
    //   }
    }

    public static function getDatumById($body){
        $admin_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
        //判断学员资料id是否为空
        if(!isset($body['datum_id']) || empty($body['datum_id']) || $body['datum_id'] <= 0){
            return ['code' => 201 , 'msg' => '请先提交资料！'];
        }
        $datumArr = Datum::where('id',$body['datum_id'])->first();
        if(is_null($datumArr)){
            $datumArr = [];
        }else{
            $datumArr['sign_region']= [$datumArr['sign_region_province_id'],$datumArr['sign_region_city_id']];
            $datumArr['reference_region']= [$datumArr['reference_region_province_id'],$datumArr['reference_region_city_id']];
            $datumArr['address']= [$datumArr['address_province_id'],$datumArr['address_city_id']];
        }
        return ['code'=>200,'msg'=>'Success','data'=>$datumArr];
    }
    //审核状态
    public static function doUpdateAudit($body){
        $admin_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
        if(!isset($body['id']) || empty($body['id']) || $body['id'] <= 0){
            return ['code' => 201 , 'msg' => 'id不合法'];
        }
         //判断毕业证照片是否为空
        if(!isset($body['audit_state']) || empty($body['audit_state'])){
            return ['code' => 201 , 'msg' => '请选择审核状态'];
        }
        DB::beginTransaction();
        $update['audit_desc'] = isset($body['audit_desc']) && !empty($body['audit_desc'])?$body['audit_desc']:'';
        $update['audit_id']  =  $admin_id;
        $update['audit_status'] = $body['audit_state'];
        $udpate['update_time'] =date('Y-m-d H:i:s');
        $res = self::where('information_id',$body['id'])->update($update);
        if(!$res){
            DB::rollBack();
            return ['code'=>203,'msg'=>'审核失败,请重试'];
        }
        $studentDatumArr  = self::where('information_id',$body['id'])->select('order_id')->first();
        $consignee_status = $body['audit_state']  == 1 ? 2:3;
        $orderRes = Pay_order_inside::where('id',$studentDatumArr['order_id'])->update(['consignee_status'=>$consignee_status,'update_time'=>date('Y-m-d H:i:s')]);
        if($orderRes){
            AdminLog::insertAdminLog([
                'admin_id'       =>   $admin_id ,
                'module_name'    =>  'datum' ,
                'route_url'      =>  'admin/datum/doUpdateAudit' ,
                'operate_method' =>  'update' ,
                'content'        =>  json_encode($body),
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
            DB::commit();
            return ['code'=>200,'msg'=>'审核成功'];
        }else{
            DB::rollBack();
            return ['code'=>203,'msg'=>'审核失败,请重试!'];
        }

    }
    //发起人信息
    public static function getInitiatorById($body){
        if(!isset($body['id']) || empty($body['id']) || $body['id'] <= 0){
            return ['code' => 201 , 'msg' => 'id不合法'];
        }
        $info = Admin::where(['is_del'=>1,'is_forbid'=>1])->select('real_name','mobile','wx')->first();
        if(is_null($info)){
            $info = [];
        }
        return ['code'=>200,'msg'=>'Success','data'=>$info];
    }
    //获取资料数量
    public static function getDatumCount($body){
        $subject = [];
        $school_id=[];
        if(isset($data['school_name'])){
            $school_id = School::select('id')->where('school_name','like','%'.$body['school_name'].'%')->where('is_del',0)->get();
        }
        if(isset($body['subject']) && !empty($body['subject'])){
            $subject = json_decode($body['subject'],1);
        }
        $count = self::where(function($query) use ($school_id,$subject) {
                    if(!empty($school_id)){
                        $query->whereIn('school_id',$school_id);
                    }
                    if(!empty($subject)){ //所属审核状态
                        $query->where('project_id',$subject[0]);
                        if(isset($subject[1]) && $subject[1]>0 ){
                            $query->where('subject_id',$subject[1]);
                        }
                    }
                    $query->where('audit_status',1);
                })->select('id')->count();
        return ['code'=>200,'msg'=>'success','total'=>$count];
    }


}
