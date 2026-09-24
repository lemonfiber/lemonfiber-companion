<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** A directory the stack keeps things under, flattened for a template. */
final readonly class ARootAsShown
{
    /**
     * @param string $where the directory, as the stack names it
     * @param string $what  what is kept under it
     */
    public function __construct(
        public string $where,
        public string $what,
    ) {}
}
