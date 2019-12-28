<?php
/**
* Created by PhpStorm.
* User: apple GeneratorCommand
* Date: 2019/09/17
* Time: 14:42:02
*/

namespace App\Generator\Repositories;

use App\Storage\Storage;
trait UserRepositoryTrait
{
    public function __construct(\App\Storage\Database\User $persistence)
    {
        parent::__construct($persistence);
    }

    public function store(array $newData)
    {
        //todo  wirete new data login
        $model = $this->storage();
        $model->name  =  $newData['name'];
        $model->moblie  =  $newData['moblie'];
        $model->password  =  $newData['password'];
        $model->pic  =  $newData['pic'];
        $model->identity  =  $newData['identity'];
        $model->grade  =  $newData['grade'];
        $model->fraction  =  $newData['fraction'];
        $model->rememberToken  =  $newData['rememberToken'];
        return $this->save($model);
    }
    public function update(Storage $oldModel, array $updateData)
    {
        //todo  wirete update  data login
        if(isset($updateData['name']))
        $oldModel->name  =  $updateData['name'];
        if(isset($updateData['moblie']))
        $oldModel->moblie  =  $updateData['moblie'];
        if(isset($updateData['password']))
        $oldModel->password  =  $updateData['password'];
        if(isset($updateData['pic']))
        $oldModel->pic  =  $updateData['pic'];
        if(isset($updateData['identity']))
        $oldModel->identity  =  $updateData['identity'];
        if(isset($updateData['grade']))
        $oldModel->grade  =  $updateData['grade'];
        if(isset($updateData['fraction']))
        $oldModel->fraction  =  $updateData['fraction'];
        if(isset($updateData['rememberToken']))
        $oldModel->rememberToken  =  $updateData['rememberToken'];
            return $this->save($oldModel);
    }

}