<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** What one run of choosing what the household may ask for reported, flattened for a template. */
final readonly class TheAllowanceChangeAsShown
{
    /**
     * @param string       $didSaid      the catalogue key leading the report: rehearsed or made
     * @param bool         $mayBeApplied whether this is a rehearsal a yes may be given to
     * @param list<string> $sentences    what the stack said, in its order and words
     */
    public function __construct(
        public string $didSaid,
        public bool $mayBeApplied,
        public array $sentences,
    ) {}
}
