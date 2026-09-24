<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What asking a stack how full its machine is produced, flattened for a template.
 *
 * Every list is in the stack's order. Nothing here says what to remove.
 */
final readonly class TheRoomTurnedOutToBe
{
    /**
     * @param string                 $standsSaid the catalogue key for where the machine stands, or empty where nothing answered
     * @param bool                   $halted     whether the stack has stopped starting new downloads
     * @param list<AVolumeAsShown>   $volumes    each volume watched
     * @param list<ALineAsShown>     $account    where the room went
     * @param list<ADownloadAsShown> $downloads  the completed downloads on disk
     */
    public function __construct(
        public HowTheReadingWent $went,
        public string $standsSaid,
        public bool $halted,
        public array $volumes,
        public array $account,
        public array $downloads,
    ) {}
}
