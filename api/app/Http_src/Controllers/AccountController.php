<?php
/**
 * Created by PhpStorm.
 * User: apple GeneratorCommand
 * Date: 2019/09/19
 * Time: 15:07:00
 */
namespace App\Http\Controllers;

use App\Generator\Controllers\AccountControllerTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    use RESTful,AccountControllerTrait;

    public function show()
    {
        $user = Auth::user();
        try{
            $result = resolve($this->repositoryClass)->handShow($user);
            return response()->apiReturn($result);
        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行失败');
        }
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $page = $request->input('page',1);
        $num = $request->input('num',10);
        $time = $request->input('time','d');//
        $type = $request->input('type','all');
        try{
            $result = resolve($this->repositoryClass)->handIndex($user,$time, $type,$page, $num);
            return response()->apiReturn($result);
        }catch (\Exception $exception){
            return response()->apiReturn([],404,'运行失败');
        }
    }


}
