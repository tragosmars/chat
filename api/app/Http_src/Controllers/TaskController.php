<?php
/**
 * Created by PhpStorm.
 * User: apple GeneratorCommand
 * Date: 2019/09/25
 * Time: 18:09:29
 */
namespace App\Http\Controllers;

use App\Generator\Controllers\TaskControllerTrait;
use App\Repositories\TaskRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    use RESTful,TaskControllerTrait;

    public function getOBJ()
    {
        return resolve(TaskRepository::class);
    }
    //商户新增红包api
    public function addTask(Request $request)
    {
        $uid = $request->input('app_id');
        $num = $request->input('num');
        $time = $request->input('time');
        $pay = $request->input('pay');
        $rand = $request->input('rand');
        $sign = $request->input('sign');
        if (!$uid || !$num || !$time || !$rand ||!$sign || !$pay ){
            return response()->apiReturn([],401,'参数错误');
        }
        try{
            $obj = $this->getOBJ();
            $ret = $obj->handAddTask($uid,$num,$time,$rand,$pay, $sign);
            if (is_array($ret)){
                return response()->apiReturn($ret['order']);
            }
            return response()->apiReturn([],401,$ret);
        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行失败');
        }



    }

    //商户状态查询api
    public function getStauts(Request $request)
    {
        $uid = $request->input('app_id');
        $time = $request->input('time');
        $order = $request->input('order');
        $sign = $request->input('sign');
        if (!$uid || !$time || !$order|| !$sign){
            return response()->apiReturn([],401,'参数错误');
        }
        try{
            $ret = $this->getOBJ()->handGetStauts($uid ,$time,$order,$sign);
            if (is_array($ret)){
                return response()->apiReturn($ret);
            }
            return response()->apiReturn([],401,$ret);
        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行失败');
        }
    }

    //商户已付款api
    public function setPayMoney(Request $request)
    {
        $uid = $request->input('app_id');
        $time = $request->input('time');
        $order = $request->input('order');
        $sign = $request->input('sign');
        if (!$uid || !$time || !$order|| !$sign){
            return response()->apiReturn([],401,'参数错误');
        }
        try{
            $ret = $this->getOBJ()->handSetPayMoney($uid ,$time,$order,$sign);
            if ($ret === true){
                return response()->apiReturn($ret);
            }
            return response()->apiReturn([],401,$ret);
        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行失败');
        }
    }

//商户主动取消订单api
    public function setCancel(Request $request)
    {
        $uid = $request->input('app_id');
        $time = $request->input('time');
        $order = $request->input('order');
        $sign = $request->input('sign');
        if (!$uid || !$time || !$order|| !$sign){
            return response()->apiReturn([],401,'参数错误');
        }
        try{
            $ret = $this->getOBJ()->handSetCancel($uid ,$time,$order,$sign);
            if ($ret === true){
                return response()->apiReturn($ret);
            }
            return response()->apiReturn([],401,$ret);
        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行失败');
        }
    }

    //商户申请付币异常api
    public function setComplaints(Request $request)
    {
        $uid = $request->input('app_id');
        $time = $request->input('time');
        $order = $request->input('order');
        $sign = $request->input('sign');
        if (!$uid || !$time || !$order|| !$sign){
            return response()->apiReturn([],401,'参数错误');
        }
        try{
            $ret = $this->getOBJ()->handSetComplaints($uid ,$time,$order,$sign);
            if ($ret === true){
                return response()->apiReturn($ret);
            }
            return response()->apiReturn([],401,$ret);
        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行失败');
        }
    }




//用户抢红包
    public function userTaskGenerate(Request $request)
    {
        $user = Auth::user();
        $order = $request->input('order');
        if (!$order){
            return response()->apiReturn([],401,'参数错误');
        }
        try{
            if($user->identity == 1 || $user->is_talk >= time()){
                return response()->apiReturn([],401,'无权抢做此任务');
            }
            $ret = $this->getOBJ()->handUserTaskGenerate($user, $order);
            if ($ret === true){
                return response()->apiReturn($ret);
            }
            return response()->apiReturn([],401,$ret);
        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行错误');
        }
    }


    //获取订单详情
    public function getDatil(Request $request)
    {
        $user = Auth::user();
        $order = $request->input('order');
        if (!$order){
            return response()->apiReturn([],401,'参数错误');
        }
        try{
            $ret = $this->getOBJ()->handGetDatil($user, $order);
            if (is_array($ret)){
                return response()->apiReturn($ret);
            }
            return response()->apiReturn([],401,$ret);

        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行错误');
        }

    }

//测试使用
    public function yy()
    {
//        $test = $this->getOBJ();
//        $task = $test->storage()->find(1);
//        $test->setNoPayMoney($task);
    }

    //用户确定付币
    public function userPayCurrency(Request $request)
    {
        $user = Auth::user();
        $order = $request->input('order');
        if (!$order){
            return response()->apiReturn([],401,'参数错误');
        }
        try{
            $ret = $this->getOBJ()->handPayCurrency($user, $order);
            if ($ret === true){
                return response()->apiReturn($ret);
            }
            return response()->apiReturn([],401,$ret);

        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行错误');
        }
    }

    public function getTaskIndex(Request $request)
    {
        $user = Auth::user();
        $page = $request->input('page',1);
        $num = $request->input('num', 10);
        $type = $request->input('type',1);
        try{
            $ret = $this->getOBJ()->handGetTaskIndex($user,$type, $page,$num);
            if (is_array($ret)){
                return response()->apiReturn($ret);
            }
            return response()->apiReturn([],401,$ret);
        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行错误');
        }
    }

    public function getGroupIndex(Request $request)
    {
        $user = Auth::user();
        $page = $request->input('page',1);
        $num = $request->input('num', 10);
        $type = $request->input('type',1)?$request->input('type',1):1;
        $tag = 1;
        try{
            $ret = $this->getOBJ()->handGetTaskIndex($user,$type, $page,$num,$tag);
            if (is_array($ret)){
                return response()->apiReturn($ret);
            }
            return response()->apiReturn([],401,$ret);
        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行错误');
        }
    }


    //获取红包状态
    public function judgeState(Request $request, TaskRepository $taskRepository)
    {
            Validator::make($request->all(),[
                'order' => 'required',
            ],[
                'order.required' => '请输入订单号！',
            ])->validate();
            $order = $request->input('order');
            $repeat_num = $request->input('repeat_num',0)?(int)$request->input('repeat_num',0):0;
            $uid = Auth::id();
            $data = $taskRepository->getState($uid, $order, $repeat_num);
             return response()->apiReturn($data);
    }


}
