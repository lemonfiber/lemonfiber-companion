<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/**
 * What a fold over a two-arm value came to, as text a template can read.
 *
 * `AboutWhat::either()` and `Because::either()` answer with an object, so that
 * a caller cannot take a service name out without saying what happens when
 * there is no service. That is right for a value and impossible for Blade,
 * which has no `either()` — so the arms answer with this, and
 * {@see WhatOneFindingSays} unwraps it once into the field the template reads.
 *
 * The empty string is how *absent* arrives on the other side, which is the same
 * convention `code`, `meaning` and `cost` already use on a row and is confined
 * to this surface for the same reason. `C2` keeps the absence explicit where
 * the value lives; this is the one place it is allowed to flatten, because the
 * thing reading it cannot branch on anything else.
 *
 * `Internal` because it is a detail of how this surface reads two values, and
 * `E2`'s promise is that anything here can be renamed without reading another
 * module.
 */
final readonly class AsText
{
    private function __construct(public string $said) {}

    /** The arm had something to say, and this is it. */
    public static function of(string $said): self
    {
        return new self($said);
    }

    /** The arm had nothing, which a template reads as an empty field. */
    public static function nothing(): self
    {
        return new self('');
    }
}
