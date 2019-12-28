<?php

namespace App\Http\Middleware;

use Closure;
use App\Repositories\UserInfoRepository;
use Illuminate\Support\Facades\Auth;

class CheckPassword
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $uid = Auth::user()->id;
        $password = $request->input('password');
        if (!$password){
            return response()->apiReturn([],401,'请输入交易密码！');
        }
        $obj = resolve(UserInfoRepository::class);
        $ret = $obj->handVerificationPassword($uid, $password);
        if(!$ret){
            return response()->apiReturn([],410,'交易密码错误！');
        }
        return $next($request);
    }
}
