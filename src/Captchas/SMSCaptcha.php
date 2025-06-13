<?php

namespace Oh86\Captcha\SMS\Captchas;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Oh86\Captcha\CaptchaInterface;
use Oh86\Captcha\Exceptions\AcquireCaptchaException;
use Oh86\Captcha\SMS\Exceptions\SendOTPException;
use Oh86\Captcha\SMS\OTPSenders\SmsOtpSenderInterface;


class SMSCaptcha implements CaptchaInterface
{
    private SmsOtpSenderInterface $otpSender;
    /**
     * @var array{
     *          characters: string[],
     *          length: int,
     *          expire: int,
     *          maxAttempts: int,
     *          reuseable: bool,
     *      }
     */
    private $config;

    /**
     * @param array $config
     * @param \Oh86\Captcha\SMS\OTPSenders\SmsOtpSenderInterface $otpSender
     */
    public function __construct($config, SmsOtpSenderInterface $otpSender)
    {
        $this->config = $config;
        $this->otpSender = $otpSender;
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

        try {
            $this->otpSender->sendOTP($options, $otp);
        } catch (SendOTPException $e) {
            throw new AcquireCaptchaException($e->getMessage());
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
        if ($errCnt >= $this->config['maxAttempts']) {
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
