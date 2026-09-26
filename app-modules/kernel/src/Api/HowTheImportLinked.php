<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What the import did with the finished download: one copy of the file, or two.
 *
 * A copy works, and costs its own size again on every import, so the sentence
 * each case keys says what that comes to rather than naming the case alone.
 */
enum HowTheImportLinked: string
{
    /** The library entry and the download are one file under two names. */
    case Hardlinked = 'hardlinked';

    /** The file now exists twice. */
    case Copied = 'copied';

    /** The catalogue key for the sentence drawn for it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('health.walkthrough.link.%s', $this->value);
    }
}
