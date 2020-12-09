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

use finfo;

class Blob
{
    /**
     * @var string
     */
    private $raw;

    /**
     * @var string
     */
    private $mimetype;

    public function __construct(string $raw)
    {
        $this->raw = $raw;
        $this->mimetype = (new finfo(FILEINFO_MIME_TYPE))->buffer($raw);
    }

    public function getRaw(): string
    {
        return $this->raw;
    }

    public function getMimetype(): string
    {
        return $this->mimetype;
    }

    public function toString(): string
    {
        return $this->raw;
    }

    public function toDataUrl(): string
    {
        return 'data:' . $this->mimetype . ';base64,' . base64_encode($this->raw);
    }
}
