<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/** What has become of a walkthrough, as the stack reports it. */
enum WhereTheWalkthroughIs: string
{
    /** Presented at the end of setup, not yet answered. */
    case Offered = 'offered';

    /** Declined, and available later. */
    case Skipped = 'skipped';

    /** Looking for releases. */
    case Searching = 'searching';

    /** Sending a release to the download client. */
    case Grabbing = 'grabbing';

    /** The download is running. */
    case Downloading = 'downloading';

    /** Moving the finished download into the library. */
    case Importing = 'importing';

    /** It is in the library and playable. */
    case Complete = 'complete';

    /** Stopped at a named step, with a diagnosis. */
    case Failed = 'failed';

    /** The operator left part-way, and whatever was in flight stays in flight. */
    case Abandoned = 'abandoned';

    /** The catalogue key for the sentence drawn for it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('health.walkthrough.state.%s', $this->value);
    }
}
