<?php
/**
* Created by PhpStorm.
* User: apple GeneratorCommand
* Date: 2019/09/20
* Time: 16:12:07
*/

namespace App\Repositories;
use App\Events\NoticeUser;
use App\Generator\Repositories\TransactionOrderRepositoryTrait;
use App\Storage\Database\Flow;
use App\Storage\Database\TransactionOrder;
use Illuminate\Support\Facades\DB;
use Faker\Factory;

class TransactionOrderRepository extends  Repository
{
    use TransactionOrderRepositoryTrait;
    const CANCEL_NUM = 3000;
    const WAIT_PAY = 3000;
    public function handStore($uid,$parent_id, $order, $num)
    {
        if (!preg_match("/^[0-9]{1,}$/",$num) || $num<=0){
            return '请输入大于0的整数';
        }
        $tranOBJ = resolve(TransactionRepository::class);
        $order = $tranOBJ->getEff($order);
        if (!$order){
            return '订单错误!';
        }
        if ($order->uid == $uid){
            return '当前用户不能购买!';
        }
        if (bccomp($order->max, $num,2) < 0){
            return '当前限制最大交易为'.$order->max;
        }
        if (bccomp($order->min, $num,2) > 0){
            return '当前限制最小交易为'.$order->min;
        }

        $redisOBJ = resolve(RedisRepository::class);
        $cancelNun = $redisOBJ->getNum($uid);
        if ($cancelNun > self::CANCEL_NUM ){
            return '今日取消交易次数太多，不能继续交易!';
        }
        $generateCount = $this->storage()->getGenerateCount($uid);
        if ($generateCount > self::WAIT_PAY){
            return '请先处理待支付任务';
        }
        $userOBJ = resolve(UserInfoRepository::class);
        $parent = $userOBJ->getOne($parent_id);//获取祖父id的费率
        $parent_num = bcmul($num,bcadd(1,$parent->rate,3),2);//祖父数量
        $my_info = $userOBJ->getOne($uid);
        if (bccomp($order->surplus, $parent_num,2) < 0){
            return '订单剩余数量不足，请选择其他订单交易';
        }
        $pay_array = json_decode($order->pay_list,true);
//        $pay_data = array();
//        foreach ($pay_array as $k => $v){
//            if ($v == $pay){
//                $pay_data[$k] = $v;
//            }
//        }
//        if (!$pay_data){
//            return '支付方式错误';
//        }
        DB::beginTransaction();
        try{
            //占用数量
            $order->surplus = bcsub($order->surplus,$parent_num, 2);
            $order->conduct = bcadd($order->conduct,$parent_num,2);
            $order_ret = $order->save();
            if (!$order_ret){
                DB::rollBack();
                return '订单处理失败';
            }
            $obj = $this->storage();
            $create_data = array(
                "order_id" => $this->getRandOrder(),
                "uid" => $uid,
                "transactions_id" => $order->order_id,
                'sell_uid' => $order->uid,
                "num"=> $num,
                'price' => $order->price,
                "parents_num"=> $parent_num,
                "get_num"=> bcmul($num,bcadd(1,$my_info->rate,3),2),
                "pay_type"=> 0,//$pay
                "pay_rand"=> mt_rand(100000,999999),
                "pay_list"=> "",//json_encode($pay_data)
                "term_of_validity"=> config('share.order_term_of_validity')+time(),
        );

            $tran_ret = $obj::create($create_data);
            if ($tran_ret){
                DB::commit();
                return array(
                    'result' => true,
                    'data' => $tran_ret->order_id
                );
            }
            DB::rollBack();
            return '订单处理错误';
        }catch (\Exception $exception){
            DB::rollBack();
            return '系统忙，请稍后再试';
        }
    }


    public function getRandOrder()
    {
        $obj = Factory::create();
        do{
            $id = $obj->regexify('[0-9]{8}');
            $w = array(
                'order_id' => $id
            );
            $ret = $this->storage()::where($w)->first();
        }while($ret);
        return $id;
    }


