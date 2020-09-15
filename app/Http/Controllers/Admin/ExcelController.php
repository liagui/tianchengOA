<?php
namespace App\Http\Controllers\Admin;

use App\Models\AdminLog;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ExcelController extends Controller {
	public function doExcelDatum(){
		$data = self::$accept_data;
		$data['schoolids'] = $this->underlingLook(isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0);
        return Excel::download(new \App\Exports\StudentDatumExport($data), '学员报名资料.xlsx');
	}
}		