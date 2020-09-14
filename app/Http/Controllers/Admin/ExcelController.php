<?php
namespace App\Http\Controllers\Admin;


use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ExcelController extends Controller {
	public function doExcelDatum(){
        return Excel::download(new \App\Exports\StudentDatumExport(self::$accept_data), '财务报表.xlsx');

	}
}		