    public function handShow($uid, $order)
    {
        $data = $this->getOrder($uid, $order);
        if (!$data){
            return '订单错误';
        }
        $list = $data->transaction;
        if ($list->uid != $uid && $data->uid != $uid){
            return '用户错误';
        }
        $user = $list->user;
        $buy_user = $data->user;
        $pay = config('pay.list');
        $payOBJ = resolve(PayRepository::class);
        $pay_list = json_decode($list->pay_list, true);
        $pay_ids = array_keys($pay_list);
        $pay_num = $payOBJ->storage()->whereIn('id', $pay_ids)->get();
        $group_pay = $pay_num->groupBy('pay_id')->toArray();
        $pay_data = [];
        foreach ($group_pay as $ks => $vs){//获取图片标志
            foreach ($vs as $kd => $vd){
                $vs[$kd]['other'] = json_decode($vd['other']);
            }
            $pay_data[$ks]['list'] = $vs;
            $pay_data[$ks]['pic'] = $pay[$ks]['pic'];
        }
        $result = array(
            'sell_user' => array(
                'name' => $user->name,
                'pic' => $user->pic,
                'mobile' => $user->mobile,
            ),
            'buy_user' => array(
                'name' => $buy_user->name,
                'pic' => $buy_user->pic,
                'mobile' => $buy_user->mobile,
            ),
            'order' => array(
                'order_id' => $data->order_id,
                'pay_rand' => $data->pay_rand,
                'price' => $data->price,
                'created_at'=>$data->created_at,
                'term_of_validity'=>($data->term_of_validity - time()) <=0?-1:$data->term_of_validity - time(),//订单有效期
                'money' => bcmul($data->num,$data->price,2),
                'num' => $data->num,
                'get_num' => $data->get_num,
                'status' => $data->status,
                'remarks' => $list->remarks,
//                'pay' => array(
//                    'pay_name' =>$pay[$data->pay_type]['name'],
//                    'pay_pic' => $pay[$data->pay_type]['pic'],
//                    'is_list' => $pay[$data->pay_type]['is_list'],
//                    'pay_info' => $payOBJ->getPayInfo(array_keys(json_decode($data->pay_list,true)),$list->uid),
//                ),
                'pay' =>$pay_data,
                'pay_money_time' => $data->pay_money_time,//付款时间
                'pay_currency_time' => $data->pay_currency_time,//付币时间
                'pay_currency_overtime'=>$data->status ==2?($data->pay_money_time+config('share.order_pay_currency_overtime')):'',


            ),
        );
        return $result;

    }

    public function getOrder($uid, $order)
    {
            $w = array(
//                'uid' => $uid,
                'order_id'=> $order
            );
            return $this->storage()::where($w)->first();
    }

    public function handPayment($uid, $order)
    {
        $ret = $this->storage()->setPayMent($order,$uid);
        if (!$ret){
            return '订单错误';
        }
        return true;

    }

    public function handPayCance($uid, $order)
    {
        $ret = $this->storage()->setPayCancel($order,$uid);
        if (!$ret){
            return '订单错误';
        }
        return true;
    }

    //获取过期订单
    public function getExpire()
    {
        return $this->storage()->getExpires();
    }


    public function handPayNocollectMoney($uid, $order)
    {
        $ret = $this->storage()->setNoCollectMoney($order,$uid);
        if (!$ret){
            return '处理错误!';
        }
        return true;
    }

    //获取卖家未付币
    public function getNoPayCurrency()
    {
        return $this->storage()->getNoPayCurrency();
    }
    //修改买家未付币
    public function setNoPayCurrencys(TransactionOrder $order){
        return $this->storage()->setNoPayCurrency($order);
    }


