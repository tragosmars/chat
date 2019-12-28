<?php

namespace App\Http\Middleware;

use App\Repositories\UserInfoRepository;
use Closure;
use Illuminate\Support\Facades\Auth;

class CheckRealName
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
        $obj = resolve(UserInfoRepository::class);
        $ret = $obj->handIsReadName($uid);
        if(!$ret){
            return response()->apiReturn([],402,'请先实名登记！');
        }
        return $next($request);
    }
}
