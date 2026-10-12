<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

use Modules\Kernel\Api\Obstacle;

/**
 * What a member is told stood in the way: the core's own words where it wrote any, and the house's otherwise.
 *
 * **The core's words first.** A refusal the core answered carries the
 * sentence and remedy it wrote for the household, and a member is told those
 * as they were written: the core knows what it refused, and this app knows
 * only the code. They are text and never looked up, so
 * {@see isInTheStacksWords()} tells {@see \Modules\Household\View\Components\WhatStoodInTheWay}
 * to draw them as they are rather than through the catalogue.
 *
 * **This app's lines otherwise**, for an obstacle met before the core
 * answered: the obstacle's sentences as the household is told them, which
 * never name the software the house runs or its version.
 */
final readonly class WhatAMemberIsTold
{
    /** The core's sentence, or the catalogue key for what happened. */
    public static function met(Obstacle $why): string
    {
        $told = $why->whatTheHouseholdWasTold();

        return $told->wasSaid() ? $told->sentence() : $why->saidToTheHousehold();
    }

    /** The core's remedy, empty where it gave none, or the catalogue key for what to do about it. */
    public static function remedy(Obstacle $why): string
    {
        $told = $why->whatTheHouseholdWasTold();

        return $told->wasSaid() ? $told->remedy() : $why->remedyForTheHousehold();
    }

    /** Whether what the member is told is the core's text, drawn as it is, rather than keys to translate. */
    public static function isInTheStacksWords(Obstacle $why): bool
    {
        return $why->whatTheHouseholdWasTold()->wasSaid();
    }
}
