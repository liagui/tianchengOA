<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model {
    //指定别的表名
    public $table      = 'ld_region';
    //时间戳设置
    public $timestamps = false;


    public static function getRegion(){
    	$data = self::select('id as value','name as label','parent_id')->get()->toArray();
    	if(!empty($data)){
    		foreach($data as $k=>$v){
	            if($v['parent_id'] == 0){
	                $arr[] = $v;    
	            }else{
	                foreach ($arr as $key => $value) {
	                    if($v['parent_id'] == $value['value']){
	                        
	                        $arr[$key]['child_array'][] = $v;
	                    }
	                }
	            }
	        }
    	}else{
    		$arr = [];
    	}
    	return ['code'=>200,'msg'=>'success','data'=>$arr];
    }



}
