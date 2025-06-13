<?php

namespace Oh86\Captcha\SMS\OTPSenders;

use Oh86\Captcha\SMS\Exceptions\SendOTPException;
use Oh86\SMS\Exceptions\SendSMSException;
use Oh86\SMS\Facades\SMS;

class Oh86SmsOtpSender implements SmsOtpSenderInterface
{
    private string $smsDriver;

    public function __construct(string $smsDriver)
    {
        $this->smsDriver = $smsDriver;
    }

    public function sendOTP($phoneInfo, $otp)
    {
        $countryCode = $phoneInfo['countryCode'] ?? '86';
        $phone = $phoneInfo['phone'];

        try {
            SMS::driver($this->smsDriver)
                ->send([$phone], [$otp], ['countryCode' => $countryCode]);
        } catch (SendSMSException $e) {
            throw new SendOTPException($e->getMessage());
        }
    }
}