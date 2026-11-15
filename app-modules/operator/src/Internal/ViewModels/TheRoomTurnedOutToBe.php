<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What asking a stack how full its machine is produced, flattened for a template.
 *
 * Every list is in the stack's order. Nothing here says what to remove: the
 * room on offer as costing nothing is listed, and each category of it is given
 * back on its own screen.
 */
final readonly class TheRoomTurnedOutToBe
{
    /**
     * @param string                 $standsSaid the catalogue key for where the machine stands, or empty where nothing answered
     * @param string                 $tone       the tone where the machine stands is drawn in, a `Modules\Design\View\Tone` value
     * @param bool                   $halted     whether the stack has stopped starting new downloads
     * @param list<AVolumeAsShown>   $volumes    each volume watched
     * @param list<ALineAsShown>     $account    where the room went
     * @param list<ADownloadAsShown> $downloads  the completed downloads on disk
     * @param list<ACategoryAsShown> $offered    each category the stack offers back as costing nothing
     */
    public function __construct(
        public HowTheReadingWent $went,
        public string $standsSaid,
        public string $tone,
        public bool $halted,
        public array $volumes,
        public array $account,
        public array $downloads,
        public array $offered,
    ) {}
}
