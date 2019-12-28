<?php
/**
* Created by PhpStorm.
* User: apple GeneratorCommand
* Date: 2019/09/25
* Time: 18:09:29
*/

namespace App\Repositories;
use App\Events\NoticeUser;
use App\Events\RedPacketFun;
use App\Events\SendTalk;
use App\Generator\Repositories\TaskRepositoryTrait;
use App\Storage\Database\Flow;
use App\Storage\Database\Task;
use App\Storage\Database\User;
use Faker\Factory;
use Illuminate\Support\Facades\DB;

class TaskRepository extends  Repository
{
    use TaskRepositoryTrait;

    //验证用户有效性
    public function isUser($uid,$time)
    {
        $userOBJ = resolve(UserRepository::class);
        $user = $userOBJ->getOne($uid);
        if (!$user || $user->identity !== 3){
            return 'appId错误';
        }
        if($time < (time()-config('share.red_packet.request_time'))){//配置时间
            return '已失效';
        }
        return array(
            'user' => $user
        );

    }
   //检查签名是否正确
    public function checkSign($userData,$sign)
    {

        asort($userData,SORT_STRING);
        $str = '';
        foreach ($userData as $k=>$v){
            $str.= $k.$v;
        }
        $sign_md5 = md5($str);
//         dd($sign_md5);
        if ($sign != $sign_md5){
            return '签名错误';
        }
        return true;
    }


    public function handAddTask($uid,$num,$time,$rand, $pay,$sign)
    {
        if (!is_int($num+0)){
            return '请输入整数数量！';
        }
        $use_pay = array();
        for ($i = 1,$k = 1; $k <= $pay;){
            if (($k & $pay) > 0){
                $use_pay[] = $k;
            }
            $k = pow(2,$i);
            $i++;
        }
        if(!$use_pay){
            return '请填写支付方式';
        }
        $ret_pay = $this->getRedPay($use_pay);
        if (!is_array($ret_pay)){
            return $ret_pay;
        }


        $user_ret = $this->isUser($uid,$time);
        if (!is_array($user_ret)){
            return $user_ret;
        }
        $userData = array(
            'app_id' => $uid,
            'num' => $num,
            'time' => $time,
            'rand' => $rand,
            'pay' => $pay,
            'service'=>$user_ret['user']->red_packets->service,
        );
        $ret = $this->checkSign($userData, $sign);

        if ($ret !== true){
            return $ret;
        }
        $start_time = time();
        $end_time = $start_time+config('share.red_packet.efffet_at') ;
        $data = array(
            'order' => $this->getOrder(),
            'send_uid' => $uid,
            'num' => $num,
            'price' => 1.00,
            'money' => $num,
            'status' => 1,
            'repeat_num' => 0,
            'efffet_at' => $end_time,
            'use_pay' => json_encode($use_pay),
        );
        $result = $this->storage()::create($data);
        if (!$result){
            return '发送失败';
        }

        $content = array(
            'type'=>'red_packet',
            'uid' => $uid,
            'from_user_name'=>$user_ret['user']->name,
            'pic' => $user_ret['user']->pic,
            'to_group_id'=>$user_ret['user']->groupchat,
            "content"=> $num,
            "pay" =>$ret_pay,
            "start_time"=>$start_time,
            'end_time' => $end_time,
            'red_packet_id' => $result->order,
            'repeat_num' => 0,
            'status' => 1,
        );
        event(new SendTalk($content, 1,$uid));//发送红包通知
        return array(
            'order'=>$result->order,
        );
    }

    public function getRedPay($use_pay)
    {
        $config_pay = config('pay.list');
        $ret_pay = array();
        foreach ($use_pay as $v){
            if (!isset($config_pay[$v])){
                return '支付方式错误';
            }
            $one = array(
                'name' => $config_pay[$v]['name'],
                'pic' => $config_pay[$v]['pic'],
            );
            $ret_pay[] =$one;
        }
        return $ret_pay;
    }


