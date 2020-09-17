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
    public static function getRegionList($body){
        $parent_id = isset($body['parent_id']) && !empty($body['parent_id']) && $body['parent_id'] >0 ?$body['parent_id'] :0;
        $data = self::select('id as value','name as label')->where('parent_id',$parent_id)->get()->toArray();
        print_r($data);die;
        return ['code'=>200,'msg'=>'success','data'=>$data];
    }


}
