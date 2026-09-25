<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What asking a stack which credentials it holds produced, flattened for a template.
 *
 * An empty list on a reading that came back is a stack holding none; a reading
 * that did not come back carries its obstacle in `went`, and the template
 * draws that instead of the list.
 */
final readonly class TheCredentialsTurnedOutToBe
{
    /**
     * @param list<ACredentialAsShown> $held       every credential, in the stack's order
     * @param string                   $summary    what the store is, or empty where nothing came back
     * @param list<string>             $against    what the store protects against
     * @param list<string>             $notAgainst what the store does not protect against
     */
    public function __construct(
        public HowTheReadingWent $went,
        public array $held,
        public string $summary,
        public array $against,
        public array $notAgainst,
    ) {}
}
