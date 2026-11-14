<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/** One music or book ask of a member's, as its page draws it: its lettered poster, its name, its kind, where it stands, and where to find it once it is here. */
final readonly class WhatYourAskSays
{
    /**
     * @param string|ALineAsShown $title    its name in the core's words, or the line for its kind where nothing named it
     * @param string              $kind     what kind of thing it is, as a key in the member's words
     * @param string              $standing where it stands, as a key in the member's words, drawn as the action and not usable
     * @param string              $foundIn  where in the house to find it, as a key, or empty where it has not arrived
     */
    public function __construct(
        public WhatOnePosterSays $poster,
        public string|ALineAsShown $title,
        public string $kind,
        public string $standing,
        public string $foundIn,
    ) {}

    /** Whether it has arrived, so there is a line saying where to find it. */
    public function saysWhereToFindIt(): bool
    {
        return $this->foundIn !== '';
    }
}
