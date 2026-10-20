<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** What a run of a setting's action reported, flattened for a template. */
final readonly class TheSettingChangeAsShown
{
    /**
     * @param string       $didSaid       the catalogue key saying what the run did
     * @param bool         $mayBeAgreed   whether it is a rehearsal a yes can be given to
     * @param bool         $toDefault     whether the change puts the setting back to its default
     * @param string       $value         what it is to hold, blank where it goes back to its default
     * @param bool         $wasSet        whether it held a value of the operator's before
     * @param string       $was           what it held before, blank where it was the default
     * @param string       $restarts      every running service the change restarts, listed, blank where none
     * @param string       $regenerates   every service whose entry the change regenerates, listed, blank where none
     * @param string       $consequence   what the change costs, in the stack's words, blank where nothing
     * @param string       $unrestarted   why the services it restarts were not restarted, blank where they were
     */
    public function __construct(
        public string $didSaid,
        public bool $mayBeAgreed,
        public bool $toDefault,
        public string $value,
        public bool $wasSet,
        public string $was,
        public string $restarts,
        public string $regenerates,
        public string $consequence,
        public string $unrestarted,
    ) {}
}
