<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One thing a finished walkthrough hands the operator on to, flattened for a template. */
final readonly class AStepOnAsShown
{
    /**
     * @param string $said                the catalogue key for the sentence that names it
     * @param bool   $leadsToWhereToWatch whether it points at a screen this app has: which app to watch on
     */
    public function __construct(
        public string $said,
        public bool $leadsToWhereToWatch,
    ) {}
}
