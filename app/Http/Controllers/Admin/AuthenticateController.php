<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\School;
use App\Models\Teacher;
use Lysice\Sms\Facade\SmsFacade;
use Log;
use JWTAuth;
use Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Controllers\Admin\AdminUserController as AdminUser;




class AuthenticateController extends Controller {


    public function postLogin(Request $request) {

        $validator = Validator::make($request->all(), [
            'username'=> 'required',
            'password'=> 'required'
        ]);
        if ($validator->fails()) {
            return $this->response($validator->errors()->first(), 202);
        }

        $credentials = $request->only('username', 'password');

        return $this->login($credentials);
    }

    public function register(Request $request) {
        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            return response($validator->errors()->first(), 202);
        }
     
        $user = $this->create($request->all())->toArray();

        return $this->login($user);
    }

    /**
     * 身份认证
     *
     * @param  array  $data
     * @return User
     */
    protected function login(array $data)
    {

        try {
            if (!$token = JWTAuth::attempt($data)) {
                return $this->response('用户名或密码不正确', 401);
            }
        } catch (JWTException $e) {
            Log::error('创建token失败' . $e->getMessage());
            return $this->response('创建token失败', 500);
        }

        $user = JWTAuth::user();
        $user['token'] = $token;
        $this->setTokenToRedis($user->id, $token);
        if($user['is_forbid'] == 0 ||$user['is_del'] == 0 ){
            return response()->json(['code'=>403,'msg'=>'此用户已被禁用或删除，请联系管理员']);
        }

        if($user['is_use']  == 0 && empty($user['mobile'])){  //未使用 
            $user['auth'] = [];
            return $this->response($user);
        }
        if($user['is_use']  == 2 && !empty($user['mobile'])){  //待审核
            $user['auth'] = [];
            return $this->response($user);
        }

        $AdminUser = new AdminUser();
        $user['auth'] = [];     //5.14 该账户没有权限返回空  begin
        if($user['role_id']>0){
            $admin_user_atuh =  $AdminUser->getAdminUserLoginAuth($user['role_id']);  //获取后台用户菜单栏（lys 5.5）
             
            if($admin_user_atuh['code']!=200){
                return response()->json(['code'=>$admin_user_atuh['code'],'msg'=>$admin_user_atuh['msg']]);
            }
            $user['auth'] = $admin_user_atuh['data'];
        }   
        return $this->response($user);
    }
    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data) {
        return Validator::make($data, [
            'username' => 'required|max:255|unique:ld_admin',
            'mobile' => 'min:11',
            'password' => 'required|min:6',
            'email' => 'email',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data) {
        return Admin::create([
            'username' => $data['username'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),//bcrypt($data['password']),
            'email' => isset($data['email']) ?: '',
            'admin_id' => isset($data['admin_id']) ?: 0,
            'realname' => isset($data['realname']) ?: '',
            'sex' => isset($data['sex']) ?: 0,
            'mobile' => isset($data['mobile']) ?: '',
            'email' => isset($data['email']) ?: '',
        ]);
    }

    public function setTokenToRedis($userId, $token) {
        try {
            Redis::set('longdeOa:admin:' . env('APP_ENV') . ':user:token', $userId, $token);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
        return true;
    }

    /*
     * @param  description   获取验证码方法
     * @param  参数说明       body包含以下参数[
     * 
     * ]
     * @param author   lys
     * @param ctime     2020-09-08
     * return string
     */
    public function doSendSms(){
        $body = self::$accept_data;
        //判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return response()->json(['code' => 202 , 'msg' => '传递数据不合法']);
        }
      
        //判断手机号是否为空
        if(!isset($body['phone']) || empty($body['phone'])){
            return response()->json(['code' => 201 , 'msg' => '请输入手机号']);
        } else if(!preg_match('#^13[\d]{9}$|^14[\d]{9}$|^15[\d]{9}$|^17[\d]{9}$|^18[\d]{9}|^16[\d]{9}|^19[\d]{9}$#', $body['phone'])) {
            return response()->json(['code' => 202 , 'msg' => '手机号不合法']);
        }

        //设置key值
        $key = 'oadminuser:bind:'.$body['phone'];
        //保存时间(5分钟)
        $time= 300;
        //短信模板code码
        $template_code = 'SMS_180053367';
        // $AdminUser = new AdminUser();
        //判断用户手机号是否注册过
        $student_info = Admin::where(["mobile" =>$body['phone'],'is_del'=>1])->first();
        if($student_info && !empty($student_info)){
            return response()->json(['code' => 205 , 'msg' => '此手机号已被绑定']);
        }
        //判断验证码是否过期
        $code = Redis::get($key);
        if(!$code || empty($code)){
            //随机生成验证码数字,默认为6位数字
            $code = rand(100000,999999);
        }
        //发送验证信息流
        $data = ['mobile' => $body['phone'] , 'TemplateParam' => ['code' => $code] , 'template_code' => $template_code];
        $send_data = SmsFacade::send($data);
        //判断发送验证码是否成功
        if($send_data->Code == 'OK'){
            //存储学员的id值
            Redis::setex($key , $time , $code);
            return response()->json(['code' => 200 , 'msg' => '发送短信成功']);
        } else {
            return response()->json(['code' => 203 , 'msg' => '发送短信失败' , 'data' => $send_data->Message]);
        }
    }


    /*
     * @param  description   绑定手机号
     * @param  参数说明       body包含以下参数[
     *      phone 手机号
     *      user_id  用户id
     *      verifycode 验证码
     *      wx  微信 
     *      license     营业执照 
     *      hand_card   手持身份证照片 
     *      card_front  身份证正面 
     *      card_side   身份证反面 
     * ]
     * @param author    lys
     * @param ctime     2020-09-08
     */
    public function bindMobile(){
        try {
            DB::beginTransaction();
            $body = self::$accept_data;
            //判断传过来的数组数据是否为空
            if(!$body || !is_array($body)){
                return response()->json(['code' => 202 , 'msg' => '传递数据不合法']);
            }
             //判断用户标识是否为空
            if(!isset($body['user_id']) || empty($body['user_id']) || $body['user_id'] <0){
                return response()->json(['code' => 201 , 'msg' => '用户标识不合法']);
            }
            //判断用户标识是否为空
            if(!isset($body['real_name']) || empty($body['real_name'])){
                return response()->json(['code' => 201 , 'msg' => '请输入真实姓名']);
            }
            //判断手机号是否为空
            if(!isset($body['phone']) || empty($body['phone'])){
                return response()->json(['code' => 201 , 'msg' => '请输入手机号']);
            } else if(!preg_match('#^13[\d]{9}$|^14[\d]{9}$|^15[\d]{9}$|^17[\d]{9}$|^18[\d]{9}|^16[\d]{9}|^19[\d]{9}$#', $body['phone'])) {
                return response()->json(['code' => 202 , 'msg' => '手机号不合法']);
            }
            //判断验证码是否为空
            if(!isset($body['verifycode']) || empty($body['verifycode'])){
                return response()->json(['code' => 201 , 'msg' => '请输入验证码']);
            }

            //验证码合法验证
            $verify_code = Redis::get('oadminuser:bind:'.$body['phone']);
            if(!$verify_code || empty($verify_code)){
                return ['code' => 201 , 'msg' => '请先获取短信验证码'];
            }

            //判断验证码是否一致
            if($verify_code != $body['verifycode']){
                return ['code' => 202 , 'msg' => '短信验证码错误'];
            }
            $key = 'oauser:bind:'.$body['phone'];
            //判断此学员是否被请求过一次(防止重复请求,且数据信息存在)
            if(Redis::get($key)){
                return response()->json(['code' => 205 , 'msg' => '此手机号已被绑定！']);
            } else {
                //判断用户手机号是否注册过
                $student_count = Admin::where(["mobile" =>$body['phone'],'is_del'=>1])->count();
                if($student_count > 0){
                    //存储学员的手机号值并且保存60s
                    Redis::setex($key , 60 , $body['phone']);
                    return response()->json(['code' => 205 , 'msg' => '此手机号已被绑定']);
                }
            }
            $update['wx'] = isset($body['wx']) && !empty($body['wx']) ?$body['wx']:''; 
            $update['license'] = isset($body['license']) && !empty($body['license']) ?$body['license']:''; 
            $update['hand_card'] = isset($body['hand_card']) && !empty($body['hand_card']) ?$body['hand_card']:''; 
            $update['card_front'] = isset($body['card_front']) && !empty($body['card_front']) ?$body['card_front']:''; 
            $update['card_side'] = isset($body['card_side']) && !empty($body['card_side']) ?$body['card_side']:''; 
            $update['mobile'] = $body['phone'];
            $update['updated_at'] = date('Y-m-d H:i:s');
            $update['is_use'] = 2; 
            $update['real_name'] = $body['real_name'];
            
            $result = Admin::where('id',$body['user_id'])->update($update);
            if($result){
                DB::commit();
                return response()->json(['code' => 200 , 'msg' => '绑定成功']);
            }else{
                DB::rollBack();
                return response()->json(['code' => 203 , 'msg' => '绑定失败']);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }


}
