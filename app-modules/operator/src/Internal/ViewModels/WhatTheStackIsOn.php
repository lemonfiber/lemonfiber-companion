<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * The version a stack is on, as the one word a line draws.
 *
 * Its own carrier rather than a {@see WhatOneReleaseSays}, which would mean
 * inventing a whole release to take one field off — a version nobody named,
 * said to be unnoticeable and not withdrawn — and those last two are answers
 * about a release that does not exist. Nothing reads them and nothing can be
 * wrong about them, which is how a placeholder outlives the reason for it.
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

    public function __construct(public string $version) {}
}
