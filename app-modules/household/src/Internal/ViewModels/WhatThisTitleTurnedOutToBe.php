<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/**
 * One title's screen, as a template draws it: the title, from what the poster
 * that opened it handed over, or nothing where it was opened without it.
 *
 * Opened without it is a screen the phone put back after the app was away,
 * with the route and none of what Home handed it. The core answers no reading
 * for one title, so the screen says to open it from Home again rather than
 * drawing a title it cannot name.
 */
final readonly class WhatThisTitleTurnedOutToBe
{
    private function __construct(
        public string $named,
        public ?WhatOnePosterSays $poster,
    ) {}

    /** The title, as the poster that opened it showed it. */
    public static function as(WhatOnePosterSays $poster): self
    {
        return new self($poster->titled, $poster);
    }

    /** Opened without the title, so there is nothing to name. */
    public static function notHandedOver(): self
    {
        return new self('', null);
    }

    /** Whether there is a title to draw. */
    public function isKnown(): bool
    {
        return $this->poster instanceof WhatOnePosterSays;
    }
}
