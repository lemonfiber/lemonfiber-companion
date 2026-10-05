<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One of the stack's links, as a template draws it.
 *
 * Every word is the stack's: the service that asked, the capability, the
 * services and the reason. `asks` and `capability` are empty on a link kept to
 * a named service, which has no capability behind it and is headed by the
 * service it runs from.
 *
 * `choices` are the claimants an operator may choose to answer it: offered
 * only where two or more services claim it, and never one that answers it
 * already.
 */
final readonly class ALinkAsShown
{
    /**
     * @param array<string, string>  $asksWith    what fills the line that heads it
     * @param array<string, string>  $settledWith what fills the line that says how it settled
     * @param list<AClaimantAsShown> $claimants   every service that claims it, in the stack's order
     * @param list<string>           $choices     every claimant that may be chosen to answer it, in the same order
     */
    public function __construct(
        public string $by,
        public string $capability,
        public string $asks,
        public array $asksWith,
        public string $settledSaid,
        public array $settledWith,
        public bool $isContested,
        public string $why,
        public array $claimants,
        public array $choices,
    ) {}
}
