<?php
/**
 * Created by PhpStorm.
 * User: apple GeneratorCommand
 * Date: 2019/09/18
 * Time: 14:45:12
 */
namespace App\Http\Controllers;

use App\Generator\Controllers\PayControllerTrait;
use App\Repositories\PayRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayController extends Controller
{

    use RESTful,PayControllerTrait;

    public function getPayList()
    {
        try{
            $list = config('pay.list');
            return response()->apiReturn($list);
        }catch (\Exception $exception){
            return response()->apiReturn([], 404, '运行失败');
        }
    }

    public function store(Request $request)
    {
        $uid = Auth::user()->id;
        $name = $request->input('name');
        $account = $request->input('account');
        $pay_id = $request->input('pay_id');
        $pay_pic = $request->input('pay_pic');
        $pid = $request->input('pid');//支付宝id
        $bankName = $request->input('bank_name');//银行名字
//        $bankAddr = $request->input('bank_addr');
        if (!$name || !$account || !$pay_id){
            return response()->apiReturn([], 401, '参数错误');
        }
        try{
            $other = array(
                'pid' => $pid?$pid:'',
                'bankName' => $bankName?$bankName:'',
//                'bankAddr' => $bankAddr?$bankAddr:''
            );
            $obj = resolve(PayRepository::class);
            $result = $obj->handStore($uid, $name, $account, $pay_id, $pay_pic, $other);
            if ($result === true){
                return response()->apiReturn(true);
            }else{
                return response()->apiReturn([], 401, $result);
            }


        }catch (\Exception $exception){
            return response()->apiReturn([], 404, '运行失败');
        }

    }

    public function index(){
        $uid = Auth::user()->id;
        try{
            $obj = resolve(PayRepository::class);
            $result = $obj->handIndex($uid);
            return response()->apiReturn($result);
        }catch (\Exception $e){
            return response()->apiReturn([], 404, '运行失败');
        }
    }

    public function show(Request $request)
    {
        $uid = Auth::user()->id;
        $id = $request->input('id');
        if (!$id){
            return response()->apiReturn([], 401, '参数错误');
        }
        try{
            $obj = resolve(PayRepository::class);
            $result = $obj->handShow($uid, $id);
            return response()->apiReturn($result);
        }catch (\Exception $exception){
            return response()->apiReturn([], 404, '参数错误');
        }
    }

    public function destroy(Request $request)
    {
        $uid = Auth::user()->id;
        $id = $request->input('id');
        if (!$id){
            return response()->apiReturn([], 401, '参数错误');
        }
        try{
            $obj = resolve(PayRepository::class);
            $result = $obj->handDestroy($uid, $id);
            if ($result === true){
                return response()->apiReturn($result);
            }else {
                return response()->apiReturn([], 404,$result);
            }

        }catch (\Exception $exception){
            return response()->apiReturn([], 404, '运行失败');
        }
    }


}