   //获取随机唯一订单号
    public function getOrder()
    {
        $obj = Factory::create();
        do{
            $rand = $obj->regexify('[0-9]{10}');
            $w = array(
                'order' => $rand
            );
            $data = $this->storage()::where($w)->first();
        }while($data);
        return $rand;
    }
    //处理 商户获取订单状态
    public function handGetStauts($uid ,$time,$order,$sign)
    {
        $user_check = $this->isUser($uid, $time);
        if (!is_array($user_check)){
            return $user_check;
        }
        $sign_data = array(
            'app_id' => $uid,
            'time' => $time,
            'order' => $order,
            'service'=>$user_check['user']->red_packets->service,
        );
        $sign_check = $this->checkSign($sign_data, $sign);
        if ($sign_check !== true){
            return $sign_check;
        }
        //验证通过
        $w = array(
            'order' => $order,
            'send_uid' => $uid
        );
        $data = $this->storage()->where($w)->first();
        if (!$data){
            return '订单错误！';
        }
        $data = array(
            'order' => $data->order,
            'status' => $data->status,
            'repeat_num' => $data->repeat_num,
            'effective_time' => $data->efffet_at
        );
        return $data;


    }

    //用户取消订单
    public function handSetCancel($uid ,$time,$order,$sign)
    {
        $user_check = $this->isUser($uid, $time);
        if (!is_array($user_check)){
            return $user_check;
        }
        $sign_data = array(
            'app_id' => $uid,
            'time' => $time,
            'order' => $order,
            'service'=>$user_check['user']->red_packets->service,
        );
        $sign_check = $this->checkSign($sign_data, $sign);
        if ($sign_check !== true){
            return $sign_check;
        }
        //签名验证通过
        $w = array(
            'order' => $order,
            'send_uid' => $uid
        );
        $data = $this->storage()->where($w)->first();
        if (!$data){
            return '订单错误！';
        }
        if ($data->status == 1){
          $ret = $this->storage()-> setCancel($data, 0.00);
        }else if ($data->status == 2 && $data->complaints_at > time()){
            $obj = new Flow();
            DB::beginTransaction();
            $ret = $obj->redPacketNoPayMoney($data,2);
            if (!$ret){
                DB::rollBack();
            }
            DB::commit();
        }else{
            return '当前订单无法取消';
        }
        return $ret;


    }


    //处理用户枪红包任务
    public function handUserTaskGenerate($user, $order)
    {
       $data = $this->storage()->getEffect($order);
       if (!$data){
            return '40001';//您手慢了，任务已被其他用户抢到或已过期了！
       }
        $account = $user->account;
        if (bccomp($account->flow, $data->num, 2) < 0){
            return '40002';//账户余额不足！
        }
        $user_pay = array(
            'uid' => $user->id
        );
        $payOBJ = resolve(PayRepository::class);
        $pay_data = $payOBJ->storage()->where($user_pay)->get();
        $user_pay_array = array();
        $payType = config('pay.list');
        $img_url = config('filesystems.disks.code.url');
        foreach ($pay_data as $v){
            $one = array(
                'type' => $payType[$v->pay_id]['name'],
                'name' => $v->name,
                'account' => $v->account,
                'payPic'=>$img_url.'/'.$v->pay_pic,
            );
            if ($payType[$v->pay_id]['is_list']){
                $one['bankName'] = json_decode($v->other,true)['bankName'];
            }
            if ($payType[$v->pay_id]['is_more']){
                $user_pay_array[$v->pay_id][]=$one;
            }else{
                $user_pay_array[$v->pay_id]=$one;
            }

        }
        $red_pay = json_decode($data->use_pay,true);
        $send_pay = array();
        foreach ($red_pay as $v){
            if (isset($user_pay_array[$v])){
                $send_pay[] = $user_pay_array[$v];
            }
        }
        if (!$send_pay){
            return '40003';//支付方式不支持
        }

        try{
            DB::beginTransaction();
            $ret = $this->storage()->setReceipt($order, $user->id,$send_pay);
            if (!$ret){
                DB::rollBack();
                return '40001';//手慢了，任务已被其他人抢到
            }
            $account->flow = bcsub($account->flow, $data->num,2);
            $account->frozen = bcadd($account->frozen, $data->num,2);
            $acc_ret = $account->save();
            if (!$acc_ret){
                DB::rollBack();
                return '40004';//抢单失败！
            }
            DB::commit();
        }catch (\Exception $exception){
            DB::rollBack();
            return '40004';//抢单失败了！
        }
        $notice = array(
            'type' => 1,
            'order' => $data->order,
            'pay' => json_encode($user_pay_array),
            'money' => $data->money,
            'content' => '用户已接单',
        );
        event(new RedPacketFun($data->send_uid,$notice));

        $content = array(
            'type'=>'red_packet_success',
            'from_user_name'=>$user->name,
            'pic' => $user->pic,
            'order' => $order,
            'repeat_num' => $data->repeat_num,
            "content"=> '抢单成功',
            "time"=>time(),
        );
        event(new SendTalk($content, 1));//发送红包通知


        return true;
    }



