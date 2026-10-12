<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** What one run of the line's action reported, flattened for a template. */
final readonly class TheLineChangeAsShown
{
    /**
     * @param string                       $didSaid      the catalogue key leading the report: rehearsed, applied or only read
     * @param bool                         $mayBeApplied whether this is a rehearsal a yes may be given to
     * @param HowTheLineTurnedOutToBe      $line         the line as this run left it or would leave it
     * @param list<AClientHoldingAsShown>  $clients      each download client, in the stack's order
     * @param ALiftAsShown                 $lift         where a lift of the limits stands
     */
    public function __construct(
        public string $didSaid,
        public bool $mayBeApplied,
        public HowTheLineTurnedOutToBe $line,
        public array $clients,
        public ALiftAsShown $lift,
    ) {}
}
