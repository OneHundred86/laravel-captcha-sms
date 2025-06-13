<?php

namespace Oh86\Captcha\SMS;

use Illuminate\Support\ServiceProvider;
use Oh86\Captcha\CaptchaManager;
use Oh86\Captcha\SMS\Captchas\SMSCaptcha;
use Oh86\Captcha\SMS\OTPSenders\Oh86SmsOtpSender;

class SMSCaptchaServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->get(CaptchaManager::class)
            ->extend('sms', function ($app) {
                $config = $app->make('config')->get('captcha.sms');
                return new SMSCaptcha($config['captcha'], new Oh86SmsOtpSender($config['smsDriver']));
            });
    }
}