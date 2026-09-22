<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Kernel\Api\Release;

/**
 * One release, flattened to what a row draws.
 *
 * **Whether the household would notice travels as a flag, not as a sentence.**
 * The distinction is what a screen sorts and leads on, and a row handed
 * a finished phrase could not be grouped by it.
 *
 * **What it delivers travels as the stack's own words, and a flag saying
 * whether there are any.** The opposite of the line above, and for the
 * opposite reason: this is prose the stack wrote and nothing here composes it.
 * The flag is what keeps a release the stack said nothing about from drawing
 * as a row with a blank where a sentence belongs — the template says so in as
 * many words instead.
 */
final readonly class WhatOneReleaseSays
{
    public function __construct(
        public string $version,
        public bool $theHouseholdWouldNotice,
        private Release $release,
        public string $deliversSaid = '',
        public bool $saysWhatItDelivers = false,
    ) {}

    /**
     * What this row was folded from.
     *
     * Kept so that a confirmation names the release that was *shown*. A screen
     * that rebuilt one from the version string on the row would be agreeing to
     * something assembled after the fact, and the point of the confirmation is
     * that it is about the thing on the screen.
     *
     * Not read by the template, which has the fields above. `private` on the
     * property and reached through here, so a template cannot take it by
     * accident.
     */
    public function release(): Release
    {
        return $this->release;
    }
}
