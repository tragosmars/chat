<?php
/**
 * Created by PhpStorm.
 * User: apple GeneratorCommand
 * Date: 2019/09/25
 * Time: 10:41:45
 */
namespace App\Http\Controllers;

use App\Events\SendTalk;
use App\Generator\Controllers\TalkControllerTrait;
use App\Repositories\TalkRepository;
use App\Repositories\UserRepository;
use Egulias\EmailValidator\Validation\Error\RFCWarnings;
use GatewayWorker\Lib\Gateway;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Request;

class TalkController extends Controller
{
    use RESTful,TalkControllerTrait;

    public function getObj()
    {
        return resolve(TalkRepository::class);
    }

    public function send(){

        $user = auth()->user();
        if ($user->is_talk && $user->is_talk > time()){
            return response()->apiReturn([], 401, '禁言中');
        }
        $content      =  Request::input("content");
        //$type = 1;
        //Gateway::$registerAddress = config('share.webSocket.registerAddress');
        $group = $user->groupchat;
        $message = [
            'type'=>'say',
            'from_user_name'=>$user->name,
            'uid' => $user->id,
            'pic' => $user->pic,
            'identity' => $user->identity,
            'to_group_id'=>$group,
            "content"=>$content,
            "time"=>time(),
        ];

        event(new SendTalk($message,1,$user->id));
        return response()->apiReturn(true);

    }

    public function bind()
    {
        $user = auth()->user();
        $group_id = $user->groupchat;
        $uid = $user->id;
        $clientId      =  Request::input("client_id");
        if (!$clientId){
            return response()->apiReturn([], 401, '参数错误');
        }
        Gateway::$registerAddress = config('share.webSocket.registerAddress');

// client_id与uid绑定
        Gateway::bindUid($clientId, $uid);
// 加入某个群组（可调用多次加入多个群组）
        Gateway::joinGroup($clientId, $group_id);
        resolve(TalkRepository::class)->onlineOrOffine($clientId, $uid);//设置在线和离线
        resolve(TalkRepository::class)->sendOfflineUnRead($uid);//
        return response()->apiReturn(["group_id"=>$group_id], 0);
    }


    public function addNoTalk(\Illuminate\Http\Request $request)
    {
        $user = Auth::user();
        $no_tale_id = $request->input('uid');
        $time = $request->input('time');
        if (!$no_tale_id || !$time){
            return response()->apiReturn([], 401, '参数错误');
        }
        try{
            $obj = $this->getObj();
            $ret = $obj->handAddNoTalk($user , $no_tale_id, $time);
            if ($ret !== true){
                return response()->apiReturn([], 401, $ret);
            }
            return response()->apiReturn($ret);
        }catch (\Exception $exception){
            return response()->apiReturn([], 404,'运行失败');
        }

    }

    public function addYesTalk(\Illuminate\Http\Request $request)
    {
        $user = Auth::user();
        $no_tale_id = $request->input('uid');
        if (!$no_tale_id){
            return response()->apiReturn([], 401, '参数错误');
        }
        try{
            $obj = $this->getObj();
            $ret = $obj->handAddYesTalk($user , $no_tale_id);
            if ($ret !== true){
                return response()->apiReturn([], 401, $ret);
            }
            return response()->apiReturn($ret);
        }catch (\Exception $exception){
            return response()->apiReturn([], 404, '参数错误');
        }

    }


   //判断指定用户是否在线
    public function isOnline(TalkRepository $talkRepository)
    {
        $uid = Auth::id();
        $ret = $talkRepository->isOnline($uid);
        return response()->apiReturn([$ret]);
    }



}
