<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Which way a download is coming down, in the core's word for it.
 */
enum HowADownloadComes: string
{
    case Usenet = 'usenet';

    case Torrent = 'torrent';

    /** The key for the line saying a change interrupts a download coming this way. */
    public function saidOnTheScreen(): string
    {
        return sprintf('config.findings.interrupts.%s', $this->value);
    }
}
