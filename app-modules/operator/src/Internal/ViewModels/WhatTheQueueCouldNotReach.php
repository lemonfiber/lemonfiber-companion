<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One thing the stack's repairs could not reach, flattened for a template.
 *
 * Both halves are always set, because {@see \Modules\Kernel\Api\Unsupported}
 * refuses to be built without either: a limit shown without its reason is one
 * nobody can act on.
 */
final readonly class WhatTheQueueCouldNotReach
{
    /**
     * @param string $what    what the stack could not reach, in its own words
     * @param string $because why, in its own words
     */
    public function __construct(
        public string $what,
        public string $because,
    ) {}
}
