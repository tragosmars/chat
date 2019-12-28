<?php
/**
* Created by PhpStorm.
* User: apple GeneratorCommand
* Date: 2019/09/19
* Time: 20:47:30
*/

namespace App\Repositories;
use App\Generator\Repositories\TransactionRepositoryTrait;
use Faker\Factory;
use Illuminate\Support\Facades\DB;

class TransactionRepository extends  Repository
{
    use TransactionRepositoryTrait;

        public function handStore($uid, $total, $max, $min, $payType, $remarks)
        {
            $checkNum = $this->checkNum($total, $max, $min);
            if ($checkNum !== true){
                return $checkNum;
            }
            $accObj = resolve(AccountRepository::class);
            $acc = $accObj->checkAccount($uid);
            if(bccomp($total, $acc, 2) > 0){
                return '账户金额不足';
            }
            $pay_type = json_decode($payType, true);
            $pay_ids  = array_unique(array_keys($pay_type));
            $payObj = resolve(PayRepository::class);
            $check_pay = $payObj->getPayIds($pay_ids, $uid)->toArray();
            if (!$check_pay){
                return '支付方式错误';
            }
            if (count($pay_ids) != count($check_pay)){
                return '支付选择错误错误';
            }
            $pay = array();
            $pay_data = array();
            foreach ($check_pay as $v){
                if (!in_array($v['payId'], $pay)){
                    $pay[] = $v['payId'];
                }
                $pay_data[$v['id']] = $v['payId'];
            }
            DB::beginTransaction();
            try{
                $add = $this->storage();
                $add->order_id = $this->getRandOrder();
                $add->uid = $uid;
                $add->total = $total;
                $add->surplus = $total;
                $add->min = $min;
                $add->max = $max;
                $add->price = 1.00;
                $add->remarks = $remarks;
                $add->pay_type = array_sum($pay);
                $add->pay_list = json_encode($pay_data);
                $add->term_of_validity = time()+config('share.term_of_validity');
                $ret = $add->save();
                if (!$ret){
                    DB::rollBack();
                    return '系统忙,请稍后再试';
                }
                $result = $accObj->addFrozen($uid, $total);
                if (!$result){
                    DB::rollBack();
                    return '系统忙';
                }
                DB::commit();
                return true;
            }catch (\Exception $exception){
                DB::rollBack();
                return '运行失败';
            }


        }

        public function checkNum($total, $max, $min)
        {
            if (bccomp($max, $min,2) < 0){
                return '最大值需要大于等于最小值';
            }
            if (bccomp($total, $min,2) < 0){
                return '最小值需要小于总数';
            }
            if (!preg_match("/^[0-9]{1,}$/",$total) || $total <= 0){
                return '总数需要大于0的整数';
            }
            if (!preg_match("/^[0-9]{1,}$/",$min) || $min<=0){
                return '最小值需要大于0的整数';
            }
            if (!preg_match("/^[0-9]{1,}$/",$max) || $max<=0){
                return '最大值需要大于的整数';
            }
            return true;

        }

        //获取随机id
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

        public function handIndex($page, $num)
        {
            $data = $this->getData($page,$num);
            if (!$data['data']){
                return array(
                    'data' => [],
                    'total' => 0
                );
            }
            $payObj = resolve(PayRepository::class);
            $result = array();
            $uids = array();
            foreach ($data['data'] as $v){
                $one = array(
                    'order_id' => $v->order_id,
                    'surplus' => $v->surplus,
                    'min' => $v->min,
                    'max' => $v->max,
                    'price' => $v->price,
                    'remarks' => $v->remarks,
                    'created_at' => $v->created_at,
                    'pay' => $payObj->getPayBin($v->pay_type),
                    'user' => $v->uid,
                    'sum' => $this->getUidSun($v->uid),//可优
                );
                $result[] = $one;
                $uids[] = $v->uid;
            }
            $uids = array_unique($uids);
            $userOBJ = resolve(UserRepository::class);
            $user_data = $userOBJ->getUserIds($uids);
            $user = array();
            foreach($user_data as $v) {
                $user[$v->id]['name'] = $v->name;
                $user[$v->id]['pic'] = $v->pic;
                $user[$v->id]['id'] = $v->id;
            }
            foreach ($result as $k => $v){
                $result[$k]['user'] = $user[$v['user']];
            }
            return array(
                'data' => $result,
                'total' => $data['total']
            );

        }

        //获取数据
        public function getData($page, $num)
        {
            $offset = ($page-1)*$num;
            $w = array(
                'status' => 1,
                array(
                    'term_of_validity',
                    '>',
                    time(),
                ),
            );
            $data = $this->storage()::where($w)->offset($offset)->limit($num)->orderBy('created_at','desc')->get();
//            $total_where = array(
//                'status' => 1,
//            );
            $count = $this->storage()::where($w)->count();
            return array(
                'data' => $data,
                'total' => $count
            );
        }

        public function handDestroy($uid, $order_id)
        {
            $w = array(
                'order_id' => $order_id,
                'uid' => $uid,
                'status' => 1,
                array(
                    'term_of_validity',
                    '>',
                    time(),
                )
            );
            $data = $this->storage()::where($w)->first();
            if (!$data){
                return '订单错误';
            }
            return $this->setOrderExpire($data,$uid);


        }
        //修改过去状态
    public function setExpire($id)
    {
       return $this->storage()->cancel($id);
    }


    //查询过期订单
    public function getExpire()
    {
        $w = array(
            array(
                'term_of_validity',
                '<',
                time(),
            ),
            'status' => 1,
        );
        return $this->storage()::where($w)->get();
    }


    /*
     * 获取指定有效订单
     *
     * */
    public function getEff($order)
    {
        $w = array(
            'order_id' => $order,
            'status' => 1,
            array(
                'term_of_validity',
                '>',
                time(),
            )
        );
        return $this->storage()::where($w)->first();
    }

    //订单过期
   public function setOrderExpire($dataOBJ,$uid)
   {
       DB::beginTransaction();
       try{
           //$ret = $this->storage()->cancel($data->id);
           $ret = $this->setExpire($dataOBJ->id);
           if ($ret){
               $accOBJ = resolve(AccountRepository::class);
               $flow = $accOBJ->addFlow($uid, $dataOBJ->surplus);
               if ($flow){
                   DB::commit();
                   return true;
               }else{
                   DB::rollBack();
                   return '账户计算错误';
               }
           }
       }catch (\Exception $exception){
           DB::rollBack();
           return '系统运行错误';
       }
   }

    //获取指定用户出售总金额
    public function getUidSun($uid)
    {
        $w = array(
            'uid' => $uid,
        );
        $orders = $this->storage()::where($w)->pluck('order_id')->toArray();
        $orderObj = resolve(TransactionOrderRepository::class);
        return $orderObj->sunOrderCurrency($orders);
    }


}