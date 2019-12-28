<?php
/**
 * Created by PhpStorm.
 * User: apple GeneratorCommand
 * Date: 2019/09/18
 * Time: 17:23:08
 */
namespace App\Http\Controllers;

use App\Generator\Controllers\UserInfoControllerTrait;
use App\Repositories\UserInfoRepository;
use App\Storage\Database\UserInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class UserInfoController extends Controller
{
    use RESTful,UserInfoControllerTrait;

    //判断是否实名制
    public function isReadName()
    {
        $uid = Auth::user()->id;
        try{
            $obj = resolve(UserInfoRepository::class);
            $ret = $obj->handIsReadName($uid);
            return response()->apiReturn($ret);
        }catch (\Exception $exception){
            return response()->apiReturn([], 404, '运行失败');
        }
    }

    public function passwordExist()
    {
        $uid = Auth::user()->id;
        try{
            $obj = resolve(UserInfoRepository::class);
            $ret = $obj->handPasswordExist($uid);
            return response()->apiReturn($ret);
        }catch (\Exception $exception){
            return response()->apiReturn([], 404, '运行失败');
        }
    }
//设置交易密码
    public function setPassWord(Request $request)
    {
        $uid = Auth::user()->id;
        $password = $request->input('password');
        $password_confirmation = $request->input('password_confirmation');
        if(!preg_match("/^[0-9]{6}$/",$password)){
            return response()->apiReturn([], 401, '密码类型错误！');
        }
        if($password != $password_confirmation){
            return response()->apiReturn([], 401, '两次密码不相同！');
        }
        try{
            $obj = resolve(UserInfoRepository::class);
            $ret = $obj->handSetPassWord($uid, $password);
            if ($ret === true){
                return response()->apiReturn($ret);
            }else{
                return response()->apiReturn([], 401, $ret);
            }

        }catch (\Exception $exception){
            return response()->apiReturn([], 404, '运行失败');
        }
    }
//判断交易密码是否正确
    public function verificationPassword(Request $request)
    {
        $uid = Auth::user()->id;
        $password = $request->input('password');
        if (!$password){
            return response()->apiReturn([], 401, '参数错误！');
        }
        try{
            $obj = resolve(UserInfoRepository::class);
            $ret = $obj->handVerificationPassword($uid, $password);
            return response()->apiReturn($ret);
        }catch (\Exception $exception){
            return response()->apiReturn([], 404, '运行失败');
        }
    }

    //修改用户交易密码
    public function resetJyPassword(Request $request)
    {
        $uid = Auth::user()->id;
        $password = $request->input('password');
        $password_confirmation = $request->input('password_confirmation');
        if(!preg_match("/^[0-9]{6}$/",$password)){
            return response()->apiReturn([], 401, '密码类型错误！');
        }
        if($password != $password_confirmation){
            return response()->apiReturn([], 401, '两次密码不相同！');
        }

        try{
            $obj = resolve(UserInfoRepository::class);
            $ret = $obj->handResetPass($uid, $password);
            if ($ret === true){
                return response()->apiReturn($ret);
            }else{
                return response()->apiReturn([], 401, $ret);
            }
        }catch (\Exception $exception){
            return response()->apiReturn([], 404, '运行失败');
        }
    }

    //实名制
    public function addReal(Request $request)
    {
        $uid = Auth::user()->id;
        $identity = Auth::user()->identity;
        $type = $request->input('type',1);
        $name = $request->input('name');
        $card = $request->input('card');
        $mobile = $request->input('mobile','')?$request->input('mobile',''):'';
        $account = $request->input('account');
        $bankName = $request->input('bank_name');
        if (!$name || !$card || !$account || !$bankName){
            return response()->apiReturn([], 401, '参数错误');
        }
        try{
            $obj = resolve(UserInfoRepository::class);
            $ret = $obj->handAddReal($uid,$type, $name, $card, $mobile, $account, $bankName, $identity);
            if ($ret === true){
                return response()->apiReturn($ret);
            }else{
                return response()->apiReturn([], 401, $ret);
            }
        }catch (\Exception $exception){
            return response()->apiReturn([], 404, '运行失败');
        }
    }

    //费率展示
    public function showRate()
    {
        $uid = Auth::user()->id;
        $identity = Auth::user()->identity;
        $user = array(
            'name' => Auth::user()->name,
            'pic' => Auth::user()->pic,
            'id' => $uid,
            'identity' => $identity
        );
        try{
            $obj = resolve(UserInfoRepository::class);
            $ret = $obj->handShowRate($uid, $identity,$user);
            return response()->apiReturn($ret);
        }catch (\Exception $exception){
            return response()->apiReturn([], 404, '运行失败');
        }
    }

    //设置用户费率
    public function setRate(Request $request)
    {
        $uid = Auth::user()->id;
        $user_id = $request->input('uid');
        $rate = $request->input('rate');
        if (!$user_id || !$rate){
            return response()->apiReturn([], 401, '参数错误');
        }
        try{
            $obj = resolve(UserInfoRepository::class);
            $ret = $obj->handSetRate($uid, $user_id, $rate);
            return response()->apiReturn($ret);
        }catch (\Exception $exception){
            return response()->apiReturn([], 404, '运行失败');
        }
    }

    //获取用户的其他信息
    public function getUserInfo(Request $request)
    {
        $uid = Auth::user()->id;
        try{
            $obj = resolve(UserInfoRepository::class);
            $ret = $obj->handGetUserInfo($uid);
            return response()->apiReturn($ret);
        }catch (\Exception $exception){
            return response()->apiReturn([], 404, '运行失败');
        }
    }

    /*
     * 获取用户费率
     *
     * */
    public function getUserRate(Request $request)
    {
        $id = Auth::id();
        $mode = resolve(UserInfo::class)->where('uid', $id)->first();
        if (!$mode || $mode->is_set_rate === 0){
            $rate = 0;
        }else{
            $rate = $mode->rate;
        }
        return  response()->apiReturn($rate);
    }

}
