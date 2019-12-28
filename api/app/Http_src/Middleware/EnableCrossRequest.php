<?php

namespace App\Http\Middleware;

use Closure;

class EnableCrossRequest
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
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Origin, Content-Type, Cookie, X-CSRF-TOKEN, Accept, TUID,Authorization, X-XSRF-TOKEN, X-Requested-With');
        header('Access-Control-Expose-Headers: Authorization, authenticated');
        header('Access-Control-Allow-Methods: GET, POST, PATH, PUT, OPTIONS');
        header('Access-Control-Expose-Headers: *');
        //$next($request)->header('Access-Control-Allow-Credentials', 'true');
        return $next($request);
    }
}
