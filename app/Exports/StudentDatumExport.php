<?php
namespace App\Exports;
use App\Models\School;
use App\Models\AdminLog;
use App\Models\StudnetDatum;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
class StudentDatumExport implements FromCollection, WithHeadings {

    protected $where;
    public function __construct($invoices){
        $this->where = $invoices;
    }
    public function collection() {
        $data = $this->where;
        $arr =School::select('id','school_name')->get();

        return $arr;

        
    }

    public function headings(): array
    {
       
        return [
            '所属分校',
            // '报考月份',
            // '报考地区',
            // '备考地区',
            // '学员姓名',
            // '学员性别',
            // '学员身份证',
            '学员身份证照片'
        ];
    }
}
