<?php
/**
 * Created by PhpStorm.
 * User: fumeibing
 * Date: 2019/9/18
 * Time: 18:34
 */

namespace App\Repositories;


use App\User;
use Fruits\Apple\Verification\Mobile;

class NoticeRepository
{

    //发送短信验证码
    public function sendCode($uid)
    {
        $data = User::find($uid);
        if (!$data){
            return false;
        }
        $this->seed($data->mobile);
        return true;
    }

    //发送
    protected function seed($mobile)
    {
        $verification = new Mobile($mobile,Mobile::TYPE_REG);
        $verification->generate();
        $verification->send();

    }

    public function verCode($uid, $code)
    {
        $data = User::find($uid);
        if (!$data){
            return false;
        }
        return $this->verification($data->mobile, $code);
    }

    protected function verification($mobile, $code)
    {
        $verification = new Mobile($mobile,Mobile::TYPE_REG);
        if(!$verification->check($code)){
            return false;
        }
        $verification->clean();
        return true;
    }

}