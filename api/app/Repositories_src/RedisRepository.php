<?php
/**
 * Created by PhpStorm.
 * User: fumeibing
 * Date: 2019/9/21
 * Time: 14:36
 */

namespace App\Repositories;


use Illuminate\Support\Facades\Redis;

class RedisRepository
{

    protected  $key = '';
    public function __construct()
    {
        $key = date('Y-m-d').'_user_cancel';
        $this->key = $key;
    }

    public function setUserCancelNum($uid)
    {
        $num = $this->getNum($uid);
        $new_num = ++$num;
        return Redis::ZADD($this->key,$new_num, $uid);

    }

    public function getNum($uid)
    {
        ;
        $ret = Redis::ZSCORE($this->key,$uid);
        if (!$ret){
            return 0;
        }
        return (int)$ret;
    }

    public function delKey()
    {
        $key = date('Y-m-d',strtotime('-1 day')).'_user_cancel';
        return Redis::del($key);
    }
}