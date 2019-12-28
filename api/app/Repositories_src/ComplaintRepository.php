<?php
/**
* Created by PhpStorm.
* User: apple GeneratorCommand
* Date: 2019/09/23
* Time: 15:24:57
*/

namespace App\Repositories;
use App\Generator\Repositories\ComplaintRepositoryTrait;
use Faker\Factory;

class ComplaintRepository extends  Repository
{
    use ComplaintRepositoryTrait;

    public function handStore($uid, $type, $order, $content, $pic)
    {
        $data_ret = $this->getUidTypeOrder($uid, $type, $order);
        if ($data_ret){
            return '当前问题已反馈，请耐心等待！';
        }
        if ($type == 1){//挂单申诉
            $oredr_ret = $this->handTransaction($order, $uid);
        }elseif ($type == 2){
            $oredr_ret = $this->handTask($order, $uid);
        }else{
            return '类型错误！';
        }
        if ($oredr_ret !== true){
            return $oredr_ret;
        }

        $data = array(
            'complaint_id' => $this->getComplaint(),
            'uid' => $uid,
            'type' => $type,
            'order' => $order,
            'content' => $content,
            'pic' => json_encode($pic),
        );
        $ret = $this->storage()->create($data);
        if ($ret){
            return true;
        }else{
            return '系统忙，请稍后再试！';
        }

    }

    //修改挂单交易状态
    public function handTransaction($order, $uid)
    {
        $tranOBJ = resolve(TransactionOrderRepository::class);
        $order_data = $tranOBJ->getOrderId($order);
        if (!$order_data ){//|| $order_data->uid != $uid
            return '订单号错误！';
        }
        if (!in_array($order_data->status, array(5,6))){
            return '此交易无异常不能申诉！';
        }
        $list = $order_data->transaction;
        if ($order_data->uid == $uid){
            $staus_ret =  $tranOBJ->setBuyComplaint($order_data);//设置申诉状态
        } elseif ($list->uid == $uid){
            $staus_ret =   $tranOBJ->setSellComplaint($order_data);//设置申诉状态
        }else{
            return '当前用户无权申诉此单号！';
        }
        if (!$staus_ret){
            return '系统忙，请稍后再试！';
        }
        return true;
    }

    public function handTask($order, $uid){
        $taskOBJ =  resolve(TaskRepository::class);
        $task = $taskOBJ->getComplaintRed($uid, $order);
        if (!$task){
            return '订单错误！';
        }
       $ret =  $taskOBJ->setComplaintRed($uid, $order);
        if (!$ret){
            return '系统忙，请稍后再试';
        }
        return true;
    }


    public function getUidTypeOrder($uid, $type, $order)
    {
        $w = array(
            'uid' => $uid,
            'type' => $type,
            'order' => $order,
            'is_hand' => 0
        );
        return $this->storage()->where($w)->first();
    }

    //获取随机唯一id
    public function getComplaint()
    {
       $randOBJ =  Factory::create();
        do{
            $rand = $randOBJ->regexify('[0-9]{10}');
            $ret = $this->storage()->where('complaint_id', $rand)->first();
        }while($ret);
        return $rand;
    }


}