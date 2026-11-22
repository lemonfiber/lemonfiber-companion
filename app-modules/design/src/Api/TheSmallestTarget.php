<?php

declare(strict_types=1);

namespace Modules\Design\Api;

/**
 * The smallest target a finger can hit, in points: Android's 48 over iOS's 44, the larger of the two platform minima.
 */
final readonly class TheSmallestTarget
{
    /** The points it takes, across and down. */
    public const int POINTS = 48;
}
