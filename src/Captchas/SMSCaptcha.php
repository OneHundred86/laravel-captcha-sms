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

        Cache::put($this->genOTPKey($key), $otp, $this->config['expire']);

        return $key;
    }

    private function genOTPKey(string $key): string
    {
        return 'captcha:sms:' . $key;
    }

    private function genErrCntKey(string $key): string
    {
        return 'captcha:sms:errCnt:' . $key;
    }

    /**
     * @param array{key:string, value:string} $captcha
     * @return bool
     */
    public function verify($captcha): bool
    {
        $otpKey = $this->genOTPKey($captcha['key']);
        /**
         * @var string | null
         */
        $otp = Cache::get($otpKey);
        if (!$otp) {
            return false;
        }

        $errCntKey = $this->genErrCntKey($captcha['key']);
        $errCnt = (int) Cache::get($errCntKey, 0);
        if ($errCnt >= $this->config['max_attempts']) {
            Cache::forget($otpKey);
            return false;
        }

        if ($otp != $captcha['value']) {
            Cache::put($errCntKey, $errCnt + 1, $this->config['expire']);
            return false;
        }

        if (!$this->config['reuseable']) {
            Cache::forget($otpKey);
        }

        return true;
    }
}
