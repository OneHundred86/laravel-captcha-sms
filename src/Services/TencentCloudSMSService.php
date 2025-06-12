<?php

namespace Oh86\Captcha\SMS\Services;

use Oh86\Captcha\SMS\SMSServiceInterface;
use Oh86\SMS\Exceptions\SendSMSException;
use Oh86\SMS\Facades\SMS;

class TencentCloudSMSService implements SMSServiceInterface
{
    /**
     * @var array{
     *          smsDriver:string,
     *          smsApp:string,
     *     }
     */
    private array $config;

    /**
     * @param array{
     *          smsDriver:string,
     *          smsApp:string,
     *     } $config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function sendOTP($phoneInfo, $otp)
    {
        $countryCode = $phoneInfo['countryCode'] ?? '86';
        $phone = $phoneInfo['phone'];

        try {
            SMS::driver($this->config['smsDriver'])
                ->driver($this->config['smsApp'])
                ->send([$phone], [$otp], ['countryCode' => $countryCode]);

            return true;
        } catch (SendSMSException $e) {
            \Log::error(__METHOD__, ['error' => $e->getMessage()]);
            return false;
        }
    }
}