<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What asking a stack for its glossary produced, flattened for a template.
 */
final readonly class TheWordsTurnedOutToBe
{
    /**
     * @param list<AWordAsShown> $words       the words shown, narrowed by any search
     * @param bool               $isSearching whether a search narrowed them, which tells an empty search from an empty glossary
     * @param string             $mayAsk      the word searched for that the glossary has no entry for, which the stack may be asked for, or empty
     * @param string             $unexplained the word searched for that the stack has no entry for either, shown as it came, or empty
     * @param string             $askingMet   what stood in the way of asking for the word searched for, as a key, or empty
     */
    public function __construct(
        public HowTheReadingWent $went,
        public array $words,
        public bool $isSearching,
        public string $mayAsk = '',
        public string $unexplained = '',
        public string $askingMet = '',
    ) {}
}
