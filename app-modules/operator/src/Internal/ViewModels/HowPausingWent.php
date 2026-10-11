<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What became of pausing or resuming the downloads, flattened for a template.
 *
 * Each client's line is drawn from what it read back after it was asked, never
 * from the rehearsal above it.
 */
final readonly class HowPausingWent
{
    /**
     * @param HowTheReadingWent            $went         whether asking after it came back, and what stood in the way where it did not
     * @param bool                         $isWorking    whether the stack is still asking the clients
     * @param bool                         $hasEnded     whether the stack has no outcome for it any more
     * @param bool                         $wasRehearsed whether it was a rehearsal, which asked no client anything
     * @param list<ADownloadClientAsShown> $clients      every client, what it said before and what it read back after
     * @param string                       $caution      what a resume runs into, in the stack's words, or empty
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $isWorking,
        public bool $hasEnded,
        public bool $wasRehearsed,
        public array $clients,
        public string $caution,
    ) {}
}
