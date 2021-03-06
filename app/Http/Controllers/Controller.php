<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use http\Client\Response;
use Laravel\Lumen\Routing\Controller as BaseController;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class Controller extends BaseController {
    //接受数据参数
    public static $accept_data;
    /*
     * @param  description   基础底层数据加密部分
     * @param  $request      数据接收参数
     * @param  author        duzhijian
     * @param  ctime         2020-04-16
     * return  string
     */
    public function __construct() {
        //self::$accept_data = app('rsa')->servicersadecrypt($request);
        //app('rsa')->Test();
        self::$accept_data = $_REQUEST;
    }



     /*返回json串
     * addtime 2020.4.28
     * auther 孙晓丽
     * $code  int   状态码
     * $data  array  数据数组
     * return  string
     * */
    protected function response($data, $statusCode = 200)
    {
        if ($statusCode == 200 && is_string($data)) {
            return response()->json(['code' => $statusCode, 'msg' => 'success', 'data' => $data]);
        } elseif (is_string($data)) {
            return response()->json(['code' => $statusCode, 'msg' => $data]);
        } else {
            return response()->json(['code' => $statusCode, 'msg' => 'success', 'data' => $data]);
        }
        return response()->json($data);
    }

    /*
     * @param  description   导入功能方法
     * @param  参数说明[
     *     $imxport      导入文件名称
     *     $excelpath    excel文件路径
     *     $is_limit     是否限制最大表格数量(1代表限制,0代表不限制)
     *     $limit        限制数量
     * ]
     * @param  author        dzj
     * @param  ctime         2020-04-30
    */
    public static function doImportExcel($imxport , $excelpath , $is_limit = 0 , $limit = 0){
        //获取提交的参数
        try{
            //导入数据方法
            $exam_array = Excel::toArray($imxport , $excelpath);

            //判断导入的excel文件中是否有信息
            if(!$exam_array || empty($exam_array)){
                return ['code' => 204 , 'msg' => '暂无信息导入'];
            } else {
                $array = [];
                //循环excel表中数据信息
                foreach($exam_array as $v){
                    //去掉header头字段信息(不加入表中)【备注:去掉二维数组中第一个数组】
                    unset($v[0]);
                    foreach($v as $k1 => $v1){
                        //去掉二维数组中最后一个空元素
                        unset($v1[count($v1)-1]);
                        for($i=0;$i<count($v1);$i++){
                            if($v1[$i] && !empty($v1[$i])){
                                $array[$k1] = $v1;
                            }
                        }
                    }
                }
            }
            //判断excel表格中总数量是否超过最大限制
            $max_count = count($array);
            if($is_limit > 0 && $max_count > $limit){
                return ['code' => 202 , 'msg' => '超过最大导入数量'];
            }
            return ['code' => 200 , 'msg' => '获取数据成功' , 'data' => $array];
        } catch (Exception $ex) {
            return ['code' => 500 , 'msg' => $ex->getMessage()];
        }
    }

    /*
     * @param  description   导入功能方法
     * @param  参数说明[
     *     $imxport      导入文件名称
     *     $excelpath    excel文件路径
     *     $is_limit     是否限制最大表格数量(1代表限制,0代表不限制)
     *     $limit        限制数量
     * ]
     * @param  author        dzj
     * @param  ctime         2020-04-30
    */
    public static function doImportExcel2($imxport , $excelpath , $is_limit = 0 , $limit = 0){
        //获取提交的参数
        try{
            //导入数据方法
            $exam_array = Excel::toArray($imxport , $excelpath);

            //判断导入的excel文件中是否有信息
            if(!$exam_array || empty($exam_array)){
                return ['code' => 204 , 'msg' => '暂无信息导入'];
            } else {
                $array = [];
                //循环excel表中数据信息
                foreach($exam_array as $v){
                    //去掉header头字段信息(不加入表中)【备注:去掉二维数组中第一个数组】
                    unset($v[0]);
                    foreach($v as $k1 => $v1){
                        //去掉二维数组中最后一个空元素
                        //unset($v1[count($v1)-1]);
                        for($i=0;$i<count($v1);$i++){
                            if($v1[$i] && !empty($v1[$i])){
                                $array[$k1] = $v1;
                            }
                        }
                    }
                }
            }
            //判断excel表格中总数量是否超过最大限制
            $max_count = count($array);
            if($is_limit > 0 && $max_count > $limit){
                return ['code' => 202 , 'msg' => '超过最大导入数量'];
            }
            return ['code' => 200 , 'msg' => '获取数据成功' , 'data' => $array];
        } catch (Exception $ex) {
            return ['code' => 500 , 'msg' => $ex->getMessage()];
        }
    }

    /*
     * @param  description   检测真实文件后缀格式的方法
     * @param  参数说明[
     *     $file             文件数组
     *     $file["name"]     获取原文件名称
     *     $file["tmp_name"] 临时文件名称
     * ]
     * @param  author        dzj
     * @param  ctime         2020-05-14
     * return  boolean($flag 1表示真实excel , 0表示伪造或者不是excel)
    */
    public static function detectUploadFileMIME($file){
        $flag = 0;
        $file_array = explode (".", $file["name"]);
        $file_extension = strtolower (array_pop($file_array));
        switch ($file_extension) {
            case "xls" :
                // 2003 excel
                $fh  = fopen($file["tmp_name"], "rb");
                $bin = fread($fh, 8);
                fclose($fh);
                $strinfo  = @unpack("C8chars", $bin);
                $typecode = "";
                foreach ($strinfo as $num) {
                    $typecode .= dechex ($num);
                }
                if ($typecode == "d0cf11e0a1b11ae1") {
                    $flag = 1;
                }
                break;
            case "xlsx" :
                // 2007 excel
                $fh  = fopen($file["tmp_name"], "rb");
                $bin = fread($fh, 4);
                fclose($fh);
                $strinfo = @unpack("C4chars", $bin);
                $typecode = "";
                foreach ($strinfo as $num) {
                    $typecode .= dechex ($num);
                }
                if ($typecode == "504b34") {
                    $flag = 1;
                }
                break;
            case "jpg" :
                $fh  = fopen($file["tmp_name"], "rb");
                $bin = fread($fh, 2);
                fclose($fh);
                $strinfo = @unpack("C2chars", $bin);
                $typecode = "";
                if($strinfo && !empty($strinfo)){
                    foreach ($strinfo as $num) {
                        $typecode .= dechex ($num);
                    }
                    if ($typecode == "ffd8") {
                        $flag = 1;
                    }
                }
                break;
            case "gif" :
                $fh  = fopen($file["tmp_name"], "rb");
                $bin = fread($fh, 2);
                fclose($fh);
                $strinfo = @unpack("C2chars", $bin);
                $typecode = "";
                if($strinfo && !empty($strinfo)){
                    foreach ($strinfo as $num) {
                        $typecode .= dechex ($num);
                    }
                    if ($typecode == "4749") {
                        $flag = 1;
                    }
                }
                break;
            case "png" :
                $fh  = fopen($file["tmp_name"], "rb");
                $bin = fread($fh, 2);
                fclose($fh);
                $strinfo = @unpack("C2chars", $bin);
                $typecode = "";
                if($strinfo && !empty($strinfo)){
                    foreach ($strinfo as $num) {
                        $typecode .= dechex ($num);
                    }
                    if ($typecode == "8950") {
                        $flag = 1;
                    }
                }
                break;
        }
        return $flag;
    }

   /** delDir()删除文件夹及文件夹内文件函数
    * @param string $path   文件夹路径
    * @param string $delDir 是否删除改
    * @return boolean
    */
    public static function delDir($path, $del = false){
        $handle = opendir($path);
        if ($handle) {
            while (false !== ($item = readdir($handle))) {
                if (($item != ".") && ($item != "..")) {
                    is_dir("$path/$item") ? self::delDir("$path/$item", $del) : unlink("$path/$item");
                }
            }
            closedir($handle);
            if ($del) {
                return rmdir($path);
            }
        }elseif(file_exists($path)) {
            return unlink($path);
        }else {
            return false;
        }
    }

   /* @param  decerption     省市县三级联动
    * @param  $body[
    *     region_id   地区id(默认为0)
    * ]
    * @return array
    */
    public static function getRegionDataList($body){
        //如果传递的地区id大于0则走下面的判断逻辑
        if(!empty($body) && isset($body['region_id']) && $body['region_id'] > 0){
            //根据地区id判断所属的地区在表中是否存在
            $region_count = \App\Models\Region::where("parent_id" , $body['region_id'])->count();
            if($region_count <= 0){
                return ['code' => 204 , 'msg' => '该地区不存在'];
            }

            //获取地区列表数据
            $region_list  = \App\Models\Region::where("parent_id" , $body['region_id'])->get()->toArray();
        } else {
            //获取省级列表数据
            $region_list  = \App\Models\Region::where("parent_id" , 0)->get()->toArray();
        }

        //返回数据信息
        return ['code' => 200 , 'msg' => '获取地区列表数据成功' , 'data' => $region_list];
   }

    /*
     * @param  description   通过证件名称获取对应的id值
     * @param $name     证件名称
     * @param author    dzj
     * @param ctime     2020-05-22
     * return string
     */
    public static function getPapersNameByType($val){
        //证件类型数组
        $arr = [1=>'身份证' , 2=>'护照' , 3=>'港澳通行证' , 4=>'台胞证' , 5=>'军官证' , 6=>'士官证' , 7=>'其他'];
        return $arr[$val];
    }

    /*
     * @param  description   生成唯一性token得方法
     * @param $login_logo    唯一标识符
     * @param author    dzj
     * @param ctime     2020-05-29
     * return string
     */
    public static function setAppLoginToken($login_logo){
        $str   = md5(uniqid(md5(microtime(true)),true));
        return sha1($str.$login_logo);
    }

    /*
     * @param  description   是否能查看下属信息的schoolids
     * @param $login_logo    唯一标识符
     * @param author     lys
     * @param ctime     2020-9-9
     * return arr
     */
    public static function underlingLook($school_id ){
        $look_school =  $oneChildSchoolIdsArr =  $twoChildSchoolIdsArr = $thereChildSchoolIdsArr  = $oneSchoolIds = $twoSchoolIds = $look_school_all_arr= $twoChildSchoolIdsArrs= [];

        $schoolIds = explode(',',$school_id);
        if(in_array($schoolIds[0],[0]) && count($schoolIds)==1){
            $schoolData = \App\Models\School::where(['is_del'=>0])->select('id')->get()->toArray();
            $schoolData = !empty($schoolData) ? array_column($schoolData,'id'):[];
            return ['code'=>200,'msg'=>'成功','data'=>$schoolData];
        }else{
            $schoolData = \App\Models\School::whereIn('id',$schoolIds)->where(['is_del'=>0])->select('id','look_all_flag','level')->get()->toArray();
            if(empty($schoolData)){
                return ['code'=>200,'msg'=>'success','data'=>$schoolData];
            }else{

                foreach($schoolData as $k=>$v){
                     array_push($look_school_all_arr, $v['id']); 
                    if($v['look_all_flag'] == 1){ //1级看二、三级
                       //能看下属的所属分校id组
                        if($v['level'] == 1){
                            array_push($oneSchoolIds,$v['id']);
                            $childOneSchoolData  = \App\Models\School::whereIn('parent_id',$oneSchoolIds)->where(['is_del'=>0])->select('id')->get()->toArray();
                            if(!empty($childOneSchoolData)){
                                $oneChildSchoolIdsArr = array_column($childOneSchoolData,'id'); //所有二级分校的id
                                $oneChildSchoolIdsArr = array_values($oneChildSchoolIdsArr);
                                if(!empty($oneChildSchoolIdsArr)){
                                    $childTwoSchoolData  = \App\Models\School::whereIn('parent_id',$oneChildSchoolIdsArr)->where(['is_del'=>0,'look_all_flag'=>1])->select('id')->get()->toArray(); //能看下属的二级分校id
                                    if(!empty($childTwoSchoolData)){
                                        $twoChildSchoolIdsArr = array_column($childTwoSchoolData,'id'); //能看下属的三级分校的二级分校id
                                        $twoChildSchoolIdsArr = array_values($twoChildSchoolIdsArr);
                                                                               if(!empty($twoChildSchoolIdsArr)){
                                            $thereChildSchoolIdsArr  = \App\Models\School::whereIn('parent_id',$twoChildSchoolIdsArr)->where(['is_del'=>0])->select('id')->get()->toArray();
                                            if(!empty($thereChildSchoolIdsArr)){
                                                $thereChildSchoolIdsArr = array_column($thereChildSchoolIdsArr,'id'); //所有能看的三级分校id
                                                $thereChildSchoolIdsArr = array_values($thereChildSchoolIdsArr);
                                            }
                                        }
                                    }
                                }
                            }
                        }else if($v['level'] == 2){
                            //二级看三级
                            array_push($twoSchoolIds,$v['id']);
                            $childTwoSchoolDatas  = \App\Models\School::whereIn('parent_id',$twoSchoolIds)->where(['is_del'=>0])->select('id')->get()->toArray();
                            if(!empty($childTwoSchoolDatas)){
                                $twoChildSchoolIdsArrs = array_column($childTwoSchoolDatas,'id');
                            }
                        }
                    }
                }
                $look_school = array_merge($oneChildSchoolIdsArr,$twoChildSchoolIdsArr,$thereChildSchoolIdsArr,$twoChildSchoolIdsArrs);

                $look_school_all = array_intersect($oneChildSchoolIdsArr, $look_school);
                $look_school  = array_values(array_unique(array_merge($look_school,$look_school_all,$look_school_all_arr)));

                return ['code'=>200,'msg'=>'Success','data'=>$look_school];
            }
        }
    }

}
