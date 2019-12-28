<?php
/**
 * Created by PhpStorm.
 * User: apple GeneratorCommand
 * Date: 2019/09/29
 * Time: 13:58:19
 */
namespace App\Http\Controllers;

use App\Generator\Controllers\NoticeDataControllerTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class NoticeDataController extends Controller
{
    use RESTful,NoticeDataControllerTrait;

    //统计未读通知
    public function listTotal()
    {
        $user = Auth::user();
        try{
            $w = array(
                'uid' => $user->id,
                'is_read' => 0
            );
            $data = resolve($this->repositoryClass)->storage()->selectRaw('type,count(*) as total')->where($w)->groupBy('type')->get();
            $result = array();
            foreach ($data as $v){
                $result[$v->type] = $v->total;
            }
            return response()->apiReturn($result);
        }catch (\Exception $exception){
            return response()->apiReturn([], 404, '运行错误');
        }
    }

    //获取指定类型的通通知列表
    public function getTypeList(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'type' => [
                'filled',
                Rule::in([1,2,3,4])
                ],
            'num' => 'filled|integer',
            'page' => 'filled|integer',
        ],[
            'type.filled' => '请选择类型',
            'type.in' => '类型错误',
            'num.filled' => '请填写记录数',
            'num.integer' => '数量类型错误',
            'page.filled' => '请填写页数',
            'page' => '分页类型错误',
        ]);
        if ($validator->fails()){
            return response()->apiReturn([], 401, $validator->errors()->first());
        }
        $page = $request->input('page',1);
        $num = $request->input('num',10);
        $type = $request->input('type',1);
        try{
            $w = array(
                'uid' => Auth::id(),
                'type' => $type
            );
            $obj= resolve($this->repositoryClass)->storage();
            $data = $obj->where($w)->offset(($page-1)*$num)->limit($num)->orderBy('created_at','desc')->get();
            $total = $obj->where($w)->count();
            $result = array();
            foreach ($data as $v){
                $one = array(
                    'content' => $v->content,
                    'is_read' => $v->is_read,
                    'id' => $v->id
                );
                $result[] = $one;
            }
            return response()->apiReturn([
                'data' => $result,
                'total' => $total
            ]);
        }catch (\Exception $exception){
            return response()->apiReturn([], 404, '运行错误');
        }

    }

    public function show(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'id' => 'required|integer',
        ],[
            'id.required' => '参数错误',
            'id.integer' => '参数类型错误',
        ]);
        if ($validator->fails()){
            return response()->apiReturn([], 401, $validator->errors()->first());
        }
        $id = $request->input('id');
        try{
            $data = resolve($this->repositoryClass)->storage()->find($id);
            if (!$data || $data->uid != Auth::id()){
                return response()->apiReturn([], 401, '记录不存在');
            }else{
                $result = array(
                    'content' => $data->content,
                    'created_at' => $data->created_at
                );
                $data->is_read = 1;
                $ret = $data->save();
                return response()->apiReturn($result);
            }
        }catch (\Exception $exception){
            return response()->apiReturn([], 404, '运行错误');
        }
    }

    public function del(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'ids' => 'required|json',
        ],[
            'ids.required' => '请选择删除的通知',
            'ids.json' => '参数类型错误',
        ]);
        if ($validator->fails()){
            return response()->apiReturn([], 401, $validator->errors()->first());
        }
        try{
            $ids = json_decode($request->input('ids'),true);
            $w = array(
                'uid' => Auth::id()
            );
            $ret = resolve($this->repositoryClass)->storage()->where($w)->whereIn('id', $ids)->delete();
            return response()->apiReturn($ret);
        }catch (\Exception $exception){
            return response()->apiReturn([], 404, '运行错误');
        }
    }


}
