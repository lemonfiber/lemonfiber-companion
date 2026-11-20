<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\TheDownloadsComingDown;
use Modules\Operator\Internal\ViewModels\ADownloadAStopInterruptsAsShown;
use Modules\Operator\Internal\ViewModels\WhatIsComingDownAsShown;

use function sprintf;

/**
 * Turns what an action that stops services would interrupt into what the question before it draws.
 */
final readonly class HowWhatIsComingDownReads
{
    /** The stack named what is still coming down. */
    public function this(TheDownloadsComingDown $coming): WhatIsComingDownAsShown
    {
        $shown = [];

        foreach ($coming as $download) {
            $shown[] = new ADownloadAStopInterruptsAsShown(
                name: $download->name,
                progress: $download->progress,
                by: sprintf('health.coming.by.%s', $download->by->value),
            );
        }

        return new WhatIsComingDownAsShown(found: true, coming: $shown);
    }

    /** The stack could not be asked, which says nothing of what is coming down. */
    public function unknown(): WhatIsComingDownAsShown
    {
        return new WhatIsComingDownAsShown(found: false, coming: []);
    }
}
