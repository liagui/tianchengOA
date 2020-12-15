<?php
namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Tools\CurrentAdmin;
use App\Models\Roleauth;

class Admin extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable;

    public $table = 'admin';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username', 'password', 'email', 'mobile', 'realname', 'sex', 'admin_id','teacher_id','school_status','school_id','is_forbid','is_del','role_id'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'created_at',
        'updated_at'
    ];


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return ['role' => 'admin'];
    }


    public static function message()
    {
        return [
            'id.required'  => json_encode(['code'=>'201','msg'=>'账号id不能为空']),
            'id.integer'   => json_encode(['code'=>'202','msg'=>'账号id不合法']),
            'school_id.required'  =>  json_encode(['code'=>'201','msg'=>'学校id不能为空']),
            'school_id.integer'   => json_encode(['code'=>'202','msg'=>'学校id类型不合法']),
            'username.required' => json_encode(['code'=>'201','msg'=>'账号不能为空']),
            'username.unique'  => json_encode(['code'=>'205','msg'=>'账号已存在']),
            'realname.required' => json_encode(['code'=>'201','msg'=>'真实姓名不能为空']),
            'mobile.required'  =>  json_encode(['code'=>'201','msg'=>'手机号不能为空']),
            'mobile.regex' => json_encode(['code'=>'202','msg'=>'手机号不合法']) ,
            'sex.integer'  => json_encode(['code'=>'202','msg'=>'性别标识不合法']),
            'sex.required' => json_encode(['code'=>'201','msg'=>'性别标识不能为空']),
            'password.required'  => json_encode(['code'=>'201','msg'=>'密码不能为空']),
            'pwd.required' => json_encode(['code'=>'201','msg'=>'确认密码不能为空']),
            'role_id.required' => json_encode(['code'=>'201','msg'=>'角色id不能为空']),
            'role_id.integer' => json_encode(['code'=>'202','msg'=>'角色id不合法']),
        ];

    }

    /*
         * @param  descriptsion 后台账号信息
         * @param  $user_id     用户id
         * @param  author  苏振文
         * @param  ctime   2020/4/25 15:44
         * return  array
         */
    // public static function GetUserOne($id,$field = ['*']){
    //     $return = self::where(['id'=>$id])->select($field)->first();
    //     return $return;
    // }


    /*
         * @param  descriptsion 后台账号信息
         * @param  $where[
         *    id   =>       用户id
         *    ....
         * ]
         * @param  author  苏振文
         * @param  ctime   2020/4/25 15:44
         * return  array
         */
    public static function getUserOne($where,$field = ['*']){

        $userInfo = self::where($where)->select($field)->first();
        if($userInfo){
            return ['code'=>200,'msg'=>'获取后台用户信息成功','data'=>$userInfo];
        }else{
            return ['code'=>204,'msg'=>'后台用户信息不存在'];
        }
    }
    /*
     * @param  descriptsion 获取后台用户列表
     * @param  $where  array     查询条件
     * @param  $title  string   查询条件(用于用户列表查询)
     * @param  $page   int     当前页
     * @param  $limit  int     每页显示
     * @param  author   lys
     * @param  ctime   2020/4/28 13:25
     * return  array
     */
    public static  function getUserAll($where=[],$title='',$page = 1,$limit= 10){

        $data = self::leftjoin('ld_role_auth','ld_role_auth.id', '=', 'ld_admin_user.role_id')
            ->where($where)
            ->where(function($query) use ($title){
                if($title != ''){
                    $query->where('ld_admin_user.real_name','like','%'.$title.'%')
                    ->orWhere('ld_admin_user.account','like','%'.$title.'%')
                    ->orWhere('ld_admin_user.phone','like','%'.$title.'%');
                }
            })
            ->get()->forPage($page,$limit)->toArray();
        return $data;
    }
    /*
     * @param  descriptsion 更新状态方法
     * @param  $where[
     *    id   =>       用户id
     *    ....
     * ]
     * @param  $update[
     *    is_del   =>      删除状态码
     *    is_forbid =>     启禁状态码
     * ]
     * @param  author  lys
     * @param  ctime   2020-04-13
     * return  int
     */
    public static function upUserStatus($where,$update){

        $result = self::where($where)->update($update);
        return $result;
    }
    /*
     * @param  descriptsion 添加用户方法
     * @param  $insertArr[
     *    phone   =>     手机号
     *    account =>     登录账号
     *    ....
     * ]
     * @param  author  duzhijian
     * @param  ctime   2020-04-13
     * return  int
     */
    public static function insertAdminUser($insertArr){
        return  self::insertGetId($insertArr);

    }
    /*
     * @param  description   获取用户列表
     * @param  参数说明       body包含以下参数[
     *     search       搜索条件 （非必填项）
     *     page         当前页码 （不是必填项）
     *     limit        每页显示条件 （不是必填项）
     *     school_id    学校id  （非必填项）
     * ]
     * @param author    lys
     * @param ctime     2020-04-29
     */
    public static function getAdminUserList($body=[]){

        //判断传过来的数组数据是否为空
        if(!is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }
        $adminUserInfo  = CurrentAdmin::user();  //当前登录用户所有信息
        $pagesize = isset($body['pagesize']) && $body['pagesize'] > 0 ? $body['pagesize'] : 15;
        $page     = isset($body['page']) && $body['page'] > 0 ? $body['page'] : 1;
        $offset   = ($page - 1) * $pagesize;
        $admin_count = self::where(function($query) use ($body){
                            if(isset($body['search']) && !empty($body['search'])){
                                $query->where('username','like','%'.$body['search'].'%')
                                      ->orWhere('mobile','like','%'.$body['search'].'%');
                            }
                            if(isset($body['use']) &&  strlen($body['use'])>0){
                                $query->where('is_use',$body['use']);
                            }
                            if(isset($body['forbid']) && strlen($body['forbid'])>0){
                                $query->where('is_forbid',$body['forbid']);
                            }
                            if(isset($body['school']) && !empty($body['school'])){
                                $query->whereRaw("find_in_set({$body['school']},school_id)"); //学校搜素
                            }
                            $query->where('is_del',1);
                        })->count();

        $sum_page = ceil($admin_count/$pagesize);
        $adminUserData = [];
        if($admin_count >0){
            $adminUserData =  self::where(function($query) use ($body){
                            if(isset($body['search']) && !empty($body['search'])){
                                $query->where('username','like','%'.$body['search'].'%')
                                      ->orWhere('mobile','like','%'.$body['search'].'%');
                            }
                            if(isset($body['use']) && strlen($body['use'])>0){
                                $query->where('is_use',$body['use']);
                            }
                            if(isset($body['forbid']) && strlen($body['forbid'])>0){
                                $query->where('is_forbid',$body['forbid']);
                            }
                            if(isset($body['school']) && !empty($body['school'])){
                                $query->whereRaw("find_in_set({$body['school']},school_id)");
                            }
                            $query->where('is_del',1);
                })->select('username','real_name','mobile','wx','role_id','school_id','is_use','is_forbid','create_time','license','hand_card','card_front','card_side','id')->offset($offset)->limit($pagesize)->get()->toArray();
            $roleArr = Roleauth::where('is_del',0)->select('id','role_name')->get()->toArray();
            $roleArr = array_column($roleArr,'role_name','id');
            $schoolAll = School::where(['is_del'=>0,'is_open'=>0])->select('id')->get()->toArray(); //所有分校id
            $schoolAll = empty($schoolAll)?[] :array_column($schoolAll,'id');
            $schoolIdsArr = School::where(['is_open'=>0,'is_del'=>0])->pluck('id')->toArray();
            foreach($adminUserData as $key=>&$v){
                $v['role_name'] = !isset($roleArr[$v['role_id']])?'':$roleArr[$v['role_id']];
                $school =  empty($v['school_id'])&&strlen($v['school_id'])<=0?[]:explode(",",$v['school_id']);
                if(empty($school)){
                   $v['schoolname'] = '';
                }else{

                    if(in_array($school[0],[0]) && !isset($school[1])){
                        $v['schoolname']  = '全部';
                    }else if(in_array($school[0],[0]) && isset($school[1])){
                        unset($school[0]);
                        if(empty(array_diff($schoolIdsArr,$school))) {
                            $v['schoolname']  = '全部';
                        }else{
                            $schoolData = School::whereIn('id',$school)->select('school_name','id')->get()->toArray();
                            $str = '';
                            if(!empty($schoolData)){
                                foreach ($schoolData as $k => &$school) {
                                    $str .= $school['school_name'].',';
                                }
                                $v['schoolname'] =  rtrim($str,',');
                            }else{
                                $v['schoolname'] = '';
                            }
                        }
                    }else if(empty(array_diff($schoolIdsArr,$school))){
                        $v['schoolname']  = '全部';
                    }else{
                        $schoolData = School::whereIn('id',$school)->select('school_name','id')->get()->toArray();
                        $str = '';
                        if(!empty($schoolData)){
                            foreach ($schoolData as $k => &$school) {
                                $str .= $school['school_name'].',';
                            }
                            $v['schoolname'] =  rtrim($str,',');
                        }else{
                            $v['schoolname'] = '';
                        }
                    }
                }
            }
        }
        $arr['code']= 200;
        $arr['msg'] = 'Success';
        $arr['data'] = ['admin_list' => $adminUserData , 'total' => $admin_count ,'sum_page'=>$sum_page];
        return $arr;
    }


    /*
     * @param  description   获取角色列表
     * @param  参数说明       body包含以下参数[
     *     search       搜索条件 （非必填项）
     *     page         当前页码 （不是必填项）
     *     limit        每页显示条件 （不是必填项）
     *
     * ]
     * @param author    lys
     * @param ctime     2020-04-29
     */
    public static function getAuthList($body=[]){
        //判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }
         $adminUserInfo  = CurrentAdmin::user();  //当前登录用户所有信息
        //判断搜索条件是否合法
        $body['search'] = !isset($body['search']) && empty($body['search']) ?'':$body['search'];

        $pagesize = isset($body['pagesize']) && $body['pagesize'] > 0 ? $body['pagesize'] : 15;
        $page     = isset($body['page']) && $body['page'] > 0 ? $body['page'] : 1;
        $offset   = ($page - 1) * $pagesize;
        $role_auth_count = Roleauth::where(['is_del'=>1,'school_id'=> $adminUserInfo['school_id']])->where('ld_role_auth.role_name','like','%'.$body['search'].'%')->count();
        $sum_page = ceil($role_auth_count/$pagesize);
        if($role_auth_count >0){
            $roleRuthData =  self::rightjoin('ld_role_auth','ld_role_auth.admin_id', '=', 'ld_admin.id')
                ->where(function($query) use ($body,$adminUserInfo){
                if(!empty($body['search'])){
                    $query->where('ld_role_auth.role_name','like','%'.$body['search'].'%');
                }
                    $query->where('ld_role_auth.is_del',1);
                    $query->where('ld_role_auth.school_id',$adminUserInfo['school_id']);
                })
                ->select('ld_role_auth.role_name','ld_admin.username','ld_role_auth.auth_desc','ld_role_auth.create_time','ld_role_auth.id')
                ->offset($offset)->limit($pagesize)->get();

            return ['code'=>200,'msg'=>'Success','data'=>['role_auth_list' => $roleRuthData , 'total' => $role_auth_count , 'pagesize' => $pagesize , 'page' => $page,'search'=>$body['search'],'sum_page'=>$sum_page]];
        }
        return ['code'=>200,'msg'=>'Success','data'=>['role_auth_list' => [] , 'total' => 0 , 'pagesize' => $pagesize , 'page' => $page,'search'=>$body['search'],'sum_page'=>$sum_page]];
    }



}
