<?php

return [
    'default' => env('CAPTCHA_DEFAULT_DRIVER', 'sms'),

    // 短信验证码
    'sms' => [
        'smsDriver' => 'tencentCloud',  // sms.drivers.xxx
        // 验证码配置
        'captcha' => [
            'characters' => ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            'length' => 6,   // 验证码长度
            'expire' => 300, // 验证码有效期，单位为秒
            'maxAttempts' => 5, // 最大尝试次数
            'reuseable' => false, // 在有效期内是否可重用验证码
        ],
    ],
];
