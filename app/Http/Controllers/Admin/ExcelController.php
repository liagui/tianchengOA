<?php
namespace App\Http\Controllers\Admin;

use App\Http\Middleware\JWTRoleAuth;
use App\Models\AdminLog;
use http\Header;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class ExcelController extends Controller{
	public function doExcelDatum(){
	    include_once("./app/Http/Middleware/JWTRoleAuth.php");
	    $jwt = new JWTRoleAuth();
	    $data = self::$accept_data;
	    $token = $jwt->handle($data['token'],'/admin/doExcelDatum');
        print_r($token);die;
//		$data['schoolids'] = $this->underlingLook(isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0);
//        return Excel::download(new \App\Exports\StudentDatumExport($data,$user), '学员报名资料.xlsx');
	}
}
