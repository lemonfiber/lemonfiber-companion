<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What choosing a filler came to, flattened for a template.
 *
 * A reading waiting on a yes carries it; a notice is said above it, or alone
 * where there is nothing left to agree to, with the stack's own words beside
 * it where they say more; a refusal of another kind is the stack's words.
 */
final readonly class TheChoiceTurnedOutToBe
{
    /**
     * @param ?TheChoiceAsShown     $reading  the choice waiting on a yes, and nothing where none is
     * @param array<string, string> $saidWith what fills the notice
     * @param ?ARefusalAsShown      $refused  a refusal of another kind, in the stack's words
     */
    public function __construct(
        public HowTheReadingWent $went,
        public ?TheChoiceAsShown $reading,
        public string $said,
        public array $saidWith,
        public string $meaning,
        public ?ARefusalAsShown $refused,
    ) {}
}
