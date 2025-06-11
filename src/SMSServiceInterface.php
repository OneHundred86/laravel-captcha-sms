<?php

namespace Oh86\Captcha\SMS;

/**
 * 短信发送验证码服务接口
 */
interface SMSServiceInterface
{
    /**
     * 发送短信验证码
     * 
     * @param array{phone:string, countryCode:string|null} $phoneInfo
     * @param string $otp
     * @return bool
     */
    public function sendOTP($phoneInfo, $otp);
}
