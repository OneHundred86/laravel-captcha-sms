<?php

return [
    'default' => env('CAPTCHA_DEFAULT_DRIVER', 'sms'),

    // 短信验证码
    'sms' => [
        'default' => 'tencentCloud',  // 默认使用的短信服务驱动

        // 验证码配置
        'characters' => ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
        'length' => 6,   // 验证码长度
        'expire' => 300, // 验证码有效期，单位为秒
        'max_attempts' => 5, // 最大尝试次数
        'reuseable' => false, // 在有效期内是否可重用验证码

        // 腾讯云短信验证码
        'tencentCloud' => [
            'secret_id' => env('TENCENT_CLOUD_SECRET_ID'),
            'secret_key' => env('TENCENT_CLOUD_SECRET_KEY'),
            'region' => env('TENCENT_CLOUD_REGION', 'ap-guangzhou'),
            'sms_app' => [
                'app_id' => env('TENCENT_CLOUD_SMS_APP_ID'),
                'sign' => env('TENCENT_CLOUD_SMS_SIGN'),
                'template_id' => env('TENCENT_CLOUD_SMS_TEMPLATE_ID'),
            ],
        ],
    ],
];
