<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/** One notable thing that happened to an item, as a service's history records it. */
enum WhatHappenedToIt: string
{
    /** A release was sent to the download client. */
    case Grabbed = 'grabbed';

    /** The download client failed the release it was handed. */
    case DownloadFailed = 'download-failed';

    /** It was imported to the library on disk. */
    case Imported = 'imported';

    /** Its file was removed. */
    case Removed = 'removed';

    /** The catalogue key for the sentence drawn for it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('health.trace.outcome.%s', $this->value);
    }
}
