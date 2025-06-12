<?php

namespace Oh86\Captcha\SMS\Services;

use Oh86\Captcha\SMS\SMSServiceInterface;
use TencentCloud\Common\Credential;
use TencentCloud\Sms\V20190711\Models\SendSmsRequest;
use TencentCloud\Sms\V20190711\SmsClient;

class TencentCloudSMSService implements SMSServiceInterface
{
    /**
     * @var array{
     *          secret_id:string,
     *          secret_key:string,
     *          region:string,
     *          sms_app:array{
     *              app_id:string,
     *              sign:string,
     *              template_id:string,
     *          }
     *     }
     */
    private array $config;

    /**
     * @param array{
     *          secret_id:string,
     *          secret_key:string,
     *          region:string,
     *          sms_app:array{
     *              app_id:string,
     *              sign:string,
     *              template_id:string,
     *          }
     *     } $config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function createCredential()
    {
        return new Credential($this->config['secret_id'], $this->config['secret_key']);
    }

    public function getRegion()
    {
        return $this->config['region'] ?? 'ap-guangzhou';
    }

    /**
     * @param string[] $phones 下发手机号码，采用 E.164 标准，格式为+[国家或地区码][手机号]，单次请求最多支持200个手机号且要求全为境内手机号或全为境外手机号。例如：+8618501234444， 其中前面有一个+号 ，86为国家码，18501234444为手机号。
     * @param string[] $templateParams
     * @return string[] 失败手机号码列表
     */
    public function send(array $phones, array $templateParams)
    {
        $client = new SmsClient($this->createCredential(), $this->getRegion());

        $req = new SendSmsRequest();
        $req->SmsSdkAppid = $this->config['sms_app']['app_id'];
        $req->Sign = $this->config['sms_app']['sign'];
        $req->TemplateID = $this->config['sms_app']['template_id'];
        $req->PhoneNumberSet = $phones;
        $req->TemplateParamSet = $templateParams;

        $resp = $client->SendSms($req);

        $failedList = [];
        /**
         * @var \TencentCloud\Sms\V20190711\Models\SendStatus $status
         */
        foreach ($resp->getSendStatusSet() as $status) {
            if ($status->getCode() != 'Ok') {
                $failedList[] = $status->getPhoneNumber();

                // 记录错误日志
                if (class_exists(\Log::class)) {
                    \Log::error(__METHOD__, [
                        'phone' => $status->getPhoneNumber(),
                        'template_id' => $req->TemplateID,
                        'code' => $status->getCode(),
                        'message' => $status->getMessage(),
                    ]);
                }
            }
        }

        return $failedList;
    }

    public function sendOTP($phoneInfo, $otp)
    {
        $countryCode = $phoneInfo['countryCode'] ?? '86';
        $phone = $phoneInfo['phone'];

        // 发送短信验证码
        $failedList = $this->send(["+$countryCode$phone"], [$otp]);
        return empty($failedList);
    }
}