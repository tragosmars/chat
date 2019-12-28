<?php
/**
* Created by PhpStorm.
* User: apple GeneratorCommand
* Date: 2019/09/25
* Time: 10:41:45
*/

namespace App\Repositories;
use App\Events\NoticeUser;
use App\Generator\Repositories\TalkRepositoryTrait;
use App\Events\SendTalk;
use App\Storage\Database\Talk;
use App\Storage\Database\Task;
use GatewayWorker\Lib\Gateway;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class TalkRepository extends  Repository
{
    use TalkRepositoryTrait;
    const ONLINE = 'online';//在线key
    const OFFLINE = 'offline'; //离线key

    public function handSend($user, $type, $content)
    {


    }

    public function handAddNoTalk($user , $uid, $time)
    {
        $obj = resolve(UserRepository::class);
        $data = $obj->getOne($uid);
        if (!$data){
            return '用户不存在';
        }
        if ($user->groupchat != $data->groupchat){
            return '当前用户无权设置';
        }
        $data->is_talk = time()+$time;
        $ret = $data->save();
        if (!$ret){
            return '禁用失败';
        }
        //禁言通知
        $message = [
            'type'=>'group_no_talk',
            'user_id' => $data->id,
            'user_name' => $data->name,
            'no_talk' => $time,
            'set_id' =>Auth::user()->id,
            'set_name' => Auth::user()->name,
            "time"=>time(),
        ];
        event(new SendTalk($message, 1,Auth::user()->id));
        return true;

    }

    public function handAddYesTalk($user , $no_tale_id)
    {
        $obj = resolve(UserRepository::class);
        $data = $obj->getOne($obj);
        if (!$data){
            return '用户不存在';
        }
        if ($user->groupchat != $data->groupchat){
            return '当前用户无权设置';
        }
        if ($data->is_talk < time()){
            return '当前用户没有被禁言';
        }
        $data->is_talk = null;
        $ret = $data->save();
        if (!$ret){
            return '操作失败';
        }
        $message = [
            'type'=>'group_set_talk',
            'user_id' => $data->id,
            'uid' => Auth::user()->id,
            'user_name' => $data->name,
            'set_id' =>Auth::user()->id,
            'set_name' => Auth::user()->name,
            "time"=>time(),
        ];
        event(new SendTalk($message,1,Auth::user()->id));
        return true;

    }


    /**
     *
     * 处理redis在线离线数据
     *
     *
     * @param $clientId string 用户标识
     * @param $uid int 用户id
     * @return  bool
     * */
    public function onlineOrOffine($clientId, $uid)
    {
        $online = self::ONLINE;
        Redis::ZSCORE($online, $clientId);
        $old_data = Redis::ZREMRANGEBYSCORE($online,$uid, $uid);//移除uid已存在的
        Redis::zadd($online, $uid, $clientId);//增加在线
        $offline = self::OFFLINE;
        Redis::srem($offline, $uid);
        return true;
    }

    /**
     *
     *  获取用户未读信息redis存储key
     *
     * @param $uid int 用户id
     * @return  string
     * */
    public function getUnreadKey($uid)
    {
        return "unread_{$uid}";
    }


    /**
     *
     *  发送离线数据
     *
     * @param $uid int 用户id
     * */
    public function sendOfflineUnRead($uid)
    {
        $ids = $this->getOfflineChatId($uid);
        if ($ids){
            $data = Talk::whereIn('id', $ids)->orderBy('id','asc')->get();
            foreach ($data as $value){
                $content = json_decode($value->content, true);
                if ($content['type'] == 'red_packet'){
                    $order_id = $content['red_packet_id'];
                    $content['status'] = $this->getRedStatus($order_id, $content['repeat_num']);
                };
               event(new SendTalk($content,0 ,$uid));
            }
            $this->delUnread($uid);
        }

    }

    /**
     *
     * 删除未读记录
     * */
    public function delUnread($uid)
    {
        $key = $this->getUnreadKey($uid);
        Redis::del($key);
    }

    /**
     *
     * 获取红包状态
     *
     * @param  $order int 红包id
     * @param $num int 红包次数
     * @return bool
     *
     * */
    public function getRedStatus($order, $num)
    {
        $w = [
            'order' => $order
        ];
        $data = Task::where($w)->first();
        if ($data->status == Task::ORDER_GANERATE && $data->repeat_num == $num && $data->efffet_at > time()){
            $state = 1;//正常
        }else if ($data->status == Task::ORDER_GANERATE && $data->repeat_num == $num && $data->efffet_at < time()){
            $state = 3;//过期
        }else if ($data->status == Task::ORDER_GANERATE && $data->repeat_num  > $num){
            $state = 3;//过期，红包已重发
        }else if($data->status == Task::ORDER_NORECEIPT){
            $state = 3;//已过期
        }else{
            $state = 2;//红包已被抢
        }
        return $state;
    }





    /**
     *
     * 获取离线未读消息ID
     *
     * @param $uid int 用户id
     * @return  array
     *
     * */
    public function getOfflineChatId($uid)
    {
        $key = $this->getUnreadKey($uid);
        $num = config('share.group.chat_save_number');
        $ids = Redis::ZREVRANGE($key, 0, $num,'WITHSCORES');
        $ret = [];
        if ($ids){
            $ret = array_keys($ids);
        }
        return $ret;
    }


    /**
     *
     * 判断用户是否在线
     *
     * @param  $uid int 用户id
     * @return  bool
     * */
    public function isOnline($uid)
    {
        Gateway::$registerAddress = config('share.webSocket.registerAddress');
        $is_online = Gateway::isUidOnline($uid);
        if ($is_online){
            return true;
        }
        return false;
    }




}