<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One thing the stack keeps, flattened for a template.
 *
 * It has no field for a value. Whether it holds a secret is said, and what
 * the secret is has nowhere here to go.
 */
final readonly class SomethingKeptAsShown
{
    /**
     * @param string $what       what it is
     * @param string $where      where it is kept
     * @param string $why        why the stack keeps it
     * @param string $secretSaid the catalogue key for whether it holds a secret
     */
    public function __construct(
        public string $what,
        public string $where,
        public string $why,
        public string $secretSaid,
    ) {}
}
