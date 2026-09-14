<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Release;

/**
 * The version a stack is on, as the one word a line draws.
 *
 * {@see \Modules\Kernel\Api\Upkeep::running()} answers in two closure arms
 * rather than with a nullable, which is `C2`'s cure and right: a screen handed
 * a null would print an empty version where one belongs, and an operator would
 * read that as *it is running nothing*. Both arms have to hand back an object,
 * and what this screen wants out of them is a string.
 *
 * Its own carrier rather than a {@see WhatOneReleaseSays}, which is what the
 * unstated arm used to build. That meant inventing a whole release to take one
 * field off — a version nobody named, said to be unnoticeable and not
 * withdrawn — and those last two were answers about a release that does not
 * exist. Nothing read them and nothing could have been wrong about them, which
 * is how a placeholder outlives the reason for it.
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

    private function __construct(public string $version) {}

    public static function of(Release $release): self
    {
        return new self($release->version());
    }

    /** The stack has not said what it is on, which is not the same as nothing. */
    public static function notNamed(): self
    {
        return new self(self::NOT_NAMED);
    }
}
