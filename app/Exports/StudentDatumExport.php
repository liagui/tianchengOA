<?php
namespace App\Exports;
use App\Models\School;
use App\Models\AdminLog;
use App\Models\Admin;
use App\Models\StudentDatum;
use App\Models\Course;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;
class StudentDatumExport implements FromCollection, WithHeadings {

    protected $where;
    public function __construct($invoices){
        $this->where = $invoices;
    }
    public function collection() {
        // $body = $this->where;
        // if(isset($body['subject']) && !empty($body['subject'])){
        //     $subject = json_decode($body['subject'],1);
        //     $oneSubject = $subject[0];
        //     $twoSubject = isset($subject[1]) && $subject[1]>0 ?$subject[1]:0;
        // }
        // DB::connection()->enableQueryLog();
        $Datum = StudentDatum::leftJoin('information','information.id','=','student_information.information_id')
            ->where(function($query) use ($body){
                // if(isset($body['school_id']) && !empty($body['school_id'])){
                //     $query->where('student_information.school_id',$body['school_id']);
                // }else{
                //     $query->whereIn('student_information.school_id',$body['schoolids']['data']);
                // }
                if(isset($body['subject']) && !empty($body['subject'])){
                    $query->where('student_information.project_id',$oneSubject);
                    $query->where('student_information.subject_id',$twoSubject);
                }
            })->select('student_id')->get()->toArray();
            // })->select('student_id','school_id','project_id','subject_id','course_id','gather_id','datum_create_time','initiator_id','student_name','student_sex','student_phone','student_card','address','month','sign_region','reference_region','culture','graduated_school','professional','years','xx_account','xx_password','branch_school','photo','card_photo_front','card_photo_contrary','card_photo_scanning','diploma_photo','diploma_scanning','my_photo','audit_id')->orderByDesc('datum_create_time')->get()->toArray();
        
        // if(!empty($Datum)){
        //     $adminArr = Admin::where(['is_del'=>1,'is_forbid'=>1])->select('id','real_name')->get()->toArray();
        //     if(!empty($adminArr)){
        //         $adminArr  = array_column($adminArr,'real_name','id');
        //     }
        //     $courseArr = Course::where(['is_del'=>1])->select('id','course_name')->get()->toArray();
        //     if(!empty($courseArr)){
        //         $courseArr  = array_column($courseArr,'course_name','id');
        //     }
        //     $schoolArr = School::where(['is_del'=>0,'is_open'=>0])->select('id','school_name')->get()->toArray();
        //     if(!empty($schoolArr)){
        //         $schoolArr  = array_column($schoolArr,'school_name','id');
        //     }
        //     foreach($Datum as $key=>&$v){
        //         $v['school_name'] = isset($schoolArr[$v['school_id']]) ? $schoolArr[$v['school_id']] :'';
        //         $v['project_name'] = isset($courseArr[$v['project_id']]) ? $courseArr[$v['project_id']] :'';
        //         $v['subject_name'] = isset($courseArr[$v['subject_id']]) ? $courseArr[$v['subject_id']] :'';
        //         $v['audit_name'] = isset($adminArr[$v['audit_id']]) ? $adminArr[$v['audit_id']] :'';
        //         $v['gather_name'] = isset($adminArr[$v['gather_id']]) ? $adminArr[$v['gather_id']] :'';
        //         $v['initiator_name'] = isset($adminArr[$v['initiator_id']]) ? $adminArr[$v['initiator_id']] :'';
        //         $v['student_sex'] = $v['student_sex'] == 1?'女':'男';
        //     }
        // }

        return $Datum; 
    }

    public function headings(): array
    {
        return ['所属分校'];
        // return [
        //     '所属分校','学员姓名','报考地区','备考地区','学员姓名','学员性别','学员手机号','学员身份证号','户籍地址','报考月份','报考地区','备考地区','文化程度','毕业学院','毕业专业','毕业年月','学信网账号','学信网密码','2寸白底照片','身份证正面照片','身份证背面照片','身份证正反面扫描','毕业证照片','毕业证扫描件','本人手持身份证照片',
        // ];
    }
}
