<?php

namespace Oh86\Captcha\SMS\OTPSenders;

/**
 * 短信验证码发送服务接口
 */
interface SmsOtpSenderInterface
{
    /**
     * 发送短信验证码
     * 
     * @param array{phone:string, countryCode:string|null} $phoneInfo
     * @param string $otp
     * @throws \Oh86\Captcha\SMS\Exceptions\SendOTPException
     */
    public function sendOTP($phoneInfo, $otp);
}
