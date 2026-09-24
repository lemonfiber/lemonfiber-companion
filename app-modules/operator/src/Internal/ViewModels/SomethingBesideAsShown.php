<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** Something on this machine that is not the stack's, flattened for a template. */
final readonly class SomethingBesideAsShown
{
    /**
     * @param string $what what it is
     * @param string $why  whose it is, and why it is not the stack's to take away
     */
    public function __construct(
        public string $what,
        public string $why,
    ) {}
}
