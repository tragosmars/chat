<?php
/**
 * Created by PhpStorm.
 * User: apple GeneratorCommand
 * Date: 2019/09/19
 * Time: 20:47:30
 */
namespace App\Http\Controllers;

use App\Generator\Controllers\TransactionControllerTrait;
use App\Repositories\TransactionRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    use RESTful,TransactionControllerTrait;

    public function store(Request $request)
    {
        $uid = Auth::user()->id;
        $total = $request->input('total');
        $max = $request->input('max');
        $min = $request->input('min');
        $payType = $request->input('pay_type');
        $remarks = $request->input('remarks','')?$request->input('remarks',''):'';
        if (!$total || !$max || !$min || !$payType){
            return response()->apiReturn([],401,'参数错误');
        }
        try{
            $obj = resolve(TransactionRepository::class);
            $ret = $obj->handStore($uid, $total, $max, $min, $payType, $remarks);
            if ($ret === true){
                return response()->apiReturn($ret);
            }else {
                return response()->apiReturn([],401,$ret);
            }

        }catch (\Exception $exception){
            return response()->apiReturn([],404,$exception->getMessage());
        }

    }

    public function index(Request $request)
    {
        $page = $request->input('page',1);
        $num = $request->input('num',10);
        try{
            $obj = resolve(TransactionRepository::class);
            $ret = $obj->handIndex($page, $num);
            return response()->apiReturn($ret);
        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行失败');
        }
    }

    public function destroy(Request $request)
    {
        $uid = Auth::user()->id;
        $order = $request->input('order_id');
        if (!$order){
            return response()->apiReturn([],401,'参数错误');
        }
        try{
            $obj = resolve(TransactionRepository::class);
            $ret = $obj->handDestroy($uid, $order);
            if ($ret === true){
                return response()->apiReturn($ret);
            }else {
                return response()->apiReturn([],401,$ret);
            }

        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行失败');
        }
    }





}
