<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2019/3/19
 * Time: 15:21
 */

namespace App\Http\Controllers\ApiAuth;


use App\Http\Controllers\Controller;

use App\Repositories\GroupchatRepository;
use App\Repositories\RedisRepository;
use Fruits\Apple\Auth\ApiAuthenticates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{

    use ApiAuthenticates;

    protected function sendLoginResponse(Request $request)
    {
        $this->clearLoginAttempts($request);
        $redisOBJ = resolve(RedisRepository::class);
        $user = Auth::guard($this->guard)->user();
        $data = $user->toArray();
        $data['api_token'] = $user->apiToken;
        $data['fraction'] = 12-$redisOBJ->getNum($user->id);

        $r = resolve(GroupchatRepository::class);
        $data['groupinfo'] = $r->handGetInfo($user->groupchat);
        return response()->apiReturn($data, 0);

    }
}
