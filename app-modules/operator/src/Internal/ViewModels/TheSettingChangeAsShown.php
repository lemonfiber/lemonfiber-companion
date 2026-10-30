<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** What a run of a settings action reported, flattened for a template. */
final readonly class TheSettingChangeAsShown
{
    /**
     * @param string                       $didSaid     the catalogue key saying what the run did
     * @param bool                         $mayBeAgreed whether it is a rehearsal a yes can be given to
     * @param bool                         $toDefault   whether the change puts settings back to their defaults
     * @param list<ASettingMoveAsShown>    $moves       each setting the change moves
     * @param string                       $restarts    every running service the change restarts, listed, blank where none
     * @param string                       $regenerates every service whose entry the change regenerates, listed, blank where none
     * @param string                       $starts      every service held back the change starts, listed, blank where none
     * @param list<AServiceWaitingAsShown> $waiting     each service held back once the change is made
     * @param string                       $unrestarted why the services it restarts were not restarted, blank where they were
     */
    public function __construct(
        public string $didSaid,
        public bool $mayBeAgreed,
        public bool $toDefault,
        public array $moves,
        public string $restarts,
        public string $regenerates,
        public string $starts,
        public array $waiting,
        public string $unrestarted,
    ) {}
}
