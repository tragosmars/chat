<?php
/**
* Created by PhpStorm.
* User: apple GeneratorCommand
* Date: 2019/09/17
* Time: 14:42:02
*/

namespace App\Repositories;
use App\Events\UserRegister;
use App\Generator\Repositories\UserRepositoryTrait;
use Faker\Factory;

class UserRepository extends  Repository
{
    use UserRepositoryTrait;

    public function add($data)
    {
        return $this->storage()::create($data);
    }

    public function getRandCode()
    {
        $rendobj = Factory::create();
        $ret = '';
        do{
            $rand = $rendobj->regexify('[0-9]{8}');
            $w = array(
                'shara_code' => $rand,
            );
            $ret = $this->storage()::where($w)->first();
        }while($ret);
        return $rand;
    }

    /*
     * 验证号码是否已经注册
     * */
    public function moblieExist($moble)
    {
        $w = array(
            'mobile' => $moble
        );
        return $this->storage()::where($w)->first();
    }

//    判断邀请码是否存在
    public function codeExist($code)
    {
        $w = array(
            'shara_code' => $code,
            'status' => 1
        );
        $ret = $this->storage()::where($w)->first();
        if (!$ret){
            return false;
        }
        $userInfo = resolve(UserInfoRepository::class);
        $check = $userInfo->checkRate($ret->id);
        if (!$check){
            return false;
        }
        $max = config('share.max');
        if ($ret->level >= $max){
            return false;
        }
        return $ret;
    }

    /*
     * 获取用户ids的数据
     * */
    public function getUserIds(array $ids)
    {
        return $this->storage()::whereIn('id',$ids)->get();
    }

    /*
     * 获取用户的下线
     * */
    public function getUserIdTeam($uid)
    {
        $w = array(
            'parent_id' => $uid,
            'status' => 1
        );
        return $this->storage()::with('userInfo')->where($w)->get();
    }

    /*
     * 获得指定id用户
     *
     * */
    public function getOne($uid)
    {
        return $this->storage()::find($uid);
    }

    //获取递归祖父id
    public function getParents($uid,$level,$data)
    {
        $user_data = $this->storage()::find($uid);


        if ($user_data->level === 0){
            return [];
        }elseif ($user_data->level == 1){
            $data[] = $user_data->id;
            return $data;
        }

        $data =  $this->getParents($user_data->parent_id,$user_data->level,$data);
       $data [] = $user_data->id;
        return $data;

    }


    public function handAddRedUser($data){
        $ret =  $this->storage()::create($data);
        $redOBJ = resolve(RedPacketRepository::class);
        $redOBJ->addUser($ret->id, rand(10000,999999),'http://123456.com');
        event(new UserRegister($ret, 1));
    }
}