<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Operator\Internal\Presenters\AgoAsShown;

/**
 * How the queue heals itself, as the queue screen draws it.
 */
final readonly class TheHealingAsShown
{
    /**
     * @param HowTheReadingWent              $went          whether the stack answered, and what stopped it where not
     * @param string                         $healingSaid   the key for whether healing acts
     * @param int                            $strikesNeeded how many passes in a row flag an item before it is acted on
     * @param string                         $graceSaid     the key for how long a new download is left alone
     * @param int                            $graceCount    how many of that key's unit
     * @param AgoAsShown                     $classified    how long ago the latest pass classified the queue, or nothing
     * @param bool                           $incomplete    whether the list may be short
     * @param bool                           $rehearsed     whether this answer is a rehearsal, which cleared nothing
     * @param string                         $mismatch      where a client files a curator's downloads outside its folder, in the stack's words, or empty
     * @param list<AWedgedItemAsShown>       $items         every unhealthy item, in the stack's order
     * @param list<AServiceUnreachedAsShown> $unreachable   every service it could not reach
     * @param list<AHealingRunAsShown>       $runs          every run, newest first
     */
    public function __construct(
        public HowTheReadingWent $went,
        public string $healingSaid,
        public int $strikesNeeded,
        public string $graceSaid,
        public int $graceCount,
        public AgoAsShown $classified,
        public bool $incomplete,
        public bool $rehearsed,
        public string $mismatch,
        public array $items,
        public array $unreachable,
        public array $runs,
    ) {}
}
