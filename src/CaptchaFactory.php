<?php

declare(strict_types=1);
/**
 * This file is part of hyperf-ext/captcha.
 *
 * @link     https://github.com/hyperf-ext/captcha
 * @contact  eric@zhu.email
 * @license  https://github.com/hyperf-ext/captcha/blob/master/LICENSE
 */
namespace HyperfExt\Captcha;

use Hyperf\Contract\ConfigInterface;
use HyperfExt\Encryption\Crypt;
use Imagick;
use ImagickDraw;
use ImagickPixel;
use Psr\SimpleCache\CacheInterface;
use Throwable;

/**
 * SimpleCaptcha class.
 */
class CaptchaFactory
{
    /**
     * @var array
     */
    protected $fonts;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var \Psr\SimpleCache\CacheInterface
     */
    protected $cache;

    public function __construct(ConfigInterface $config, CacheInterface $cache)
    {
        $this->config = $config->get('captcha');
        $this->cache = $cache;
        $this->fonts = glob(realpath($this->config['fonts_dir']) . '/*.{ttf,otf}', GLOB_BRACE);
    }

    public function create(?array $config = null): Captcha
    {
        $config = $config ? array_merge($this->config, $config) : $this->config;

        $text = $this->getRandomText($config['characters'], $config['length']);
        $expiresAt = $config['ttl'] + time();
        $key = $this->assembleKey($text, $expiresAt);

        return new Captcha($key, $text, $this->createImageBlob($text, $config), $expiresAt);
    }

    public function validate(string $key, string $text): bool
    {
        try {
            [$original, $expiresAt] = $this->disassembleKey($key);

            if ($original === strtolower($text)
                && $expiresAt >= time()
                && $this->cache->get($cacheKey = $this->getCacheKey($key)) === null
            ) {
                $this->cache->set($cacheKey, $expiresAt, $expiresAt - time());
                return true;
            }
        } catch (Throwable $e) {
        }

        return false;
    }

    protected function assembleKey(string $text, int $expiresAt): string
    {
        return Crypt::encrypt([strtolower($text), $expiresAt, random_bytes(16)], true, $this->config['encryption_driver']);
    }

    protected function disassembleKey(string $key): array
    {
        return Crypt::decrypt($key, true, $this->config['encryption_driver']);
    }

    protected function createImageBlob(string $text, array $config): Blob
    {
        $image = new Imagick();

        $draws = [];
        $x = 0;
        $y = 0;
        foreach (str_split($text) as $char) {
            $foregroundColor = new ImagickPixel($this->getRandomForegroundColor($config['foreground_colors']));
            $draw = new ImagickDraw();
            $draw->setFont($this->getRandomFont());
            $draw->setFontSize($config['height']);
            $draw->setFillColor($foregroundColor);
            $metrics = $image->queryFontMetrics($draw, $char);
            $draw->annotation($x, $metrics['ascender'], $char);

            $draws[] = $draw;
            $x += $metrics['textWidth'];
            $y = max($y, $metrics['textHeight']);
        }

        $image->newImage((int) $x, (int) $y, new ImagickPixel($config['background_color']));

        foreach ($draws as $draw) {
            $image->drawImage($draw);
        }

        $image->trimImage(0);
        $image->setImagePage(0, 0, 0, 0);

        $w = $image->getImageWidth();
        $h = $image->getImageHeight();

        $draw = new ImagickDraw();
        $lineColor = new ImagickPixel($this->getRandomForegroundColor($config['foreground_colors']));
        $draw->setStrokeColor($lineColor);
        $draw->setFillColor($lineColor);
        $draw->setStrokeWidth(max(2, $config['height'] / 15));
        $draw->line(0, random_int($h * 2, $h * 8) / 10, $x, random_int($h * 2, $h * 8) / 10);
        $image->drawImage($draw);

        $image->swirlImage(random_int(40, 60));

        $image->scaleImage($config['width'], $config['height']);

        $image->setImageFormat($config['format']);

        $data = $image->getImageBlob();

        $image->destroy();

        return new Blob($data);
    }

    protected function getCacheKey(string $key): string
    {
        return 'captcha:' . md5($key);
    }

    protected function getRandomFont(): string
    {
        return $this->fonts[array_rand($this->fonts)];
    }

    protected function getRandomForegroundColor(array $colors): string
    {
        return $colors[array_rand($colors, 1)];
    }

    protected function getRandomText(string $characters, int $length): string
    {
        $text = '';
        $charCount = strlen($characters);
        for ($i = 0; $i < $length; ++$i) {
            $text .= substr($characters, random_int(0, $charCount - 1), 1);
        }
        return $text;
    }
}
