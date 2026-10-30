<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** What the stack read of every download client, as a screen draws it. */
final readonly class WhatTheClientsSaid
{
    /**
     * @param list<ADownloadClientAsShown> $clients every client and what it said, in the stack's order
     * @param string                       $caution what a resume runs into, in the stack's words, or empty
     */
    public function __construct(
        public array $clients,
        public string $caution,
    ) {}
}
