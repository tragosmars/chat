<?php
/**
* Created by PhpStorm.
* User: apple GeneratorCommand
* Date: 2019/09/18
* Time: 14:45:12
*/

namespace App\Repositories;
use App\Generator\Repositories\PayRepositoryTrait;
use App\Storage\Database\UserInfo;

class PayRepository extends  Repository
{
    protected  $bankCount = 5;
    use PayRepositoryTrait;

    /*
     * 新增支付方式
     * $uid 用户id
     * $name 用户名
     * $account 账户信息
     *  $pay_id 支付id
     *  $pay_pic 支付二维码
     *  $other 额外信息
     * */
        public function handStore($uid, $name, $account, $pay_id, $pay_pic, $other)
        {
            $payList = config('pay.list');
            if (!isset($payList[$pay_id])){
                return '支付方式不存在！';
            }
            if (!$payList[$pay_id]['is_more']){
                $w = array(
                    'pay_id' => $pay_id,
                    'uid' => $uid
                );
                $ret = $this->storage()::where($w)->first();
                if ($ret){
                    return '支付方式已存在！';
                }
            } else {//可以多张
                $count_where = array(
                    'uid' => $uid,
                    'pay_id' => $pay_id,
                );
                $count = $this->storage()::where($count_where)->count();
                if ($count >= $this->bankCount ){
                    return '添加上限！';
                }
                $ws = array(
                    'pay_id' => $pay_id,
                    'account' => $account,
                    'uid'=>$uid,
                );
                $ret = $this->storage()::where($ws)->first();
                if ($ret){
                    return '支付方式已存在！';
                }
            }
            if ($payList[$pay_id]['is_list']){
//                dd($other['bankName']);
                if (!in_array($other['bankName'], $payList[$pay_id]['list'])){
                    return '请选择正确的银行名称！';
                }
            }


            $pic = $pay_pic?$pay_pic:'';
            if ($payList[$pay_id]['is_code']){
                if (!$pay_pic){
                    return '请上传收款二维码！';
                }
            }
            $data = array(
                'uid' => $uid,
                'pay_id' => $pay_id,
                'name' =>  $name,
                'account' => $account,
                'pay_pic' => $pic,
                'other' => json_encode($other,JSON_UNESCAPED_UNICODE),
            );
            $ret = $this->storage()::create($data);
            if (!$ret){
                return '添加失败！';
            }
            return true;
        }

        public function handIndex($uid)
        {
            $payList = config('pay.list');
            $w = array(
                'uid' => $uid
            );
            $data = $this->storage()::where($w)->get();
            $result = array();
            foreach ($data as $v){
                if ($payList[$v->pay_id]['is_list']){
                    $name = json_decode($v->other, true)['bankName'];
                }else{
                    $name = $payList[$v->pay_id]['name'];
                }
                $one = array(
                    'id' => $v->id,
                    'name' => $name,
                    'account' => $v->account,
                    'pic' => $payList[$v->pay_id]['pic'],
                    'pay_id' => $v->pay_id,
                );
                $result[] = $one;
            }
            return $result;

        }

        public function handShow($uid, $id)
        {
            $w = array(
                'uid' => $uid,
                'id' => $id
            );
            $data = $this->storage()::where($w)->first();
            $data->other = json_decode($data->other, true);
            return $data;
        }

        public function handDestroy($uid, $id){
            $w = array(
                'uid' => $uid,
                'id' => $id
            );
            $data = $this->storage()::where($w)->first();
            if ($data){
                $ret = $data->delete();
                if (!$ret){
                    return '删除失败';
                }
                return true;
            }else{
                return '支付不存在';
            }
        }

        //查询指定ids的支付方式
        public function getPayIds(array $ids, $uid)
        {
            return $this->storage()::whereIn('id', $ids)->where('uid', $uid)->get();
        }

        //返回支付方式名称，图标
        public function getPayBin(int $num)
        {
            $data = array();
            for ($i = 1,$k = 1; $k <= $num;){
                if (($k & $num) > 0){
                    $data[] = $k;
                }
                $k = pow(2,$i);
                $i++;
            }
            $pay_data = config('pay.list');
            $ret = array();
            foreach ($data as $v){
                $ret[$v]['name'] = $pay_data[$v]['name'];
                $ret[$v]['pic'] = $pay_data[$v]['pic'];
                $ret[$v]['id'] = $pay_data[$v]['id'];
            }
            return $ret;
        }

        //获取用户支付信息
        public function getPayInfo(array $ids,$uid){
            $data = $this->getPayIds($ids,$uid);
            $ret = array();
            foreach ($data as $v){
                $one = array(
                    'name' => $v->name,
                    'account' => $v->account,
                    'pay_pic' => $v->pay_pic,
                    'other' => json_decode($v->other,true),
                );
                $ret[] =$one;
            }
            return $ret;
        }
}