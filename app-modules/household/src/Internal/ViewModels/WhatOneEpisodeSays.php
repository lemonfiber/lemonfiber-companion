<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/** One episode, flattened for a template: its heading, how long it runs, what happens in it, and Play. */
final readonly class WhatOneEpisodeSays
{
    /**
     * @param string                $id            the episode as the core names it, which its Play asks for
     * @param string                $headed        its heading, as a catalogue key: its number and name, or its name alone
     * @param array<string, int|string> $headedFilling what that key is filled with
     * @param string                $runs          how long it runs, as a catalogue key, or empty where unstated
     * @param array<string, int|string> $runsFilling what that key is filled with
     * @param string             $about       what happens in it, or empty
     */
    public function __construct(
        public string $id,
        public string $titled,
        public string $headed,
        public array $headedFilling,
        public string $runs,
        public array $runsFilling,
        public string $about,
        public WhatPlayingSays $playing,
    ) {}
}
