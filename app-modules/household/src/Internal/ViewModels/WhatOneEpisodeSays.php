<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/** One episode, flattened for a template: its heading, how long it runs, what happens in it, and Play. */
final readonly class WhatOneEpisodeSays
{
    /**
     * @param string            $id     the episode as the core names it, which its Play asks for
     * @param ALineAsShown      $headed its heading: its number and name, or its name alone
     * @param ALineAsShown|null $runs   how long it runs, or null where unstated
     * @param string            $about  what happens in it, or empty
     */
    public function __construct(
        public string $id,
        public string $titled,
        public ALineAsShown $headed,
        public ?ALineAsShown $runs,
        public string $about,
        public WhatPlayingSays $playing,
    ) {}
}
