<?php
namespace App\Http\Controllers\Admin;

use App\Http\Middleware\JWTRoleAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Admin;

class ExcelController extends Controller{
	public function doExcelDatum(){
        $data = self::$accept_data;
        if(!isset($data['id']) || $data['id']<=0){
            return ['code' => 202 , 'msg' =>'id 不合法'];
        }
        $adminArr = Admin::where(['is_del'=>1,'is_forbid'=>1])->select('school_id')->first();
        // if(!empty($data['token'])){
        //     $jwt = new JWTRoleAuth();
        //     $user =  $jwt->handle($data['token'],'/admin/doExcelDatum');
        //     if(empty($user)){
        //         return ['code' => 202 , 'msg' => 'token....'];
        //     }
        // }else{
        //     return ['code' => 202 , 'msg' => 'token....'];
        // }
		$data['schoolids'] = $this->underlingLook(isset($adminArr['school_id']) && !empty($adminArr['school_id']) ? $adminArr['school_id'] : 0);
        return Excel::download(new \App\Exports\StudentDatumExport($data,$user), '学员报名资料.xlsx');
	}
}
