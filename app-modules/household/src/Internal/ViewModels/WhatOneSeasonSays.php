<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/** One season, flattened for a template: what it is called and its episodes in order. */
final readonly class WhatOneSeasonSays
{
    /** @param list<WhatOneEpisodeSays> $episodes */
    public function __construct(public string $named, public array $episodes) {}
}
