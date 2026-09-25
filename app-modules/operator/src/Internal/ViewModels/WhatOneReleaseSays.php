<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One release, flattened to what a row draws.
 *
 * **Whether the household would notice travels as a flag, not as a sentence.**
 * The distinction is what a screen leads on, and a row handed a finished phrase
 * could not be grouped by it.
 *
 * **What it delivers travels as the stack's own words, or as nothing.** This is
 * prose the stack wrote and nothing here composes it. A release it said nothing
 * about carries `null` rather than an empty string, and the template reads the
 * absence and says so in as many words.
 */
final readonly class WhatOneReleaseSays
{
    public function __construct(
        public string $version,
        public bool $theHouseholdWouldNotice,
        public bool $wasWithdrawn,
        public ?string $deliversSaid = null,
    ) {}
}
