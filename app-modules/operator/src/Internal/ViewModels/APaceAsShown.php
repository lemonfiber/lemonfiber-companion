<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** What a copy came to against what a copy is reckoned to manage in a minute, flattened for a template. */
final readonly class APaceAsShown
{
    /**
     * @param string       $said   the catalogue key for the sentence, which says whether it was inside the budget and in which tense
     * @param ASizeAsShown $moved  what the copy came to
     * @param ASizeAsShown $budget what a copy can come to and still finish in a minute
     */
    public function __construct(
        public string $said,
        public ASizeAsShown $moved,
        public ASizeAsShown $budget,
    ) {}
}
