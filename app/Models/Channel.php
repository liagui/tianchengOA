<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\AdminLog;
use App\Models\PapersExam;
use App\Models\Bank;
use App\Models\QuestionSubject;
use App\Models\School;
use Validator;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
class  Channel extends Model {
    //指定别的表名
    public $table = 'channel';
    //时间戳设置
    public $timestamps = false;
}