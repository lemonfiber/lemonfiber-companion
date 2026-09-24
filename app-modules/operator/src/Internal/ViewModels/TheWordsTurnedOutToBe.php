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
     */
    public function __construct(
        public HowTheReadingWent $went,
        public array $words,
        public bool $isSearching,
    ) {}
}
