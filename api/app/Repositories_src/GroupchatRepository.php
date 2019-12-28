<?php
/**
* Created by PhpStorm.
* User: apple GeneratorCommand
* Date: 2019/09/24
* Time: 10:29:27
*/

namespace App\Repositories;
use App\Generator\Repositories\GroupchatRepositoryTrait;

class GroupchatRepository extends  Repository
{
    use GroupchatRepositoryTrait;

    //创建群聊
    public function add(array $data)
    {
        return $this->storage()::create($data);
    }

    public function handSetGroupName($uid, $name, $group_id)
    {
        $data = $this->storage()::find($group_id);
        if (!$data){
            return '战队错误';
        }
        if ($data->master != $uid){
            return '当前用户无权设置此战队名字！';
        }
        $data->name = $name;
        $ret = $data->save();
        if (!$ret){
            return '系统忙，请稍后再试！';
        }
        return true;
    }

    public function handGetInfo($id)
    {
        return $this->storage()::find($id);
    }

    //获取指定用户的群id
    public function getUserGroup($uid)
    {
        $w = array(
            'master' => $uid
        );
        return $this->storage()->where($w)->first();
    }


    public function handGetDayOrderBy($user)
    {
        //默认一个群
        $flowOBJ = resolve(FlowRepository::class);
        $data = $flowOBJ->storage()->getDay();
        $uids = array();
        $ret = array();
        foreach ($data as $v){
            $one = array(
                'user_name' => '',
                'user_pic' => '',
                'user_id' => $v->uid,
                'user_total' => $v->total

            );
            $ret[] = $one;
            $uids[] = $v->uid;
        }
        if (!$ret){
            return $ret;
        }
        $user_data = resolve(UserRepository::class)->storage()->getUids($uids);
        $user_ret = array();
        foreach ($user_data as $k=>$v){
            $user_ret[$v->id] = array(
                'name' => $v->name,
                'pic' => $v->pic
            );
        }
        foreach ($ret as $k=>$v) {
            $ret[$k]['user_name'] = $user_ret[$v['user_id']]['name'];
            $ret[$k]['user_pic'] = $user_ret[$v['user_id']]['pic'];
        }
        return $ret;
    }

    public function handAllDayOrderBy($user)
    {
        $data = resolve(AccountRepository::class)->storage()->getTaskTotalOrderBy();
        $ret = array();
        $total = 0;
        foreach ($data as $v){
            $one = array(
                'user_name' => $v->user->name,
                'user_pic' => $v->user->pic,
                'user_id' => $v->uid,
                'user_total' => $v->task
            );
            $ret[] = $one;
            $total = bcadd($v->task,$total,2);
        }
        $result = array(
            'total' => $total,
            'list' => $ret
        );
        return $result;
    }

    public function handGetUser($user)
    {
        $w = array(
            'groupchat' => $user->groupchat,
            array('name','<>',''),
            'status' => 1,
        );
        $data = resolve(UserRepository::class)->storage()->where($w)->whereIn('identity',array(1,2))->orderBy('pinyin','asc')->get();
        $result = array();
        foreach ($data as $v){
            $one = array(
                'id' => $v->id,
                'pic' => $v->pic,
                'name' => $v->name,
            );
            if ($v->identity == 1){
                $result['master'] = $one;
            }else{
                if (is_numeric($v->pinyin[0])){
                    $result['#'][] = $one;
                }else{
                    $result[ucfirst($v->pinyin[0])][] = $one;
                }
            }

        }
        return $result;
    }



}