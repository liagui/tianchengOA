<?php
namespace App\Http\Controllers\Admin;

use App\Http\Middleware\JWTRoleAuth;
use App\Models\AdminLog;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Admin;

class OrderExcelController extends Controller {
    /*
     * @param  description   导出分校收入详情
     * @param  参数说明       body包含以下参数[
     *      open_id        开课得管理id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-08
     * return string
     */
    public function doExportBranchSchoolExcel() {
        $body = self::$accept_data;
        return Excel::download(new \App\Exports\BranchSchoolExport($body), '分校收入详情.xlsx');
    }

    /*
     * @param  description   导出分校已确认订单
     * @param  参数说明       body包含以下参数[
     *      open_id        开课得管理id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-08
     * return string
     */
    public function doExportBranchSchoolConfirmOrderExcel() {
        $body = self::$accept_data;
        return Excel::download(new \App\Exports\BranchSchoolConfirmOrderExport($body), '分校收入详情-已确认订单.xlsx');
    }
    
    /*
     * @param  description   导出分校已退费订单
     * @param  参数说明       body包含以下参数[
     *      open_id        开课得管理id
     * ]
     * @param author    dzj
     * @param ctime     2020-09-08
     * return string
     */
    public function doExportBranchSchoolRefundOrderExcel() {
        $body = self::$accept_data;
        return Excel::download(new \App\Exports\BranchSchoolRefundOrderExport($body), '分校收入详情-已退费订单.xlsx');
    }
}
