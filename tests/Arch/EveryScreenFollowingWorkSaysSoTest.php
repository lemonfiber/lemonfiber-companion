<?php

declare(strict_types=1);

use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\LooksAgainWhileItMoves;
use Modules\Operator\Internal\Screens\GuardingWhileYouWatch;
use Modules\Operator\Internal\Screens\WhatThisStackRuns;
use Native\Mobile\Attributes\Poll;
use Tests\Support\Screens;

// A screen that follows work it sent says whether it still awaits the outcome.
//
// The lock asks, when it stands again over a screen, whether that screen is
// waiting on something it sent: the device then does not raise its prompt by
// itself. A screen that follows work and cannot be asked would have the prompt
// raised over the action in flight. The sign that a screen follows work is the
// cadence it keeps while work runs.

/**
 * Screens that keep the cadence and send nothing whose outcome they await,
 * each with why. A register that may shrink and may not grow.
 */
const IT_KEEPS_THE_CADENCE_AND_AWAITS_NOTHING = [
    // A guard runs until the operator stops it, so there is no outcome to await.
    GuardingWhileYouWatch::class,
    // It sends nothing; its services settle by themselves.
    WhatThisStackRuns::class,
];

/**
 * Whether a screen keeps the cadence work runs at.
 *
 * Looking again at what moves on its own is as frequent and follows nothing,
 * so the method that does it is left out by name.
 *
 * @param ReflectionClass<object> $screen
 */
function keepsTheCadenceWorkRunsAt(ReflectionClass $screen): bool
{
    $moving = new ReflectionClass(LooksAgainWhileItMoves::class);

    foreach ($screen->getMethods() as $method) {
        if ($moving->hasMethod($method->getName())) {
            continue;
        }

        foreach ($method->getAttributes(Poll::class) as $poll) {
            if ($poll->newInstance()->ms === HowOftenAScreenLooks::WHILE_WORK_RUNS_MS) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Every screen keeping the cadence work runs at, and those of them nobody can ask.
 *
 * Named functions because reading an attribute raises, and the analyser
 * refuses a checked exception inside a closure.
 *
 * @return array{following: list<string>, silent: list<string>}
 */
function whoFollowsWork(): array
{
    $following = [];
    $silent = [];

    foreach (Screens::byTheViewTheyRender() as $screen) {
        if (! keepsTheCadenceWorkRunsAt($screen)) {
            continue;
        }

        $following[] = $screen->getName();

        if (! $screen->implementsInterface(AwaitsAnOutcome::class)
            && ! in_array($screen->getName(), IT_KEEPS_THE_CADENCE_AND_AWAITS_NOTHING, strict: true)) {
            $silent[] = $screen->getName();
        }
    }

    return ['following' => $following, 'silent' => $silent];
}

it('asks every screen that follows work whether it still awaits the outcome', function (): void {
    ['following' => $following, 'silent' => $silent] = whoFollowsWork();

    expect($following)->not->toBe([], 'no screen keeps the cadence work runs at, so this rule read nothing')
        ->and(array_values(array_diff(IT_KEEPS_THE_CADENCE_AND_AWAITS_NOTHING, $following)))->toBe([])
        ->and($silent)->toBe([], sprintf(
            "These follow work and cannot be asked whether they still await it:\n  %s\n\n"
            . 'Implement AwaitsAnOutcome, answered from what the screen last heard.',
            implode("\n  ", $silent),
        ));
});
