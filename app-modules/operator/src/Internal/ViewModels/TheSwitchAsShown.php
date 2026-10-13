<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** What one run of a switch reported, flattened for a template. */
final readonly class TheSwitchAsShown
{
    /**
     * @param string       $didSaid      the catalogue key leading the report: rehearsed or made
     * @param bool         $mayBeApplied whether this is a rehearsal a yes may be given to
     * @param list<string> $stopped      each service it stops, in the stack's order
     * @param list<string> $started      each service it starts
     * @param list<string> $kept         each service both sets hold, kept running and not restarted
     * @param string       $stopCommand  the command it stops what falls outside with, as typed, or empty where nothing stops
     */
    public function __construct(
        public string $didSaid,
        public bool $mayBeApplied,
        public array $stopped,
        public array $started,
        public array $kept,
        public string $stopCommand,
    ) {}
}
