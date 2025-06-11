<?php

namespace Oh86\Captcha\SMS\Captchas;

use Illuminate\Contracts\Container\Container as Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Oh86\Captcha\CaptchaInterface;
use Oh86\Captcha\SMS\Exceptions\AcquireCaptchaException;
use Oh86\Captcha\SMS\SMSServiceInterface;


class SMSCaptcha implements CaptchaInterface
{
    private SMSServiceInterface $smsService;
    /**
     * @var array{
     *          characters: string[],
     *          length: int,
     *          expire: int,
     *          max_attempts: int,
     *          reuseable: bool,
     *      }
     */
    private $config;

    public function __construct(Application $app, SMSServiceInterface $smsService)
    {
        $this->config = Arr::only($app->get('config')->get('captcha.sms'), [
            'characters',
            'length',
            'expire',
            'max_attempts',
            'reuseable',
        ]);

        $this->smsService = $smsService;
    }

    public function randomOTP()
    {
        $otp = '';
        for ($i = 0; $i < $this->config['length']; $i++) {
            $otp .= Arr::random($this->config['characters']);
        }
        return $otp;
    }

    /**
     * @param array{phone:string, countryCode:string|null} $options
     * @return string
     */
    public function acquire($options = null)
    {
        $otp = $this->randomOTP();
        if (!$this->smsService->sendOTP($options, $otp)) {
            throw new AcquireCaptchaException('获取验证码失败');
        }

        $key = Str::random(32);

        Cache::put(
            'captcha:sms:' . $key,
            [
                'phone' => $options['phone'],
                'countryCode' => $options['countryCode'] ?? null,
                'otp' => $otp,
            ],
            $this->config['expire']
        );

        return $key;
    }

    /**
     * @param array{phone:string, countryCode:string, key:string, otp:string} $captcha
     * @return bool
     */
    public function verify($captcha): bool
    {
        /**
         * @var array{
         *          phone: string,
         *          countryCode: string|null,
         *          otp: string,
         *      } | null
         */
        $data = Cache::get('captcha:sms:' . $captcha['key']);
        if (!$data) {
            return false;
        }

        $errCnt = (int) Cache::get('captcha:sms:errCnt:' . $captcha['key'], 0);
        if ($errCnt >= $this->config['max_attempts']) {
            Cache::forget('captcha:sms:' . $captcha['key']);
            return false;
        }

        if ($data['phone'] != $captcha['phone'] || $data['countryCode'] != ($captcha['countryCode'] ?? null) || $data['otp'] != $captcha['otp']) {
            Cache::put('captcha:sms:errCnt:' . $captcha['key'], $errCnt + 1, $this->config['expire']);

            return false;
        }

        if (!$this->config['reuseable']) {
            Cache::forget('captcha:sms:' . $captcha['key']);
        }

        return true;
    }
}
