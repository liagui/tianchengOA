<?php
namespace App\Http\Controllers\Admin;

use App\Http\Middleware\JWTRoleAuth;
use App\Models\AdminLog;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class ExcelController extends BaseMiddleware{
	public function doExcelDatum(){

        $data = $_REQUEST;
            // 解析token角色
        $token_role = $this->auth->parseToken()->getClaim('role');
        print_r($token_role);die;
//		$data['schoolids'] = $this->underlingLook(isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0);
//        return Excel::download(new \App\Exports\StudentDatumExport($data,$user), '学员报名资料.xlsx');
	}
}
