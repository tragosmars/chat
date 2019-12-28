<?php
/**
* Created by PhpStorm.
* User: apple GeneratorCommand
* Date: 2019/09/24
* Time: 14:14:43
*/

namespace App\Repositories;
use App\Generator\Repositories\GroupNociteRepositoryTrait;
use App\Events\SendTalk;

class GroupNociteRepository extends  Repository
{
    use GroupNociteRepositoryTrait;

    public function handStore($uid, $content, $user)
    {
        $group = resolve(GroupchatRepository::class);
        $group_data = $group->getUserGroup($uid);
        if (!$group_data){
            return '数据错误!';
        }
        $data = array(
            'group_id' => $group_data->id,
            'uid' => $uid,
            'content' => $content,
            'read_num' => 0,
        );
        $ret = $this->storage()::create($data);
        if (!$ret){
            return '新增失败！';
        }

        $message = [
            'type'=>'group_add_notice',
            'from_user_name'=>$user->name,
            'pic' => $user->pic,
            "content"=>$content,
            "time"=>time(),
        ];

        event(new SendTalk($message, 1));
        return true;
    }

    public function handIndex($groupchat, $page, $num)
    {
        $data = $this->getData($groupchat, $page, $num);
        $ret = array();
        foreach ($data['data'] as $v){
            $one = array(
                'id' => $v->id,
                'user' => $v->user->name,
                'content' => $v->content,
                'read_num' => $v->read_num,
                'created_at' => $v->created_at,
                'updated_at' => $v->updated_at,
            );
            $ret[] = $one;
        }
        $result = array(
            'data' => $ret,
            'total' => $data['total']
        );
        return $result;

    }

    public function getData($groupchat, $page, $num)
    {
        $offset = ($page-1)*$num;
        $w = array(
            'group_id' => $groupchat
        );
        $data['data'] = $this->storage()::with('user')->where($w)->orderBy('created_at','desc')->offset($offset)->limit($num)->get();
        $data['total'] = $this->storage()->where($w)->count();
        return $data;
    }

    public function handShow($notice, $groupchat,$uid)
    {
       $data = $this->storage()::find($notice);
       if (!$data || $data->group_id != $groupchat){
            return '通知不存在';
       }
       $user = $data->user;
       $ret = array(
           'id' => $data->id,
           'user' => $user->name,
           'pic' => $user->pic,
           'content' => $data->content,
           'read_num' => $data->read_num,
           'created_at' => $data->created_at,
           'updated_at' => $data->updated_at,
       );
       $w = array(
           'uid' => $uid,
           'nocite_id' => $notice
       );
       $read = $data->groupReadNocite()->where($w)->first();
       if (!$read){
           $result =   $data->GroupReadNocite()->create($w);
           if ($result){
               $save = $data->increment('read_num');
           }
       }

       return $ret;
    }

    public function handDestroy($notice, $groupchat, $uid)
    {
        $data = $this->storage()::find($notice);
        if (!$data || $data->group_id != $groupchat){
            return '通知不存在';
        }
        $ret = $data->delete();
        if (!$ret){
            return '删除失败';
        }
        return true;
    }
    public function handUpdate($user,$id, $content)
    {
        $data = $this->storage()::find($id);
        if (!$data || $data->group_id != $user->groupchat){
            return '通知错误！';
        }
        $ret = $data->content = $content;
        $result = $data->save();
        if (!$result){
            return '系统错误';
        }
        return true;

    }

}