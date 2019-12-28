<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2019/3/18
 * Time: 20:21
 */

namespace App\Http\Controllers\ApiAuth;


use App\Events\UserRegister;
use App\Http\Controllers\Controller;
use App\Repositories\UserRepository;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Fruits\Apple\Verification\Mobile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Cache\RateLimiter;

class RegisterController extends  Controller
{

    public function register(Request $request)
    {

        $this->validator($request->all())->validate();

        $code = $request->input('code');
        $mobile =  $request->input('mobile');

        $verification = new Mobile($mobile,Mobile::TYPE_REG);
        if($verification->check($code)){

            $parent = $this->varCode($request->input('sharecode'));
            if (!$parent){
                return response()->apiReturn([],401,'邀请码错误',200);
            }

            $verification->clean();
            $this->create($request->all(),$parent);
            return response()->apiReturn([__('register.success')]);
        }else{
            return response()->apiReturn([__('register.codeFail')],0,'', 419);
        }

    }
    public function verification(Request $request)
    {
        Validator::make($request->all(), [
            'mobile' => 'required|string|max:255|unique:users',

        ])->validate();


        $mobile = $request->input('mobile');
        $decaySeconds = 60; //60秒内只能发一次
        $maxAttempts = 1;
        $key = $mobile;
        $limiter =  resolve(RateLimiter::class);
        if ($limiter->tooManyAttempts($key, $maxAttempts)) {

            return response()->apiReturn([],400,["mobile"=>"一个手机号{$decaySeconds}秒内只能请求一次"]);
        }


        $verification = new Mobile($mobile,Mobile::TYPE_REG);
        $verification->generate();
        $verification->send();
        $limiter->hit($key, $decaySeconds);
        return response()->apiReturn([__('register.sentCode')]);
    }





    protected function create(array $data,$parent)
    {
        $obj = resolve(UserRepository::class);
        $shara_code = $obj->getRandCode();

        $ret =  User::create([
            'mobile' => $data['mobile'],
            'password' => Hash::make($data['password']),
            'shara_code' => $shara_code,
            'parent_id' => $parent->id,
            'initial_id' => $parent->initial_id===0?$parent->id:$parent->initial_id,
            'level' => ($parent->level+1)
        ]);
        if ($parent->initial_id === 0){
            $ret->initial_id = $ret->id;
            $ret->save();
        }


        event(new UserRegister($ret, $parent->groupchat));
    }

    protected function varCode($sharecode)
    {
        $obj = resolve(UserRepository::class);
        return $obj->codeExist($sharecode);
    }


    protected function validator(array $data)
    {
        return Validator::make($data, [
            'mobile' => 'required|string|max:255',
            'code' => 'required|int',
            'password' => 'required|string|min:6|confirmed',
            'sharecode'=>'required|string'
        ]);


    }
}