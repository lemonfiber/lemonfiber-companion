<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * The lines that differ between pausing and resuming, as catalogue keys.
 */
final readonly class WhatPausingSays
{
    /**
     * @param string $whatItDoes the heading over the rehearsal
     * @param string $explained  what carrying it out does, before the yes
     * @param string $agree      the yes
     * @param string $working    while the stack asks the clients
     * @param string $done       the heading over what each client read back
     */
    public function __construct(
        public string $whatItDoes,
        public string $explained,
        public string $agree,
        public string $working,
        public string $done,
    ) {}
}
