<?php
namespace App\Http\Controllers\Admin;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Tools\CurrentAdmin;
use OSS\Core\OssException;
use OSS\OssClient;
class CommonController extends BaseController {
    /*
     * @param  description   获取添加账号信息
     * @param  id            当前登录用户id
     * @param author    lys
     * @param ctime     2020-04-29
    */
    public function getInsertAdminUser(){
            $adminId = CurrentAdmin::user()['id'];
            $data =  \App\Models\Admin::getUserOne(['id'=>$adminId]);
            if($data['code'] != 200){
                return response()->json(['code' => $data['code'] , 'msg' => $data['msg']]);
            } 
            $adminUserSchoolId = $data['data']['school_id'];
            $adminUserSchoolType = $data['data']['school_status']; 

            // if($adminUserSchoolType >0){
                //总校
            // $schoolData = \App\Models\School::getSchoolAlls(['id','name']);
            // }else{
                // //分校
            $schoolData = \App\Models\School::getSchoolOne(['id'=>$adminUserSchoolId],['id','name']);
            //}
            $rolAuthArr = \App\Models\Roleauth::getRoleAuthAlls(['school_id'=>$adminUserSchoolId,'is_del'=>1],['id','role_name']);
            $arr = [
                'school'=>$schoolData['data'],
                'role_auth'=>$rolAuthArr
            ];
            return response()->json(['code' => 200 , 'msg' => '获取信息成功' , 'data' => $arr]);
    }

    /*
     * @param  description   获取角色权限列表
     * @param author    lys
     * @param ctime     2020-04-29
    */
    public  function getRoleAuth(){
         try{
            $adminId = CurrentAdmin::user()['id'];
         
            $data =  \App\Models\Admin::getUserOne(['id'=>$adminId]);
            if($data['code'] != 200){
                return response()->json(['code' => $data['code'] , 'msg' => $data['msg']]);
            } 
            $adminUserSchoolId = $data['data']['school_id'];
            $adminUserSchoolType = $data['data']['school_status']; 
            
            if($adminUserSchoolType >0){
                //总校 Auth 
                $roleAuthArr = \App\Models\AuthMap::getAuthAlls(['is_del'=>0,'is_forbid'=>0],['id','title','parent_id']);
                
            }else{
                // //分校  Auth
                // $schoolData = \App\Models\Roleauth::getRoleOne(['school_id'=>$adminUserSchoolId,'is_del'=>1,'is_super'=>1],['id','role_name','auth_desc','auth_id']);
              
                // if( $schoolData['code'] != 200){    
                //      return response()->json(['code' => 403 , 'msg' => '请联系总校超级管理员' ]);
                // }
                // $auth_id_arr = explode(',',$schoolData['data']['auth_id']);
      
                // if(!$auth_id_arr){
                //      $auth_id_arr = [$auth_id];
                // }
                $mapAuthIds = \App\Models\Roleauth::where(['school_id'=>$adminUserSchoolId,'is_super'=>1])->select('map_auth_id')->first();
                $mapAuthId=explode(',',$mapAuthIds['map_auth_id']);
                $roleAuthArr = \App\Models\AuthMap::whereIn('id',$mapAuthId)->get()->toArray();

            }
          
            // $roleAuthData = \App\Models\Roleauth::getRoleAuthAlls(['school_id'=>$adminUserSchoolId,'is_del'=>1],['id','role_name','auth_desc','auth_id']);
            $roleAuthArr  = getAuthArr($roleAuthArr);
            $arr = [
                // 'role_auth'=>$roleAuthData,
                'auth'=>$roleAuthArr,
                'school_id'=>$adminUserSchoolId,
                'admin_id' =>$adminId,
            ];
            return response()->json(['code' => 200 , 'msg' => '获取信息成功' , 'data' => $arr]);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }   
    }
    
