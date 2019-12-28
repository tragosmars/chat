<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StaticController extends Controller
{
    //获取服务协议
    public function getServiceProtocol()
    {
        try{
            return response()->apiReturn(config('static.list.service.content'));
        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行失败');
        }
    }

    //获得隐私政策
    public function getYs()
    {
        try{
            return response()->apiReturn(config('static.list.ys.content'));
        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行失败');
        }
    }

    //获得举报须知
    public function getJbxz()
    {
        try{
            return response()->apiReturn(config('static.list.jbxz.content'));
        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行失败');
        }
    }
}
