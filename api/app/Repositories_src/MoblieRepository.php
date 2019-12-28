<?php
/**
 * Created by PhpStorm.
 * User: fumeibing
 * Date: 2019/9/17
 * Time: 15:06
 */

namespace App\Repositories;


class MoblieRepository
{
      public function regMoblie($moblie)
      {
         //大陆手机
          $reg = preg_match("/^1[356789]{1}[0-9]{9}$/",$moblie);
          return $reg;
      }

}