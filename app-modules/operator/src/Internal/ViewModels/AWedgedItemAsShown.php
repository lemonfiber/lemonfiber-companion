<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Operator\Internal\Presenters\AgoAsShown;

/**
 * One unhealthy download, as the queue screen draws it before the queue acts on it.
 */
final readonly class AWedgedItemAsShown
{
    /**
     * @param AQueuedDownloadAsShown $download  the download
     * @param string           $classSaid the key for why it is unhealthy
     * @param string           $why       why, in the stack's words
     * @param int              $strikes   how many passes in a row have flagged it
     * @param AgoAsShown       $added     how long ago it was added, or nothing
     * @param AgoAsShown       $graceEnds how far ahead it stops being left alone, or nothing
     * @param list<string>     $next      the key for each act the next run would take, in order
     */
    public function __construct(
        public AQueuedDownloadAsShown $download,
        public string $classSaid,
        public string $why,
        public int $strikes,
        public AgoAsShown $added,
        public AgoAsShown $graceEnds,
        public array $next,
    ) {}
}
