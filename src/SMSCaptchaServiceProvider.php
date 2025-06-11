<?php

namespace Oh86\Captcha\SMS;

use Illuminate\Support\ServiceProvider;
use Oh86\Captcha\CaptchaManager;

class SMSCaptchaServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->get(CaptchaManager::class)
            ->extend('sms', function ($app) {
                return new SMSCaptchaManager($app);
            });
    }
}