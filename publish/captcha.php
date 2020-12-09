<?php

declare(strict_types=1);
/**
 * This file is part of hyperf-ext/captcha.
 *
 * @link     https://github.com/hyperf-ext/captcha
 * @contact  eric@zhu.email
 * @license  https://github.com/hyperf-ext/captcha/blob/master/LICENSE
 */
return [
    'fonts_dir' => BASE_PATH . '/storage/fonts',
    'encryption_driver' => env('CAPTCHA_ENCRYPTION_DRIVER', 'aes'),

    'ttl' => env('CAPTCHA_TTL', 600),
    'characters' => env('CAPTCHA_CHARACTERS', 'abcdefghjmnpqrtuxyzABCDEFGHJMNPQRTUXYZ'),
    'length' => 4,
    'width' => 160,
    'height' => 80,
    'format' => 'png',
    'foreground_colors' => ['#000000FF'],
    'background_color' => '#FFFFFF00',
];
