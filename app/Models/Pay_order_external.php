<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pay_order_external extends Model
{
    //指定别的表名
    public $table = 'pay_order_external';
    //时间戳设置
    public $timestamps = false;
}
