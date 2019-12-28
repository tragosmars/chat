<?php
/**
* Created by PhpStorm.
* User: apple GeneratorCommand
* Date: 2019/09/19
* Time: 15:07:01
*/

namespace App\Repositories;
use App\Generator\Repositories\AccountRepositoryTrait;

class AccountRepository extends  Repository
{
    use AccountRepositoryTrait;

    /*
     * 增加注册用户账户信息
     *
     * */
    public function add($uid)
    {
        $obj = $this->storage();
        $obj->uid = $uid;
        return $obj->save();
    }

    //查询用户账户信息
    public function getDataInfo($uid)
    {
        $w = array(
            'uid' => $uid,
            'status' => 1
        );
        return $this->storage()::where($w)->first();
    }

    //查询指定用户账户可用金额
    public function checkAccount($uid)
    {
        $data = $this->getDataInfo($uid);
        return $data->flow;
    }

    //新增冻结金额
    public function addFrozen($uid, $num)
    {
        $data = $this->getDataInfo($uid);
        if (!$data || bccomp($data->flow, $num,2) < 0){
            return false;
        }
        $data->flow = bcsub($data->flow, $num, 2);
        $data->frozen = bcadd($data->frozen,$num, 2);
        $ret = $data->save();
        return $ret;
    }

    //新增冻结-》流动
    public function addFlow($uid, $num)
    {
        $data = $this->getDataInfo($uid);
        if (bccomp($data->frozen, $num,2) < 0){
            return false;
        }
        $data->flow = bcadd($data->flow, $num,2);
        $data->frozen = bcsub($data->frozen,$num,2);
        $ret = $data->save();
        return $ret;
    }

    public function handShow($user)
    {
        $w = array(
            'uid' => $user->id
        );
        $ret = $this->storage()->where($w)->first();
        return array(
            'total' => bcadd($ret->flow, $ret->frozen, 2),
            'flow' => $ret->flow,
            'frozen' => $ret->frozen,
            'reward' => $ret->reward,
        );
    }

    public function handIndex($user,$time, $type,$page, $num)
    {
        if ($time == 'w'){
            $select_time = strtotime('-1 week');
        }elseif ($time == 'm'){
            $select_time = strtotime('last Monday');
        }else{
            $select_time = strtotime('-1 day');
        }
        $flow_where = array(
            array('created_at','>', $select_time),
        );
        if ($type == 'add'){
            $flow_where['symbol'] = 1;
        }else if ($type == 'sub'){
            $flow_where['symbol'] = 2;
        }
        $w = array(
            'uid' => $user->id
        );
        $ret = $this->storage()->where($w)->first();
        $account = array(
            'total' => bcadd($ret->flow, $ret->frozen, 2),
            'flow' => $ret->flow,
            'frozen' => $ret->frozen,
            'reward' => $ret->reward,
        );

        $offset = ($page-1)*$num;
        $flows = $ret->flow()->where($flow_where)->offset($offset)->limit($num)->orderBy('created_at','desc')->get();
        $total = $ret->flow()->where($flow_where)->count();
        $data = array();
        foreach ($flows as $one){
            $list = array();
            $list['type'] = $one->symbol;
            $list['num'] = $one->num;
            $list['time'] = $one->created_at;
            $list['other'] = array(
                'source' =>'',
                'text' => '',
            );
            if($one->flow_type == 4){
                $list['other'] = array(
                    'source' => $one->transactions->user->name,
                    'text' => '分成',
                );
            }
            $data[] = $list;
        }
        $result = array(
            'account' => $account,
            'order' => array(
                'list' => $data,
                'total' => $total
            ),

        );
        return $result;
    }

}