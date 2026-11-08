<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Which way a download is coming down, in the core's word for it, or a way this app has no word for.
 */
enum HowADownloadComes: string
{
    case Usenet = 'usenet';

    case Torrent = 'torrent';

    /** A way the core named that this app has no word for, drawn as a download. */
    case SomeOtherWay = 'another-way';

    /** The way the core named, or a download where this app has no word for it. */
    public static function named(string $said): self
    {
        return self::tryFrom($said) ?? self::SomeOtherWay;
    }

    /** The key for the line saying a change interrupts a download coming this way. */
    public function saidOnTheScreen(): string
    {
        return sprintf('config.findings.interrupts.%s', $this->value);
    }
}
