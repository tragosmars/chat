<?php
/**
 * Created by PhpStorm.
 * User: apple GeneratorCommand
 * Date: 2019/09/24
 * Time: 10:29:27
 */
namespace App\Http\Controllers;

use App\Generator\Controllers\GroupchatControllerTrait;
use App\Repositories\GroupchatRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupchatController extends Controller
{
    use RESTful,GroupchatControllerTrait;

    public function getOBJ()
    {
        return resolve(GroupchatRepository::class);
    }

    public function setGroupName(Request $request)
    {
        $uid = Auth::user()->id;
        $group_id = Auth::user()->groupchat;
        $name = $request->input('name');
        if (!$name || !$group_id){
            return response()->apiReturn([],401,'参数错误');
        }
        try{
            $obj = $this->getOBJ();
            $ret = $obj->handSetGroupName($uid, $name, $group_id);
            if ($ret !== true){
                return response()->apiReturn([],401,$ret);
            }
            return response()->apiReturn($ret);
        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行错误');
        }
    }

    public function getInfo(Request $request)
    {
        $group_id = Auth::user()->groupchat;

//        $arr = array('小明','小花','哎','a哎');
//        $arr1 = sort($arr);
//        dd($arr);

        try{
            $obj = $this->getOBJ();
            $ret = $obj->handGetInfo($group_id);
            return response()->apiReturn($ret);
        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行错误');
        }

    }

    //获取今日排行
    public function getDayOrderBy()
    {
        $user = Auth::user();
        try{
            $ret = resolve($this->repositoryClass)->handGetDayOrderBy($user);
            return response()->apiReturn($ret);
        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行错误');
        }
    }

    //获取战队历史排行
    public function getAllOrderBy()
    {
        $user = Auth::user();
        try{
            $ret = resolve($this->repositoryClass)->handAllDayOrderBy($user);
            return response()->apiReturn($ret);
        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行失败');
        }
    }

    //获取战队成员
    public function getUser()
    {
        $user = Auth::user();
        try{
            $result = resolve($this->repositoryClass)->handGetUser($user);
            return response()->apiReturn($result);
        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行失败');
        }
    }



}
