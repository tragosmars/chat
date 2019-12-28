<?php
/**
 * Created by PhpStorm.
 * User: apple GeneratorCommand
 * Date: 2019/09/29
 * Time: 13:58:19
 */

namespace App\Generator\Controllers;

use Illuminate\Http\Request;
use App\Repositories\NoticeDataRepository;
trait NoticeDataControllerTrait
{
    public function __construct()
    {

        $this->repositoryClass = NoticeDataRepository::class;
    }

    protected  function storeData(Request $request)
    {
        return [
        'uid'=>$request->input('uid'),
                'type'=>$request->input('type'),
                'content'=>$request->input('content'),
                'isRead'=>$request->input('isRead'),
                ];

     }
    protected  function updateData(Request $request)
    {
        return [
];

     }
}