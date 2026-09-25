<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Why a walkthrough could not go on.
 *
 * Ten causes and not one *failed*, because they send an operator to ten
 * different places: indexers that would not answer and a search that ran
 * cleanly and matched nothing are entirely different problems, and the second
 * is the one most often mistaken for the first.
 */
enum WhyTheWalkthroughStopped: string
{
    /** No indexer is configured, so there is nothing to search. */
    case NoIndexers = 'no-indexers';

    /** The indexers, or the service holding them, would not answer. */
    case IndexersFailed = 'indexers-failed';

    /** The search ran cleanly and matched nothing. */
    case NothingMatched = 'nothing-matched';

    /** Releases exist, and none meets the chosen quality preset. */
    case NoneMetThePreset = 'none-met-the-preset';

    /** Torrents are in play and the tunnel is not verified up, so nothing is grabbed. */
    case TunnelDown = 'tunnel-down';

    /** The release was never handed to a download client. */
    case NotGrabbed = 'not-grabbed';

    /** The download stopped making progress. */
    case Stalled = 'stalled';

    /** The download finished and the library manager would not take it. */
    case ImportFailed = 'import-failed';

    /** There is no media server in the running form to make it playable. */
    case NoMediaServer = 'no-media-server';

    /** It was imported, the media server was told, and it still cannot be found there. */
    case NotVisible = 'not-visible';

    /** The catalogue key for the sentence drawn for it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('health.walkthrough.stopped.%s', $this->value);
    }
}
