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
     * @param ?TheChoiceAsShown $reading the choice waiting on a yes, and nothing where none is
     * @param ?ALineToSay       $said    the notice on what became of the choice, where there is one
     * @param ?string           $meaning the stack's account of a refusal beside this app's words, where it gave one that adds to them
     * @param ?ARefusalAsShown  $refused a refusal of another kind, in the stack's words
     */
    public function __construct(
        public HowTheReadingWent $went,
        public ?TheChoiceAsShown $reading = null,
        public ?ALineToSay $said = null,
        public ?string $meaning = null,
        public ?ARefusalAsShown $refused = null,
    ) {}
}