    public function handPayCollectMoney($uid, $order)
    {
        $order_data = $this->storage()->getPayCurrency($order);
        if (!$order_data){
            return '订单错误';
        }
        $transaction = $order_data->transaction;
        if ($transaction->uid != $uid){
            return '用户错误';
        }
        $flowOBJ = new Flow();
        try{
            DB::beginTransaction();
            $sell_ret = $flowOBJ->listSell($order_data);
            if ($sell_ret !== true){
                DB::rollBack();
                return '订单处理失败！';//处理卖家账户错误
            }
            $buy_data = $flowOBJ->listBuy($order_data);
            if ($buy_data !== true){
                DB::rollBack();
                return '订单处理失败！';//处理买家账户错误
            }
//            $buy_reward = $flowOBJ->listBuyReward($order_data);
//            if (!$buy_reward){
//                DB::rollBack();
//                return '处理买家奖励账户错误';
//            }
            $reward_ret = $flowOBJ->listReward($order_data);
            if ($reward_ret !== true){
                DB::rollBack();
                return $reward_ret;
                return '订单处理失败！';//处理分成奖励账户错误
            }
            $ret = $this->storage()->setPayCurrrency($order_data);
            if (!$ret){
                DB::rollBack();
                return '订单状态错误';
            }
            DB::commit();

            $content = array(
                'type'=>'user_nocite_1',
                'content' => '订单'.$order.' 卖家已支付币',    //通知内容
                'time' => time(),    //通知时间 -时间戳
            );
            event(new NoticeUser($content,'ORDER',$order_data->uid));

            return true;
        }catch (\Exception $exception){
            DB::rollBack();
            return '运行错误！';
        }
    }

//获取挂单ids的总币种
    public function sunOrderCurrency(array $orders)
    {
        return $this->storage()->sumOrderCurrencys($orders);
    }

    public function getOrderId($order)
    {
        $w = array(
            'order_id'=> $order
        );
        return $this->storage()::where($w)->first();
    }

    //买家申诉
    public function setBuyComplaint(TransactionOrder $order)
    {
        return $this->storage()->setBuyComplaint($order);
    }

    //卖家申诉
    public function setSellComplaint(TransactionOrder $order)
    {
        return $this->storage()->setSellComplaint($order);
    }


    //获取订单列表
    public function handGetOrderList($user, $type,$page,$num)
    {
        if ($user->identity == 2){
            $w = array(
                'uid' => $user->id,
            );
        }else{
            $w = array(
                'sell_uid' => $user->id,
            );
        }
        if ($type == 1){//交易中
            $status = array(1,2,5,6);
        }elseif ($type == 2){//取消
            $status = array(3,4);
        }elseif ($type == 3){
            $status = array(7);
        }elseif ($type == 4){
            $status = array(8,9);
        }else{
            return '参数错误！';
        }

        $data = $this->getOrderListData($w,$status,$page,$num);
        $ids = array();
        $ret = array();
        foreach ($data['data'] as $v){
            $one = array(
                'order_id' => $v->order_id,
                'get_num' => $v->get_num,
                'status' => $v->status,
                'buy_user' => array(
                    'id'  => $v->uid,
                ),
                'sell_user' => array(
                    'id' => $v->sell_uid,
                ),
                'created_at' => $v->created_at
            );
            $ids[] = $v->uid;
            $ids[] = $v->sell_uid;
            $ret[] = $one;
        }
        if (!$ret){
            return [];
        }
        $ids = array_unique($ids);
        $userOBJ = resolve(UserRepository::class);
        $user_data = $userOBJ->getUserIds($ids);
        $user_ret = array();
        foreach ($user_data as $v){
            $user_ret[$v->id]['name'] = $v->name;
            $user_ret[$v->id]['pic'] = $v->pic;
        }
        $userInfoOBJ = resolve(UserInfoRepository::class);
        $userInfo_data = $userInfoOBJ->getUserIds($ids);
        $userInfo_ret = array();
        $redisOBJ = resolve(RedisRepository::class);
        foreach ($userInfo_data as $v){
            $userInfo_ret[$v->uid]['grade'] = $v->grade;
            $userInfo_ret[$v->uid]['fraction'] = 12-($redisOBJ->getNum($v->uid));
        }
        foreach ($ret as $k=>$v){
            $ret[$k]['buy_user']['data'] = $user_ret[$v['buy_user']['id']];
            $ret[$k]['buy_user']['info'] = $userInfo_ret[$v['buy_user']['id']];
            $ret[$k]['sell_user']['data'] = $user_ret[$v['sell_user']['id']];
            $ret[$k]['sell_user']['info'] = $userInfo_ret[$v['sell_user']['id']];
        }
        $result = array();
        $result['data'] = $ret;
        $result['total'] = $data['total'];
       return $result;


    }

    public function getOrderListData($w,$status,$page,$num)
    {
        $offset = ($page-1)*$num;
        $data['data'] = $this->storage()->where($w)->whereIn('status', $status)->orderBy('created_at','desc')->offset($offset)->limit($num)->get();
        $data['total'] = $this->storage()->where($w)->whereIn('status', $status)->count();
        return $data;
    }

}