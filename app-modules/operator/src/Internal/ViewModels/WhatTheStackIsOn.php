<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * The release a stack is on: the version a line draws, and its notes where the
 * stack named it.
 *
 * The notes are null where the stack named no release, rather than a
 * {@see WhatOneReleaseSays} invented to fill the gap: a version nobody named,
 * said to be unnoticeable and not withdrawn, would be answers about a release
 * that does not exist.
 */
final readonly class WhatTheStackIsOn
{
    /**
     * What a screen shows where the stack did not name a version.
     *
     * A dash rather than an empty string, so the line renders in every state:
     * a screen silent about the version teaches an operator to read silence,
     * and silence is also what a screen that forgot the field produces.
     */
    public const string NOT_NAMED = '—';

    public function __construct(
        public string $version,
        public ?WhatOneReleaseSays $notes = null,
    ) {}
}
