<?php
/**
 * Created by PhpStorm.
 * User: apple GeneratorCommand
 * Date: 2019/09/17
 * Time: 14:42:02
 */
namespace App\Http\Controllers;

use App\Events\UserPy;
use App\Generator\Controllers\UserControllerTrait;
use App\Repositories\UserRepository;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Events\SendTalk;


class UserController extends Controller
{
    use RESTful,UserControllerTrait;

    public function resetPass(Request $request)
    {
        $uid = Auth::user()->id;
        $old = $request->input('old_pass');
        $new = $request->input('password');
        $new2 = $request->input('password_confirmation');
        if (!$old || !$new || !$new2){
            return response()->apiReturn([],401,'参数错误');
        }
        if ($new !== $new2){
            return response()->apiReturn([],401,'两次密码不一致');
        }
        try{
            if(Hash::check($old,Auth::user()->password)){
                $userOBJ = new User();
                $data = $userOBJ::find($uid);
                $data->password = Hash::make($new);
                $ret = $data->save();
                if ($ret){
                    return response()->apiReturn(true);
                }else{
                    return response()->apiReturn([],401,'修改失败');
                }
            }else{
                return response()->apiReturn([],401,'密码错误');
            };
        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行错误');
        }
    }

    public function resetName(Request $request)
    {
        $uid = Auth::user()->id;
        $name = $request->input('name');
        if(!$name){
            return response()->apiReturn([],401,'参数错误');
        }
        try{
            $userOBJ = new User();
            $w = array(
                'name' => $name
            );
            $nameData = $userOBJ::where($w)->first();
            if ($nameData){
                return response()->apiReturn([],401,'用户名已存在');
            }
            $data = $userOBJ::find($uid);
            $data->name = $name;
            $ret = $data->save();
            if ($ret){
                event(new UserPy($uid, $name));
                return response()->apiReturn(true);
            }else{
                return response()->apiReturn([],401,'修改失败');
            }
        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行错误');
        }
    }

    public function setPic(Request $request)
    {
        $uid = Auth::user()->id;
        $file = 'pic';
        try{
            $err =  Validator::make([$file => $request->file($file)],[
                $file=>'required|image',
            ],[
                $file.'.required' => '请上传图片',
                $file.'.image' => '请上传图片类型',
            ]);
            if ($err->fails()) {
                $errors = $err->errors();
                $errorData = $errors->first($file);
                return response()->apiReturn([],401,$errorData);
            }
            $path = $request->$file->store('images', $file);
            if ($path) {
                $userOBJ = new User();
                $data = $userOBJ::find($uid);
                $data->pic = $path;
                $ret = $data->save();
                if ($ret){
                    return response()->apiReturn($path);
                }else{
                    return response()->apiReturn([],401,'上传失败');
                }
            } else {
                return response()->apiReturn([],401,'上传失败');
            }

        }catch (\Exception $exception){
            return response()->apiReturn([], 404, '运行失败');
        }
    }

    //判断用户邀请码是否可以邀请人
    public function checkUserShara(Request $request)
    {
        $shara_code = Auth::user()->shara_code;//用户分享码
        try{
            if(!$shara_code){
                return response()->apiReturn([],401,'参数错误');
            }
            $userOBJ = resolve(UserRepository::class);
            $ret = $userOBJ->codeExist($shara_code);
            if($ret){
                $ret = true;
            }
            return response()->apiReturn($ret);
        }catch (\Exception $exception){
            return response()->apiReturn([], 404, '运行失败');
        }
    }


    public function checkUserSharaIsUse(Request $request)
    {
        $shara_code = $request->input('shara_code');//用户分享码
        try{
            if(!$shara_code){
                return response()->apiReturn([],401,'参数错误');
            }
            $userOBJ = resolve(UserRepository::class);
            $ret = $userOBJ->codeExist($shara_code);
            return response()->apiReturn($ret);
        }catch (\Exception $exception){
            return response()->apiReturn([], 404, '运行失败');
        }
    }


    public function firstRegister()
    {
        $user = Auth::user();
        $message = [
            'type'=>'group_add_user',
            'from_user_name'=>$user->name,
            'pic' => $user->pic,
            "content"=>'欢迎用户'.$user->name.'加入',
            'pinyin' =>$user->pinyin,
            "time"=>time(),
        ];
        event(new SendTalk($message,1));
    }


    //创建红包商户----后台
    public function addRedUser()
    {
        $data = array(
              'name' => '收购币商户11',
              'mobile' => '',
              'password' => rand(100000,999999999),
              'pic'  => '',
              'identity'  => '3',
              'status' => '',
              'shara_code' => '',
              'parent_id' => '0',
              'initial_id'  => '0',
              'level' => '0',
              'groupchat'  => 1,
        );
        $userOBJ = resolve(UserRepository::class);
        $ret = $userOBJ->handAddRedUser($data);
        return response()->apiReturn($ret);
    }

    public function pinyin()
    {
//        event(new UserPy(20,'张三wode1hh'));
    }

}
