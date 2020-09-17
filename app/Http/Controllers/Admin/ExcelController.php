<?php
namespace App\Http\Controllers\Admin;

use App\Http\Middleware\JWTRoleAuth;
use App\Models\AdminLog;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ExcelController extends Controller {
	public function doExcelDatum(){
        $data = self::$accept_data;
        if(!empty($data['token'])){
            $jwt = new JWTRoleAuth();
            $user =  $jwt->handle($data['token'],'/admin/doExcelDatum');
            if(empty($user)){
                return ['code' => 202 , 'msg' => 'token....'];
            }
        }else{
            return ['code' => 202 , 'msg' => 'token....'];
        }
//		$data['schoolids'] = $this->underlingLook(isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0);
        return Excel::download(new \App\Exports\StudentDatumExport($data,$user), '学员报名资料.xlsx');
	}
}
