<?php
/**
* Created by PhpStorm.
* User: apple GeneratorCommand
* Date: 2019/09/18
* Time: 17:23:08
*/

namespace App\Repositories;
use App\Generator\Repositories\UserInfoRepositoryTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserInfoRepository extends  Repository
{
    use UserInfoRepositoryTrait;

    public function handIsReadName($uid)
    {
        $w = array(
            'uid' => $uid
        );
        $data = $this->storage()::where($w)->first();
        if (!$data || !$data->name){
            return false;
        }
        return true;
    }


//    验证是否设置交易密码
    public function handPasswordExist($uid)
    {
        $data  = $this->storage()->getOne($uid);
        if (!$data || !$data->jy_password){
            return false;
        }
        return true;
    }

    //设置交易密码
    public function handSetPassWord($uid, $password)
    {
        $data  = $this->storage()->getOne($uid);
        if ($data && $data->jy_password){
            return '密码重复设置！';
        }
        $obj = $this->storage();
        $obj->uid = $uid;
        $obj->jy_password = Hash::make($password);
        $ret = $obj->save();
        if (!$ret){
            return '密码设置失败！';
        }
        return true;
    }

    //验证交易密码
    public function handVerificationPassword($uid, $password)
    {
        $data  = $this->storage()->getOne($uid);
        if (!$data || !$data->jy_password || !Hash::check($password,$data->jy_password)){
            return false;
        }
        return true;
    }

//修改密码
    public function handResetPass($uid, $password)
    {
        $data  = $this->storage()->getOne($uid);
        if (!$data){
//            return '用户不存在！';
            $obj = $this->storage();
            $obj->uid = $uid;
            $obj->jy_password = Hash::make($password);
            $ret = $obj->save();
            if (!$ret){
                return '密码设置失败！';
            }
            return true;
        }
        $data->jy_password = Hash::make($password);
        $ret = $data->save();
        if (!$ret){
            return '修改失败！';
        }
        return true;
    }

    public function handAddReal($uid,$type, $name, $card, $mobile, $account, $bankName,$identity)
    {
        $data  = $this->storage()->getOne($uid);
        if ($data && $data->name){
            return '已实名';
        }
        $ws= array(
            'name' => $name,
            'card' => $card
        );
        $reReal = $this->storage()::where($ws)->first();
        if ($reReal){
            return '此身份信息已使用';
        }
        $realOBJ = resolve(RealNameRepositories::class);
        if ($type == 1){
            $realRet = $realOBJ->mainland($name, $card);
        }else {
            $realRet = $realOBJ->other($name, $card);
        }
        if (!$realRet){
            return '实名认证错误';
        }
        $payOBJ = resolve(PayRepository::class);
        $pay_id = config('pay.default');
        $pay_pic = '';
        $other = array(
            'pid' => '',
            'bankName' => $bankName,
        );
        DB::beginTransaction();
        try{
            $payRet = $payOBJ->handStore($uid, $name, $account, $pay_id, $pay_pic, $other);
            if ($payRet !== true){
                DB::rollBack();
                return '支付方式错误';
            }
            if (!$data){
                $data = $this->storage();
            }
            $data->type = $type;
            $data->name = $name;
            $data->card = $card;
            $data->mobile = $mobile;
            if ($identity == 1){
                $rate = config('share.rate');
                $data->rate = $rate;
                $data->is_set_rate = 1;
            }
            $ret = $data->save();
            if (!$ret){
                DB::rollBack();
                return '系统忙，请稍后再试';
            }
            DB::commit();
            return true;
        }catch (\Exception $exception){
            DB::rollBack();
            return '系统忙，请稍后再试';
        }


    }

    //判断用户是否被设置费率
    public function checkRate($uid)
    {
        $data  = $this->storage()->getOne($uid);
        if (!$data ){//|| $data->is_set_rate != 1
            return false;
        }else{
            return true;
        }
    }


    /*
     * 展示用户下线费率
     * */
    public function handShowRate($uid, $identity,$current_user)
    {
        $ret = array();
        $userOBJ = resolve(UserRepository::class);
        $user = $userOBJ->getUserIdTeam($uid);
        $ids = array();
        $user_data = array();
        foreach ($user as $v){
            $ids[] = $v->id;
            $user_data['team'][$v->id] = array(
                'user' => array(
                    'name' => $v->name,
                    'pic' => $v->pic,
                    'id' => $v->id,
                    'identity' => $v->identity,
                    'real' => isset($v->userInfo->name)?1:0,
                    'real_name' => $v->userInfo&&$v->userInfo->name?1:0,
                ),
                'rate' => array(
                    'uid' => $v->id,
                    'is_set_rate' => 0,
                    'rate' => 0,
                ),
            );
        }
        $user_data['my'][$uid] = array(
            'user' => $current_user,
            'rate' => array(),
        );
        $ids[] = $uid;
        $udata = $this->getUserIds($ids);
        foreach ($udata as $vs){
            $one = array(
                'uid' => $vs->uid,
                'is_set_rate' => $vs->is_set_rate,
                'rate' => $vs->rate,
            );
            if ($vs->uid == $uid){
//                $ret['my'] = $user_data[$vs->uid]['rate'] = $one;
                $user_data['my'][$vs->uid]['rate'] = $one;
            }else{
//                $ret['team'][] = $user_data[$vs->uid]['rate'] = $one;
                $user_data['team'][$vs->uid]['rate'] = $one;
            }

        }

        return $user_data;


    }

    /*
     * 获取用户id的数据
     * */
    public function getUserIds(array $ids)
    {
        return $this->storage()->whereIn('uid', $ids)->get();
    }

    public function handSetRate($uid, $user_id, $rate)
    {
        $my_data = $this->storage()->getOne($uid);
        if (!$my_data || $my_data->is_set_rate != 1){
            return '用户无权设置费率!';
        }
        $data  = $this->storage()->getOne($user_id);
        if (!$data || !$data->name || $data->is_set_rate == 1){
            return '当前用户不能设置费率！';
        }
        $userOBJ = resolve(UserRepository::class);
        $user_data = $userOBJ->getOne($user_id);
        if (!$user_data || $user_data->parent_id != $uid){
            return '用户不存在！';
        }
        if (bccomp(0,$rate,1) > 0 || bccomp(bcmul($my_data->rate,100,1),$rate,3) < 0){
            return '费率错误！';
        }
        $user_rate = bcdiv($rate,100,3);
        $data->rate = $user_rate;
        $data->is_set_rate = 1;
        $Ret = $data->save();
        return $Ret;
    }

    public function getOne($uid)
    {
        $w = array(
            'uid' => $uid
        );
        return $this->storage()::where($w)->first();
    }


    public function getIdsRate(array $ids)
    {
        return $this->storage()::whereIn('uid',$ids)->pluck('rate','uid');
    }

    public function handGetUserInfo($uid)
    {
        $data = $this->getOne($uid);
        if (!$data){
            $ret = array(
                'grade' => 1,
                'fraction' => 12,
            );
        }else{
            $redisOBJ = resolve(RedisRepository::class);
            $ret = array(
                'grade' => $data->grade,
                'fraction' => 12-($redisOBJ->getNum($uid)),
            );
        }
        return $ret;

    }

    //返回等级
    public function returnGrade($num)
    {


    }

}