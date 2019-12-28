<?php
/**
 * Created by PhpStorm.
 * User: apple GeneratorCommand
 * Date: 2019/09/20
 * Time: 16:12:07
 */
namespace App\Http\Controllers;

use App\Events\NoticeUser;
use App\Generator\Controllers\TransactionOrderControllerTrait;
use App\Repositories\RedisRepository;
use App\Repositories\TransactionOrderRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use GatewayWorker\Lib\Gateway;

class TransactionOrderController extends Controller
{
    use RESTful,TransactionOrderControllerTrait;

    public function store(Request $request)
    {
        $uid = Auth::user()->id;
        $parent = Auth::user()->initial_id;
        $order = $request->input('order');
        $num = $request->input('num');
//        $pay = $request->input('pay');
        if (!$order || !$num ){
            return response()->apiReturn([],401,'参数错误');
        }
        try{
            $obj = resolve(TransactionOrderRepository::class);
            $ret = $obj->handStore($uid, $parent,$order, $num);
            if (isset($ret['result'])){
                return response()->apiReturn($ret['data']);
            }
            return response()->apiReturn([],401,$ret);


        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行错误');
        }
    }

    public function show(Request $request)
    {
        $uid = Auth::user()->id;
        $order = $request->input('order');
        if (!$order){
            return response()->apiReturn([],401,'参数错误');
        }
        try{
            $obj = resolve(TransactionOrderRepository::class);
            $ret = $obj->handShow($uid, $order);
            if (is_array($ret)){
                return response()->apiReturn($ret);
            }
            return response()->apiReturn([],401,$ret);
        }catch (\Exception $exception){
            return response()->apiReturn([],404,$exception->getMessage());
        }
    }

    public function payMent(Request $request)
    {
        $uid = Auth::user()->id;
        $order = $request->input('order');
        if (!$order){
            return response()->apiReturn([],401,'参数错误');
        }
        try{
            $obj = resolve(TransactionOrderRepository::class);
            $ret = $obj->handPayment($uid, $order);
            if ($ret === true){
                return response()->apiReturn($ret);
            }
            return response()->apiReturn([],401,$ret);
        }catch (\Exception $exception){
            return response()->apiReturn([],404,$exception->getMessage());
        }
    }

    public function payCancel(Request $request)
    {
        $uid = Auth::user()->id;
        $order = $request->input('order');
        if (!$order){
            return response()->apiReturn([],401,'参数错误');
        }
        try{
            $obj = resolve(TransactionOrderRepository::class);
            $ret = $obj->handPayCance($uid, $order);
            if ($ret === true){
                return response()->apiReturn($ret);
            }
            return response()->apiReturn([],401,$ret);
        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行错误');
        }
    }

    public function payNocollectMoney(Request $request)
    {
        $uid = Auth::user()->id;
        $order = $request->input('order');
        if (!$order){
            return response()->apiReturn([],401,'参数错误');
        }
        try{
            $obj = resolve(TransactionOrderRepository::class);
            $ret = $obj->handPayNocollectMoney($uid, $order);
            if ($ret === true){
                return response()->apiReturn($ret);
            }
            return response()->apiReturn([],401,$ret);
        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行错误');
        }
    }

    public function payCollectMoney(Request $request)
    {
        $uid = Auth::user()->id;
        $order = $request->input('order');
        if (!$order){
            return response()->apiReturn([],401,'参数错误');
        }
        try{
            $obj = resolve(TransactionOrderRepository::class);
            $ret = $obj->handPayCollectMoney($uid, $order);
            if ($ret === true){
                return response()->apiReturn($ret);
            }
            return response()->apiReturn([],401,$ret);
        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行错误');
        }
    }


    public function  getOrderList(Request $request)
    {
        $user = Auth::user();
        $type = $request->input('type',1);
        $page = $request->input('page', 1);
        $num = $request->input('num', 10);
        try{
            $obj = resolve(TransactionOrderRepository::class);
            $ret = $obj->handGetOrderList($user, $type,$page,$num);
            if (is_array($ret)){
                return response()->apiReturn($ret);
            }
            return response()->apiReturn([],401,$ret);
        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行失败');
        }
    }


    //买家催促卖家付币
    public function setNoticePayCurrency(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'order' => 'bail|required|string',
        ],[
            'order.required' => '订单号必填！',
            'order.string' => '订单号错误！',
        ]);
        if ($validator->fails()){
            return response()->apiReturn([],401,$validator->errors()->first());
        }
        try{
            $w = array(
                'order_id' => $request->input('order'),
                'uid' => Auth::user()->id,
                'status' => 2
            );
            $order = resolve($this->repositoryClass)->storage()->where($w)->first();
            if (!$order){
                return response()->apiReturn([],401,'当前订单不能进行此操作！');
            }
            $content = array(
                'type'=>'user_nocite_1',
                'content' => '订单'.$order->order_id.' 已付款，请核实后尽快发货！',    //通知内容
                'time' => time(),    //通知时间 -时间戳
            );
            event(new NoticeUser($content,'SEND',$order->sell_uid));
            return response()->apiReturn(true);
        }catch (\Exception $exception){
            return response()->apiReturn([],404, '运行失败');
        }
    }

}
