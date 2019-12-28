<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2019/9/17
 * Time: 17:58
 */

namespace App\Http\Controllers;


use GatewayWorker\Lib\Gateway;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ChatController extends  Controller
{

    public function index(){



        return response()->view("chat/index");
    }
    public function send(){

        $user = auth()->user();
        Gateway::$registerAddress = '127.0.0.1:1236';

        $content      =  Request::input("content");
        $group = "root";
       // $message = '{"type":"say","from_client_id":xxx,"to_client_id":"all/client_id","content":"xxx","time":"xxx"}';
        $message = [
            'type'=>'say',
            'from_user_name'=>$user->name,
            'to_group_id'=>$group,
            "content"=>$content,
            "time"=>date("Y-m-d H:i:s"),


        ];

        Gateway::sendToGroup($group, json_encode($message));
    }
    public function bind()
    {
        $clientId      =  Request::input("client_id");
        $uid = Auth::user()->id;
        Gateway::$registerAddress = '127.0.0.1:1236';

// 假设用户已经登录，用户uid和群组id在session中
     //   $uid      =   $user = auth()->user()->id;

        $group_id = "1";


// client_id与uid绑定
        Gateway::bindUid($clientId, $uid);
// 加入某个群组（可调用多次加入多个群组）
        Gateway::joinGroup($clientId, $group_id);

        return response()->apiReturn(["group_id"=>$group_id], 0);
    }
}