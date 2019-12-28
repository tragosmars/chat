<?php
/**
 * Created by PhpStorm.
 * User: fumeibing
 * Date: 2019/9/19
 * Time: 11:39
 */

namespace App\Repositories;


class RealNameRepositories
{
    //中国大陆实名
    public function mainland($name, $card)
    {
            return true;
    }

    //其他实名
    public function other($name, $card)
    {
        return true;
    }
}