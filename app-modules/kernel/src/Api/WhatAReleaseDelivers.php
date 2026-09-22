<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * What a release delivers, in the stack's own prose — or the stack saying
 * nothing about it.
 *
 * **An operator is asked to agree to an update, so they are owed what it
 * changes.** A version string is an identifier and not an argument: nobody
 * decides an evening on `4.0.15`. Whether the household would notice is the
 * other half and is already on the row, and it says *whether* this matters
 * rather than *what* it is.
 *
 * **Two arms rather than a string that may be empty.** The wire carries this
 * as optional, and a release the generator had nothing to say about is a real
 * state rather than a defect — a release with no user-facing change is
 * required to be stated as such rather than shown as an empty one. Folded into
 * one string, a row with nothing to print and a row whose prose failed to
 * arrive draw identically, and the second is the one somebody should be
 * looking into.
 *
 * **No accessor**, for {@see WhatASettingHolds}'s reason: the only way to the
 * prose is through {@see either()}, which cannot be entered without saying
 * what the screen does when there is none.
 */
final readonly class WhatAReleaseDelivers
{
    private function __construct(private string $said, private bool $told) {}

    /**
     * The stack said what this release delivers.
     *
     * Blank is refused rather than carried, because a blank sentence and no
     * sentence are the same thing on a screen and only one of them is
     * something the stack said. A payload with the field present and empty
     * reads here as the stack having said nothing.
     */
    public static function said(string $prose): self
    {
        $told = trim($prose);

        return $told === '' ? self::saidNothing() : new self(said: $told, told: true);
    }

    /**
     * The stack carried no prose for this release.
     */
    public static function saidNothing(): self
    {
        return new self(said: '', told: false);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * @template TSaid of object
     * @template TSilent of object
     *
     * @param  Closure(string): TSaid  $said
     * @param  Closure(): TSilent  $saidNothing
     * @return TSaid|TSilent
     */
    public function either(Closure $said, Closure $saidNothing): object
    {
        // Read off the silence, as the other folds here read off the arm with
        // a consequence: a fall-through that printed an empty string is the
        // blank row this type exists to prevent.
        return $this->told ? $said($this->said) : $saidNothing();
    }
}
