# 验证码-短信验证码

### 一、安装

```shell
composer require oh86/laravel-captcha
composer require oh86/laravel-captcha-sms
# 可选项
# composer require tencentcloud/sms
```

### 二、配置 `config/captcha.php`

```php
return [
    'default' => env('CAPTCHA_DEFAULT_DRIVER', 'sms'),

    // ...

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
```

### 三、使用示例

```php
use Oh86\Captcha\Facades\Captcha;

// demo1
$key = Captcha::driver('sms')->acquire(['phone' => '13800138000']);
/** @var bool */
$result = Captcha::driver('sms')->verify(['key' => $key, 'value' => '066611']);

```
