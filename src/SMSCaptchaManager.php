<?php

namespace Oh86\Captcha\SMS;

use Illuminate\Support\Manager;
use Oh86\Captcha\SMS\Captchas\SMSCaptcha;
use Oh86\Captcha\SMS\Services\TencentCloudSMSService;

class SMSCaptchaManager extends Manager
{
    public function getDefaultDriver()
    {
        return $this->container->get('config')->get('captcha.sms.default');
    }

    public function createTencentCloudSmsDriver()
    {
        $config = $this->container->get('config')->get('captcha.sms.tencentCloudSms');
        return new SMSCaptcha($this->container, new TencentCloudSMSService($config));
    }

    // TODO: extend more drivers
}