    public function handGetDatil($user, $order)
    {
        $w = array(
            'uid' => $user->id,
            'order' => $order
        );
        $data = $this->storage()->where($w)->first();
        if (!$data){
            return '订单不存在！';
        }
        $redUser = $data->user;
        $hand_complaints = '';
        if ($data->complaints_id){
            $complaints = $data->complaints;
            $hand_complaints = $complaints->hand_content;
        }
        $result = array(
            'order' => $data->order,
            'pay_currency_user' => array(
                'name' =>$redUser->name,
                'pic' => $redUser->pic,
            ),
            'pay_currency_user' => array(
                'name' =>$user->name,
                'pic' => $user->pic,
            ),
            'complaints_at'=>$data->complaints_at - time(),//可投诉期限
            'status' => $data->status,
            'num' => $data->num,
            'price' => $data->price,
            'money' => $data->money,
            'task_at' => $data->task_at,//订单创建时间
            'get_money_at'=>$data->get_money_at,//付款时间
            'get_currency_at' => $data->get_currency_at,//付币时间
            'set_complaints_at'=>$data->set_complaints_at,//投诉时间
            'hand_complaints'=>$hand_complaints,
            'cancel_at' => $data->cancel_at,//取消时间
            'cancel_reward' => $data->cancel_reward,//赔偿奖励
        );
        return $result;
    }


    //获取商户过期未支付订单
    public function getExpirePayMoney()
    {
        $w = array(
            'status' =>2,
            array('complaints_at','<',time()),
        );
        return $this->storage()->where($w)->get();
    }


    public function handSetPayMoney($uid ,$time,$order,$sign)
    {
        $user_check = $this->isUser($uid, $time);
        if (!is_array($user_check)){
            return $user_check;
        }
        $sign_data = array(
            'app_id' => $uid,
            'time' => $time,
            'order' => $order,
            'service'=>$user_check['user']->red_packets->service,
        );
        $sign_check = $this->checkSign($sign_data, $sign);
        if ($sign_check !== true){
            return $sign_check;
        }
        $w = array(
            'order' => $order,
            'send_uid' =>$uid
        );
        $data = $this->storage()->where($w)->first();
        if (!$data){
            return '订单不存在';
        }
        if ($data->status !== 2){
            return '订单状态错误！';
        }
        if ($data->status == 2 && $data->complaints_at < time()){
            return '超时付款，请联系管理员！';
        }
        $ret = $this->storage()->setPayMoney($data);
        if (!$ret){
            '订单修改失败，请联系管理员';
        }
        $content = array(
            'type'=>'user_nocite_1',
            'content' => '订单'.$data->order.'已付款,请注意查收！',    //通知内容
            'time' => time(),    //通知时间 -时间戳
        );
        event(new NoticeUser($content,'ORDER',$data->uid));
        return true;

    }

    //商户过期未支付订单
    public function setNoPayMoney(Task $task)
    {
        $obj = new Flow();
        DB::beginTransaction();
        $ret = $obj->redPacketNoPayMoney($task);
        if (!$ret){
                DB::rollBack();
        }
        DB::commit();
        return true;
    }