    /*
     * @param  description   OSS公共参数配置接口
     * @param author    dzj
     * @param ctime     2020-06-05
     * return string
     */
    public function getImageOssConfig(){
        //oss图片公共参数配置部分
        $image_config = [
            'accessKeyId'     =>   env('OSS_IMAGE_ACCESSKEYID') ,
            'accessKeySecret' =>   env('OSS_IMAGE_ACCESSKEYSECRET') ,
            'bucket'          =>   env('OSS_IMAGE_BUCKET') ,
            'oss_url'         =>   env('OSS_IMAGE_URL')
        ];
        
        //返回json部分
        return response()->json(['code' => 200 , 'msg' => '获取图片配置参数成功' , 'data' => $image_config]);
    }
    
    
    /*
     * @param  description   上传图片方法
     * @param author    dzj
     * @param ctime     2020-06-05
     * return string
     */
    public function doUploadImage(){
        //获取提交的参数
        try{
            //获取上传文件
            $file = isset($_FILES['file']) && !empty($_FILES['file']) ? $_FILES['file'] : '';

            //判断是否有文件上传
            if(!isset($_FILES['file']) || empty($_FILES['file']['tmp_name'])){
                return response()->json(['code' => 201 , 'msg' => '请上传图片文件']);
            }
            
            //获取上传文件的文件后缀
            $is_correct_ext = \App\Http\Controllers\Controller::detectUploadFileMIME($file);
            $image_extension= substr($_FILES['file']['name'], strrpos($_FILES['file']['name'], '.')+1);   //获取图片后缀名
            if($is_correct_ext <= 0 || !in_array($image_extension , ['jpg' , 'jpeg' , 'gif' , 'png'])){
                return response()->json(['code' => 202 , 'msg' => '上传图片格式非法']);
            }
            
            //判断图片上传大小是否大于3M
            $image_size = filesize($_FILES['file']['tmp_name']);
            if($image_size > 3145728){
                return response()->json(['code' => 202 , 'msg' => '上传图片不能大于3M']);
            }

            //存放文件路径
            $file_path= app()->basePath() . "/public/upload/editor/" . date('Y-m-d') . '/';
            //判断上传的文件夹是否建立
            if(!file_exists($file_path)){
                mkdir($file_path , 0777 , true);
            }

            //重置文件名
            $filename = time() . rand(1,10000) . uniqid() . substr($file['name'], stripos($file['name'], '.'));
            $path     = $file_path.$filename;
            
            //判断文件是否是通过 HTTP POST 上传的
            if(is_uploaded_file($_FILES['file']['tmp_name'])){
                //上传文件方法
                $rs =  move_uploaded_file($_FILES['file']['tmp_name'], $path);
                if($rs && !empty($rs)){
                    return response()->json(['code' => 200 , 'msg' => '上传图片成功' , 'data' => 'http://'.$_SERVER["SERVER_NAME"]."/upload/editor/" . date('Y-m-d') . '/'.$filename]);
                } else {
                    return response()->json(['code' => 203 , 'msg' => '上传图片失败']);
                }
            } else {
                return response()->json(['code' => 202 , 'msg' => '上传方式非法']);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    
    
    /*
     * @param  description   上传图片到OSS阿里云方法
     * @param author    dzj
     * @param ctime     2020-06-05
     * return string
     */
    public function doUploadOssImage() {
        //获取提交的参数
        try{
            //获取上传文件
            $file = isset($_FILES['file']) && !empty($_FILES['file']) ? $_FILES['file'] : '';

            //判断是否有文件上传
            if(!isset($_FILES['file']) || empty($_FILES['file']['tmp_name'])){
                return response()->json(['code' => 201 , 'msg' => '请上传图片文件']);
            }
            
            //获取上传文件的文件后缀
            $is_correct_ext = \App\Http\Controllers\Controller::detectUploadFileMIME($file);
            $image_extension= substr($_FILES['file']['name'], strrpos($_FILES['file']['name'], '.')+1);   //获取图片后缀名
            if($is_correct_ext <= 0 || !in_array($image_extension , ['jpg' , 'jpeg' , 'gif' , 'png'])){
                return response()->json(['code' => 202 , 'msg' => '上传图片格式非法']);
            }
            
            //判断图片上传大小是否大于3M
            $image_size = filesize($_FILES['file']['tmp_name']);
            if($image_size > 3145728){
                return response()->json(['code' => 202 , 'msg' => '上传图片不能大于3M']);
            }
            
            //重置文件名
            $filename = time() . rand(1,10000) . uniqid() . substr($file['name'], stripos($file['name'], '.'));
            $path     = "upload/" . date('Y-m-d') . '/'.$filename;
            
            //oss图片公共参数配置部分
            $image_config = [
                'accessKeyId'     =>   env('OSS_IMAGE_ACCESSKEYID') ,
                'accessKeySecret' =>   env('OSS_IMAGE_ACCESSKEYSECRET') ,
                'bucket'          =>   env('OSS_IMAGE_BUCKET') ,
                'oss_url'         =>   env('OSS_IMAGE_URL')
            ];

            //上传图片到阿里云OSS服务器上面
            $ossClient = new \OSS\OssClient($image_config['accessKeyId'] , $image_config['accessKeySecret'] , $image_config['oss_url']);
            
            //上传图片到OSS
            $getOssInfo = $ossClient->uploadFile($image_config['bucket'] , $path , $_FILES['file']['tmp_name'] , [OssClient::OSS_CONTENT_TYPE => 'image/jpg']);
            if($getOssInfo && !empty($getOssInfo)){
                return response()->json(['code' => 200 , 'msg' => '上传成功' , 'data' => $getOssInfo['info']['url']]);
            } else {
                return response()->json(['code' => 203 , 'msg' => '上传失败']);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
     /*
     * @param  description   上传文件到OSS阿里云方法
     * @param author    lys
     * @param ctime     2020-06-18
     * return string
     */
    public function doUploadOssFile() {
      
        //获取提交的参数
        try{
            //获取上传文件
            $file = isset($_FILES['file']) && !empty($_FILES['file']) ? $_FILES['file'] : '';
         
            //判断是否有文件上传
            if(!isset($_FILES['file']) || empty($_FILES['file']['tmp_name'])){
                return response()->json(['code' => 201 , 'msg' => '请上传文件']);
            }
            
            // //获取上传文件的文件后缀
            // $is_correct_ext = \App\Http\Controllers\Controller::detectUploadFileMIME($file);
            // $image_extension= substr($_FILES['file']['name'], strrpos($_FILES['file']['name'], '.')+1);   //获取图片后缀名
            // if($is_correct_ext <= 0 || !in_array($image_extension , ['jpg' , 'jpeg' , 'gif' , 'png'])){
            //     return response()->json(['code' => 202 , 'msg' => '上传图片格式非法']);
            // }
            //判断图片上传大小是否大于200M
            $image_size = filesize($_FILES['file']['tmp_name']);
            if($image_size > 209715200){
                             
                return response()->json(['code' => 202 , 'msg' => '上传文件不能大于200M']);
            }
              
            //重置文件名
            $filename = time() . rand(1,10000) . uniqid() . substr($file['name'], stripos($file['name'], '.'));
            $path     = "upload/" . date('Y-m-d') . '/'.$filename;
            
            //oss图片公共参数配置部分
            $image_config = [
                'accessKeyId'     =>   env('OSS_IMAGE_ACCESSKEYID') ,
                'accessKeySecret' =>   env('OSS_IMAGE_ACCESSKEYSECRET') ,
                'bucket'          =>   env('OSS_IMAGE_BUCKET') ,
                'oss_url'         =>   env('OSS_IMAGE_URL')
            ];
          
            //上传图片到阿里云OSS服务器上面
            $ossClient = new \OSS\OssClient($image_config['accessKeyId'] , $image_config['accessKeySecret'] , $image_config['oss_url']);
            //上传图片到OSS
            $getOssInfo = $ossClient->uploadFile($image_config['bucket'] , $path , $_FILES['file']['tmp_name']);
            if($getOssInfo && !empty($getOssInfo)){
                return response()->json(['code' => 200 , 'msg' => '上传成功' , 'data' =>['name'=>$file['name'],'url'=>$getOssInfo['info']['url'],'size'=>$image_size]]);
            } else {
                return response()->json(['code' => 203 , 'msg' => '上传失败']);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    
    /*
     * @param  description   删除OSS上面的图片方法
     * @param $filename      文件的信息(例如:upload/2020-06-08/159158885017335eddb7f232739.jpg)
     * @param author    dzj
     * @param ctime     2020-06-08
     * return string
     */
    public function doDeleteOssImage($filename = ''){
        try {
            //oss图片公共参数配置部分
            $image_config = [
                'accessKeyId'     =>   env('OSS_IMAGE_ACCESSKEYID') ,
                'accessKeySecret' =>   env('OSS_IMAGE_ACCESSKEYSECRET') ,
                'bucket'          =>   env('OSS_IMAGE_BUCKET') ,
                'oss_url'         =>   env('OSS_IMAGE_URL')
            ];
            
            //上传图片到阿里云OSS服务器上面
            $ossClient = new \OSS\OssClient($image_config['accessKeyId'] , $image_config['accessKeySecret'] , $image_config['oss_url']);
            
            //先判断图片是否存在于OSS服务器上面
            $exists_image = $ossClient->doesObjectExist($image_config['bucket'] , $filename);
            if($exists_image && !empty($exists_image)) {
                //执行删除OSS图片文件操作
                $rs = $ossClient->deleteObject($image_config['bucket'] , $filename);
                if($rs && !empty($rs)){
                    return response()->json(['code' => 200 , 'msg' => '删除成功' , 'data' => $rs]);
                } else {
                    return response()->json(['code' => 203 , 'msg' => '删除失败']);
                }
            } else {
                return response()->json(['code' => 203 , 'msg' => '此图片不存在']);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    
    /*
     * @param  description   OA项目公共参数接口
     * @param author    dzj
     * @param ctime     2020-09-10
     * return string
     */
    public function getCommonList(){
        //支付方式
        $payMethod = [
            [
                'label'   =>  '支付宝扫码' ,
                'value'   =>  1
            ] ,
            [
                'label'   =>  '微信扫码' ,
                'value'   =>  2
            ] ,
            [
                'label'   =>  '银联快捷支付' ,
                'value'   =>  3
            ] ,
            [
                'label'   =>  '微信小程序' ,
                'value'   =>  4
            ] ,
            [
                'label'   =>  '线下录入' ,
                'value'   =>  5
            ]
        ];
        
        //支付状态
        $payStatus = [
            [
                'label'   =>  '未支付' ,
                'value'   =>  0
            ] ,
            [
                'label'   =>  '已支付' ,
                'value'   =>  1
            ] ,
            [
                'label'   =>  '支付失败' ,
                'value'   =>  2
            ] ,
            [
                'label'   =>  '已退款' ,
                'value'   =>  3
            ] 
        ];
        
        //订单类型
        $orderType = [
            [
                'label'   =>  '课程订单' ,
                'value'   =>  1
            ] ,
            [
                'label'   =>  '报名订单' ,
                'value'   =>  2
            ] ,
            [
                'label'   =>  '课程+报名订单' ,
                'value'   =>  3
            ] 
        ];
        
        //订单状态
        $orderStatus = [
            [
                'label'   =>  '未确认' ,
                'value'   =>  0
            ] ,
            [
                'label'   =>  '确认' ,
                'value'   =>  1
            ] ,
            [
                'label'   =>  '驳回' ,
                'value'   =>  2
            ] 
        ];
        
        //是否回访
        $returnVisit = [
            [
                'label'   =>  '未回访' ,
                'value'   =>  0
            ] ,
            [
                'label'   =>  '已回访' ,
                'value'   =>  1
            ]
        ];
        
        //是否开课
        $classOpen = [
            [
                'label'   =>  '不开课' ,
                'value'   =>  0
            ] ,
            [
                'label'   =>  '开课' ,
                'value'   =>  1
            ]
        ];
        
        //物料类型
        $materialTypeList = [
            [
                'label'   =>  '合同' ,
                'value'   =>  1
            ] ,
            [
                'label'   =>  '发票' ,
                'value'   =>  2
            ] ,
            [
                'label'   =>  '收据' ,
                'value'   =>  3
            ]
        ];
        
        //确认状态
        $confirmStatus = [
            [
                'label'   =>  '未确认' ,
                'value'   =>  0
            ] ,
            [
                'label'   =>  '已确认' ,
                'value'   =>  1
            ]
        ];
        
        //收集状态
        $consigneeStatusList = [
            [
                'label'   =>  '待收集' ,
                'value'   =>  0
            ] ,
            [
                'label'   =>  '收集中' ,
                'value'   =>  1
            ] ,
            [
                'label'   =>  '已收集' ,
                'value'   =>  2
            ] ,
            [
                'label'   =>  '重新收集' ,
                'value'   =>  3
            ]
        ];
        
        //封装成数组
        $arr = [
            'payMethod'   =>   $payMethod ,
            'payStatus'   =>   $payStatus ,
            'orderType'   =>   $orderType ,
            'orderStatus' =>   $orderStatus ,
            'returnVisit' =>   $returnVisit ,
            'classOpen'   =>   $classOpen ,
            'materialTypeList' => $materialTypeList ,
            'confirmStatus'    => $confirmStatus ,
            'consigneeStatusList' => $consigneeStatusList
        ];
                
        return ['code'=>200,'msg'=>'获取列表成功','data'=>$arr];
    }
    /*
     * @param  description   上传汇付支付本地证书
     * @param author    dzj
     * @param ctime     2020-06-05
     * return string
     */
    public function doUploadHfile(){
        $school_id = !isset(CurrentAdmin::user()['school_id'])?0:CurrentAdmin::user()['school_id'];
        if($school_id <=0){
            return response()->json(['code' => 201 , 'msg' => '请选择分校']);
        }
        //获取提交的参数
        try{
            //获取上传文件
            $file = isset($_FILES['file']) && !empty($_FILES['file']) ? $_FILES['file'] : '';
    
            //判断是否有文件上传
            if(!isset($_FILES['file']) || empty($_FILES['file']['tmp_name'])){
                return response()->json(['code' => 201 , 'msg' => '请上传图片文件']);
            }
            
            //获取上传文件的文件后缀
            // $is_correct_ext = \App\Http\Controllers\Controller::detectUploadFileMIME($file);
            $image_extension= substr($_FILES['file']['name'], strrpos($_FILES['file']['name'], '.')+1);   //获取图片后缀名
           
            if(!in_array($image_extension , ['cer' , 'pfx' ])){
                return response()->json(['code' => 202 , 'msg' => '上传格式非法']);
            }
            
            // //判断图片上传大小是否大于3M
            // $image_size = filesize($_FILES['file']['tmp_name']);
            // if($image_size > 3145728){
            //     return response()->json(['code' => 202 , 'msg' => '上传图片不能大于3M']);
            // }
    
            //存放文件路径
            $file_path= app()->basePath() . "/public/hfCer/" . $school_id. '/';
            //判断上传的文件夹是否建立
            if(!file_exists($file_path)){
                mkdir($file_path , 0777 , true);
            }
    
            //重置文件名
            $filename = time() . rand(1,10000) . uniqid() . substr($file['name'], stripos($file['name'], '.'));
            $path     = $file_path.$filename;
            
            //判断文件是否是通过 HTTP POST 上传的
            if(is_uploaded_file($_FILES['file']['tmp_name'])){
                //上传文件方法
                $rs =  move_uploaded_file($_FILES['file']['tmp_name'], $path);
                if($rs && !empty($rs)){
                    return response()->json(['code' => 200 , 'msg' => '上传文件成功' , 'data' => "/hfCer/" . $school_id. '/'.$filename]);
                } else {
                    return response()->json(['code' => 203 , 'msg' => '上传文件失败']);
                }
            } else {
                return response()->json(['code' => 202 , 'msg' => '上传方式非法']);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
   


}
