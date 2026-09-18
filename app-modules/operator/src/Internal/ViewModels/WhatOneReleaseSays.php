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
 */
final readonly class WhatOneReleaseSays
{
    public function __construct(
        public string $version,
        public bool $theHouseholdWouldNotice,
        private Release $release,
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
