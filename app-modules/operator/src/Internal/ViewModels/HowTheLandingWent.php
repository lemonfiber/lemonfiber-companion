<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * A change agreed to once the downloads land, as the row it changes draws it.
 *
 * Its own report, apart from the review of whichever setting is open, so a
 * landing that finishes is drawn on the row it changed and on no other.
 */
final readonly class HowTheLandingWent implements SaysWhereTheWorkStands
{
    /**
     * @param WhereTheWorkStands            $stands where the waiting stands, and whether the stack could be asked at all
     * @param string                        $key    the setting the change is for, or nothing where nothing waits
     * @param WhatAChangeTurnedOutToBe|null $came   what the change came to once it was made, or what stood in the way of asking, which takes the place of the review on its row
     * @param string|null                   $said   the key for why the change was not made, where it was not
     */
    public function __construct(
        public WhereTheWorkStands $stands,
        public string $key,
        public ?WhatAChangeTurnedOutToBe $came,
        public ?string $said,
    ) {}

    /** Whether this is the change to that setting, still waiting for the downloads. */
    public function waitsToChange(string $key): bool
    {
        return $this->key === $key && $this->stands->isWorking();
    }

    /** Why the change to that setting was not made, where it was not. */
    public function saidAbout(string $key): ?string
    {
        return $this->key === $key ? $this->said : null;
    }
}
