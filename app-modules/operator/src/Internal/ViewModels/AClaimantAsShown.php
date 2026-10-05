<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One service that claims a capability, with where it came from, as a template draws it.
 *
 * Where it came from is drawn in the line every service's origin is drawn in,
 * and is absent only where the stack named a claimant without saying where it
 * came from.
 */
final readonly class AClaimantAsShown
{
    public function __construct(
        public string $name,
        public ?WhereARowSaysItCameFrom $from,
    ) {}
}
