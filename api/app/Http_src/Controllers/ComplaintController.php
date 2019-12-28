<?php
/**
 * Created by PhpStorm.
 * User: apple GeneratorCommand
 * Date: 2019/09/23
 * Time: 15:24:57
 */
namespace App\Http\Controllers;

use App\Generator\Controllers\ComplaintControllerTrait;
use App\Repositories\ComplaintRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ComplaintController extends Controller
{
    use RESTful,ComplaintControllerTrait;


    public function addPic(Request $request)
    {

        $file = 'complaint';
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

    public function store(Request $request)
    {
        $uid = Auth::user()->id;
        $type = $request->input('type');
        $order = $request->input('order');
        $content = $request->input('content');
        $pic = json_decode($request->input('pic'),true);
        if (!$type || !$order || !$content || !$pic){
            return response()->apiReturn([],401,'参数错误');
        }
        try{
            $obj = resolve(ComplaintRepository::class);
            $ret = $obj->handStore($uid, $type, $order, $content, $pic);
            if ($ret === true){
                return response()->apiReturn(true);
            }else{
                return response()->apiReturn([],401,$ret);
            }
        }catch (\Exception $exception){
            return response()->apiReturn([],404,$exception->getMessage().'--'.$exception->getFile().'---'.$exception->getLine());
        }


    }


}
