<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;

/**
 * What an obstacle's sentences are filled with, by the names the catalogue gives them.
 *
 * The obstacle holds its facts typed; this is their shape for the translator,
 * made where a sentence is drawn. Two versions that disagree are the only
 * facts an obstacle names, and every other kind fills nothing in.
 */
final readonly class WhatAnObstacleNames
{
    /** @return array<string, int> */
    public static function in(Obstacle $why): array
    {
        if (! $why->is(KindOfObstacle::VersionsDisagree)) {
            return [];
        }

        return ['answered' => $why->versionsSpoken()->answered(), 'spoken' => $why->versionsSpoken()->spoken()];
    }
}
