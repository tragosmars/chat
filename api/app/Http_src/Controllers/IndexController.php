<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2019/3/19
 * Time: 17:17
 */

namespace App\Http\Controllers;


use App\Repositories\NoticeRepository;
use Egulias\EmailValidator\Validation\Error\RFCWarnings;
use Illuminate\Http\Request;
use Fruits\Apple\Verification\Mobile;
use Fruits\Apple\Verification\Email;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class IndexController extends  Controller
{

/*
 * 验证短信验证码是否正确
 * */
    public function mobileCode(Request $request)
    {
        $mobile = $request->input('mobile');
        $ocde = $request->input('code');
        $type = $request->input('type',1);
        if (!$mobile || !$ocde){
            return response()->apiReturn([],401,'参数错误');
        }
        try{
            if ($type == 1){
                $verification = new Mobile($mobile,Mobile::TYPE_REG);
            }else{
                $verification = new Mobile($mobile,Mobile::TYPE_RESET);
            }
            if(!$verification->check($ocde)){
                return response()->apiReturn([],401,'验证码错误');
            }
            return response()->apiReturn(true);
        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行错误');
        }
    }

    /*
     * 发送验证码
     * */
    public function send()
    {
        $uid = Auth::user()->id;
        try{
            $obj = resolve(NoticeRepository::class);
            $ret = $obj->sendCode($uid);
            return response()->apiReturn($ret);
        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行错误');
        }
    }


    /*
     * 上传支付二维码
     *
     * */
    public function upcode(Request $request)
    {
        $uid = Auth::user()->id;
        $file = 'code';
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
                return response()->apiReturn($path);
            } else {
                return response()->apiReturn([],401,'上传失败');
            }

        }catch (\Exception $exception){
            return response()->apiReturn([], 404, '运行失败');
        }
    }




}