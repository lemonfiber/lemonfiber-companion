<?php

declare(strict_types=1);

use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\FollowsAJob;
use Modules\Operator\Internal\LooksAgainWhileItMoves;
use Modules\Operator\Internal\Screens\WatchingOneArrive;
use Modules\Operator\Internal\Screens\WhatThisStackRuns;
use Modules\Operator\Internal\Screens\WhatToDoWithThis;
use Modules\Operator\Internal\ShowsWhatTheYesWillRun;
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

/**
 * Whether a screen, read from this source, follows its job other than through FollowsAJob, or names a job itself.
 *
 * @param class-string $screen
 */
function followsItsJobApart(string $screen, string $source): bool
{
    return ! in_array(FollowsAJob::class, class_uses_recursive($screen), strict: true) || str_contains($source, 'Job::named(');
}

/**
 * The source of a screen and of every trait it takes, since a job a trait names is one the screen names.
 *
 * @param ReflectionClass<covariant object> $screen
 */
function whatItIsWrittenIn(ReflectionClass $screen): string
{
    return implode("\n", array_map(
        static fn(ReflectionClass $written): string => (string) file_get_contents((string) $written->getFileName()),
        [$screen, ...Screens::traitsOf($screen)],
    ));
}

/**
 * Every screen awaiting an outcome, and those of them that follow their job some other way, or name a job themselves.
 *
 * @return array{awaiting: list<string>, apart: list<string>}
 */
function whoFollowsTheirJobApart(): array
{
    $awaiting = [];
    $apart = [];

    foreach (Screens::byTheViewTheyRender() as $screen) {
        if (! $screen->implementsInterface(AwaitsAnOutcome::class)) {
            continue;
        }

        $awaiting[] = $screen->getName();

        if (followsItsJobApart($screen->getName(), whatItIsWrittenIn($screen))) {
            $apart[] = $screen->getShortName();
        }
    }

    return ['awaiting' => $awaiting, 'apart' => $apart];
}

it('follows the job of every screen awaiting an outcome through the shared home', function (): void {
    ['awaiting' => $awaiting, 'apart' => $apart] = whoFollowsTheirJobApart();

    expect($awaiting)->not->toBe([], 'no screen awaits an outcome, so this rule read nothing')
        ->and($apart)->toBe([], sprintf(
            "These await an outcome and follow their job apart from FollowsAJob, or name a job themselves:\n  %s\n\n"
            . 'Follow it through FollowsOneJobAtATime or FollowsAJob, and take the job from the stack\'s answer.',
            implode("\n  ", $apart),
        ));
});

it('would catch a screen that follows its job through FollowsAJob and holds a job by its name too', function (): void {
    $source = (string) file_get_contents((string) new ReflectionClass(WatchingOneArrive::class)->getFileName());
    $holding = str_replace('    public function walk(): void', "    private function held(): Job\n    {\n        return Job::named('a-walk');\n    }\n\n    public function walk(): void", $source);

    expect($holding)->not->toBe($source)
        ->and(followsItsJobApart(WatchingOneArrive::class, $source))->toBeFalse()
        ->and(followsItsJobApart(WatchingOneArrive::class, $holding))->toBeTrue();
});

it('reads the source of every trait a screen takes, beside its own', function (): void {
    $screen = new ReflectionClass(WhatToDoWithThis::class);
    $written = whatItIsWrittenIn($screen);

    expect($written)->toContain((string) file_get_contents((string) $screen->getFileName()))
        ->and($written)->toContain((string) file_get_contents((string) new ReflectionClass(ShowsWhatTheYesWillRun::class)->getFileName()))
        ->and(followsItsJobApart(WhatToDoWithThis::class, $written))->toBeFalse();
});
