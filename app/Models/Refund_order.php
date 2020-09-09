<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refund_order extends Model
{
    //指定别的表名
    public $table = 'refund_order';
    //时间戳设置
    public $timestamps = false;

    /*
         * @param  退费列表
         * @param  $user_id     参数
         * @param  author  苏振文
         * @param  ctime   2020/9/9 10:45
         * return  array
         */
}