    //处理用户确定支付币
    public function handPayCurrency($user, $order)
    {
        $task = $this->storage()->getPayCurrencyOrder($order, $user->id);
        if (!$task){
            return '订单错误！';
        }
        $obj = new Flow();
        DB::beginTransaction();
        $ret = $obj->setRedPacketSuccess($task);
        if (!$ret){
            DB::rollBack();
            return '支付错误!';
        }
        DB::commit();

        $notice = array(
            'type' => 2,
            'order' => $task->order,
            'pay' => '',
            'money' => '',
            'content' => '用户已付币',
        );
        event(new RedPacketFun($task->send_uid,$notice));

        return true;
    }
    //用户投诉买家--获取
    public function getComplaintRed($uid, $order)
    {
        return $this->storage()->getComplaintOrder($order, $uid);
    }
    //用户投诉买家--设置
    public function setComplaintRed($uid, $order)
    {

       return $this->storage()->setComplaintOrder($order, $uid);

    }

    //红包商户投诉
    public function handSetComplaints($uid ,$time,$order,$sign){
        $user_check = $this->isUser($uid, $time);
        if (!is_array($user_check)){
            return $user_check;
        }
        $sign_data = array(
            'app_id' => $uid,
            'time' => $time,
            'order' => $order,
            'service'=>$user_check['user']->red_packets->service,
        );
        $sign_check = $this->checkSign($sign_data, $sign);
        if ($sign_check !== true){
            return $sign_check;
        }
        return  $this->storage()->addRedComplaint($order, $uid);

    }

    public function handGetTaskIndex($user,$type, $page,$num,$tag = 0)
    {
        $data = $this->storage()->getDatas($user,$type, $page,$num,$tag);
        if ($data === false){
            return '参数错误';
        }
        $result = array();
        $redisOBJ = resolve(RedisRepository::class);
        foreach ($data['data'] as $v){
            $one = array(
                'order' => $v->order,
                'sell_currency' => array(
                    'name' => $v->getUser->name,
                    'pic' => $v->getUser->pic,
                    'grade' => $v->getUser->grade,
                    'fraction' => 12-$redisOBJ->getNum($v->getUser->id),
                ),
                'buy_currency' => array(
                    'name' => $v->user->name,
                    'pic' => $v->user->pic,
                    'grade' => $v->user->grade,
                    'fraction' => 12-$redisOBJ->getNum($v->user->id),
                ),
                'num' => $v->num,
                'created_at'=>$v->created_at,

            );
            $result['list'][] =$one;
        }
        $result['total'] = $data['total'];
        return $result;
    }

    //获取红包状态
    public function getState($uid, $order, $repeat_num = 0)
    {
        $w  = [
            'order' => $order
        ] ;
        $desc = '';
        $code = 0;
        $data = $this->storage()->where($w)->first();
        if (!$data){
            $code = 0;
            $desc = '红包不存在！';
        }else{
            //生成红包
            if ($data->status == Task::ORDER_GANERATE){
                if ($data->repeat_num != $repeat_num || $data->efffet_at < time()){
                    $code = 2;
                    $desc = '已过期！';
                }else{
                    $code = 1;
                    $desc = '';
                }
            }else if ($data->status == Task::ORDER_NORECEIPT ){
                $code = 2;
                $desc = '已过期！';
            }else{
                $code = 3;
                if ($data->uid == $uid){
                    $desc = '恭喜你！';
                }else{
                    $desc = '您来晚了！';
                }
            }


        }

        return [
            'code' => $code,
            'desc' => $desc
        ];

    }


    /**
     *
     *  发送红包状态修改通知
     *
     * @param $order int 红包id
     * @param  $state int 红包状态  2，红包已过期  3，红包已被抢
     * @param $user User 用户对象
     * @param $type string 通知类型
     * @return  bool
     *
     * */
    public function sendRedStateNotice($order, $state, $user = null, $type= 'Red')
    {
        if ($state != 3 && $state != 2){
            return false;
        }
        $user_data = [];
        if ($state == 3){
            $user_data = [
                'id' => $user->id,
                'name' => $user->name,
                'pic' => $user->pic
            ];
        }
        $message = [
            'type'=>'red',
            'order' => $order,
            'state' =>$state,
            'user' => $user_data,
            "time"=>time(),
        ];
        event(new SendTalk($message,1));
    }
}