<?php
/**
 * Created by PhpStorm.
 * User: apple GeneratorCommand
 * Date: 2019/09/24
 * Time: 14:14:43
 */
namespace App\Http\Controllers;

use App\Generator\Controllers\GroupNociteControllerTrait;
use App\Repositories\GroupNociteRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupNociteController extends Controller
{
    use RESTful,GroupNociteControllerTrait;

    public function getOBJ()
    {
        return resolve(GroupNociteRepository::class);
    }

    public function store(Request $request)
    {
        $uid = Auth::user()->id;
        $user = Auth::user();
        $content = $request->input('content');
        if (!$content || mb_strlen($content)>600){
            return response()->apiReturn([],401,'参数错误');
        }
        try{
            $OBJ = $this->getOBJ();
            $ret = $OBJ->handStore($uid, $content, $user);
            if ($ret !== true){
                return response()->apiReturn([],401,$ret);
            }
            return response()->apiReturn($ret);

        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行错误');
        }
    }

    public function index(Request $request)
    {
        $groupchat = Auth::user()->groupchat;
        $page = $request->input('page', 1)?$request->input('page', 1):1;
        $num = $request->input('num', 10)?$request->input('num', 10):10;
        try{
            $OBJ = $this->getOBJ();
            $ret = $OBJ->handIndex($groupchat, $page, $num);
            return response()->apiReturn($ret);
        }catch (\Exception $exception){

            return response()->apiReturn([],404,'运行失败');
        }
    }


    public function show(Request $request)
    {
        $groupchat = Auth::user()->groupchat;
        $uid =  Auth::user()->id;
        $notice = $request->input('notice_id');
        try{
            $OBJ = $this->getOBJ();
            $ret = $OBJ->handShow($notice, $groupchat, $uid);
            if (!is_array($ret)){
                return response()->apiReturn([],401,$ret);
            }
            return response()->apiReturn($ret);
        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行失败');
        }
    }


    public function destroy(Request $request)
    {
        $groupchat = Auth::user()->groupchat;
        $uid =  Auth::user()->id;
        $notice = $request->input('notice_id');
        try{
            $OBJ = $this->getOBJ();
            $ret = $OBJ->handDestroy($notice, $groupchat, $uid);
            if ($ret !== true){
                return response()->apiReturn([],401,$ret);
            }
            return response()->apiReturn($ret);
        }catch (\Exception $exception){
            return response()->apiReturn([],404,$exception->getMessage());
        }
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $content = $request->input('content');
        $id = $request->input('notice_id');
        try{
            $OBJ = $this->getOBJ();

            if (!$content || mb_strlen($content)>600){
                return response()->apiReturn([],401,'参数错误');
            }
            $ret = $OBJ->handUpdate($user,$id, $content);
            if ($ret !== true){
                return response()->apiReturn([],401,$ret);
            }
            return response()->apiReturn($ret);
        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行错误');
        }
    }

}
