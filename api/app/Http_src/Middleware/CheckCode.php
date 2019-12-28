<?php

namespace App\Http\Middleware;

use Closure;
use App\Repositories\NoticeRepository;
use Illuminate\Support\Facades\Auth;

class CheckCode
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
        $code = $request->input('code');
        if (!$code){
            return response()->apiReturn([],401,'请输入验证码！');
        }
        $obj = resolve(NoticeRepository::class);
        $check = $obj->verCode($uid, $code);
        if (!$check){
            return response()->apiReturn([],401,'验证码错误！');
        }
        return $next($request);
    }
}
