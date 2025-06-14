<?php

namespace Oh86\Captcha\SMS\Captchas;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Oh86\Captcha\CaptchaInterface;
use Oh86\Captcha\Exceptions\AcquireCaptchaException;
use Oh86\Captcha\SMS\Exceptions\SendOTPException;
use Oh86\Captcha\SMS\OTPSenders\SmsOtpSenderInterface;
use RuntimeException;


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

    private function assertArrayKeyExists(array $array, string $key)
    {
        if (!array_key_exists($key, $array)) {
            throw new RuntimeException(sprintf('The key "%s" does not exist.', $key));
        }
    }

    /**
     * @param array{phone:string, countryCode:string|null} $options
     * @return string
     */
    public function acquire($options = null)
    {
        $this->assertArrayKeyExists($options, 'phone');

        $otp = $this->randomOTP();

        try {
            $this->otpSender->sendOTP($options, $otp);
        } catch (SendOTPException $e) {
            throw new AcquireCaptchaException($e->getMessage());
        }

        $key = Str::random(32);

        Cache::put($this->genOTPKey($key), [
            'otp' => $otp,
            'phone' => $options['phone'],
            'countryCode' => $options['countryCode'] ?? null
        ], $this->config['expire']);

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
     * @param array{key:string, value:string, phone:string, countryCode:string|null} $captcha
     * @return bool
     */
    public function verify($captcha): bool
    {
        $this->assertArrayKeyExists($captcha, 'key');
        $this->assertArrayKeyExists($captcha, 'value');
        $this->assertArrayKeyExists($captcha, 'phone');

        $otpKey = $this->genOTPKey($captcha['key']);
        /** @var array{otp:string, phone:string, countryCode:string|null} | null */
        $data = Cache::get($otpKey);
        if (!$data) {
            return false;
        }

        if ($data['otp'] != $captcha['value'] || $data['phone'] != $captcha['phone'] || $data['countryCode'] != ($captcha['countryCode'] ?? null)) {
            $errCntKey = $this->genErrCntKey($captcha['key']);
            $errCnt = (int) Cache::get($errCntKey, 0) + 1;
            if ($errCnt >= $this->config['maxAttempts']) {
                Cache::forget($otpKey);
            } else {
                Cache::put($errCntKey, $errCnt, $this->config['expire']);
            }
            return false;
        }

        if (!$this->config['reuseable']) {
            Cache::forget($otpKey);
        }

        return true;
    }
}
