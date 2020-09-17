<?php
namespace App\Http\Controllers\Admin;

use App\Http\Middleware\JWTRoleAuth;
use App\Http\Controllers\Controller;

class ExcelController extends Controller{
	public function doExcelDatum(){
        require_once("../../Middleware/JWTRoleAuth.php");
	    $jwt = new JWTRoleAuth();
	    $data = self::$accept_data;
	    $token = $jwt->handle($data['token'],'/admin/doExcelDatum');
        print_r($token);die;
//		$data['schoolids'] = $this->underlingLook(isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0);
//        return Excel::download(new \App\Exports\StudentDatumExport($data,$user), '学员报名资料.xlsx');
	}
}
