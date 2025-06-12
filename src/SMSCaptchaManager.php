<?php

namespace Oh86\Captcha\SMS;

use Illuminate\Support\Manager;
use Oh86\Captcha\SMS\Captchas\SMSCaptcha;
use Oh86\Captcha\SMS\Services\TencentCloudSMSService;
use RuntimeException;

class SMSCaptchaManager extends Manager
{
    public function getDefaultDriver()
    {
        return $this->container->get('config')->get('captcha.sms.default');
    }

    public function createTencentCloudDriver()
    {
        if (!class_exists(\TencentCloud\Sms\V20190711\SmsClient::class)) {
            throw new RuntimeException('Tencent Cloud SMS SDK is not installed. Please install it via composer: "composer require tencentcloud/sms".');
        }

        $config = $this->container->get('config')->get('captcha.sms.tencentCloud');
        return new SMSCaptcha($this->container, new TencentCloudSMSService($config));
    }

    // TODO: extend more drivers
}