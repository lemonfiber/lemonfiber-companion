<?php

declare(strict_types=1);

use Modules\Kernel\Api\HowOftenAScreenLooks;
use Tests\Support\Screens;
use Tests\Support\Tree;

// A screen whose content can change while it is open refreshes on a cadence
// it **declares**, and does not rely on the operator leaving and returning to
// see a change.
//
// Declared, not shown. The cadence is machinery: a line saying how often a
// screen looks tells an operator nothing about whether what they are looking at
// is current, and the age a stale reading carries already does. So this asks
// two things of every poll in the application: that its interval is one
// `HowOftenAScreenLooks` declares, where this can read it, and that a screen stands behind
// it. And one thing of every screen: that it hands a template no cadence to
// print.
//
// Read as tokens rather than as prose, in files a generator does not write.

/**
 * Every source file in the application that declares a poll.
 *
 * @return list<string>
 */
function everyFileThatPolls(): array
{
    $found = [];

    $sources = [
        ...Tree::filesUnder(Tree::at('app-modules'), '.php'),
        ...Tree::filesUnder(Tree::at('bootstrap'), '.php'),
    ];

    foreach ($sources as $path) {
        if (str_contains($path, '/tests/')) {
            continue;
        }

        if (str_contains((string) file_get_contents($path), '#[Poll(')) {
            $found[] = $path;
        }
    }

    return $found;
}

/**
 * What each `#[Poll]` in a source was given, in the order they are written.
 *
 * The attribute is found inside the brackets rather than as the whole of them.
 * PHP lets a group carry several — `#[Lazy, Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]`
 * is one `#[` and two attributes — and an expression that required `)]` to
 * follow the arguments reads that as no poll at all. The file still counts as
 * one that polls, so the rule below examines it, finds no interval to judge and
 * passes: the one half a number could break, gone, on a screen that
 * is visibly refreshing.
 *
 * @return list<string>
 */
function everyPollIntervalIn(string $source): array
{
    preg_match_all('/#\[[^\]]*?\bPoll\(([^)]*)\)/', $source, $found);

    return array_map(trim(...), $found[1]);
}

it('every cadence is one `HowOftenAScreenLooks` declares, never a number at the attribute', function (): void {
    $written = [];

    foreach (everyFileThatPolls() as $path) {
        $intervals = everyPollIntervalIn((string) file_get_contents($path));

        // A file that declares a poll and hands this nothing to read is a
        // finding rather than a pass. It is how the rule went quiet before:
        // silence here is indistinguishable from a cadence that is correct.
        if ($intervals === []) {
            $written[] = sprintf('%s — declares a poll whose interval this rule cannot read', basename($path));

            continue;
        }

        foreach ($intervals as $said) {
            if (str_starts_with($said, 'HowOftenAScreenLooks::')) {
                continue;
            }

            $written[] = sprintf('%s — #[Poll(%s)]', basename($path), $said);
        }
    }

    expect($written)->toBe([], sprintf(
        "These poll at a number written at the attribute:\n  %s\n\n"
        . '`#[Poll]` takes a constant expression, and a literal there is a cadence no test '
        . "can read. `HowOftenAScreenLooks` holds each interval once.\n",
        implode("\n  ", $written),
    ));
});

it('the reading finds a poll however the attributes were grouped', function (): void {
    // The judgement, handed both spellings. Planting the grouped one would mean
    // rewriting a real screen's attributes for the length of a run, and what
    // would be proven is the same thing this asserts in one line.
    expect(everyPollIntervalIn('#[Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]'))->toBe(['HowOftenAScreenLooks::WHILE_WORK_RUNS_MS']);
    expect(everyPollIntervalIn('#[Lazy, Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]'))->toBe(['HowOftenAScreenLooks::WHILE_WORK_RUNS_MS']);
    expect(everyPollIntervalIn('#[Poll(30_000), Lazy]'))->toBe(['30_000']);

    // And a mention of the attribute is not a use of it: every screen that
    // carries one explains it in a docblock first, and a docblock is where a
    // reading that matched the bare name would find its subjects.
    expect(everyPollIntervalIn(' * **`#[Poll]`** because the answer moves while it is open.'))->toBe([]);
});

/**
 * The screens a file that polls stands for.
 *
 * A screen's own file stands for itself. A trait carrying `#[Poll]` stands
 * for every screen that uses it, since each of those is what re-reads and
 * each must say how often; a trait no screen uses stands for nothing, and is
 * then reported as silent.
 *
 * Matched by file rather than by name, so a screen renamed is a screen this
 * still follows — and a file with no class behind it answers *nothing*, which
 * is the safe way round: something polling that this cannot identify is
 * exactly what wants a human to look.
 *
 * @return list<ReflectionClass<object>>
 */
function theScreensThatPollThrough(string $path): array
{
    $screens = [];

    foreach (Screens::all() as $screen) {
        $traits = array_map(static fn(ReflectionClass $trait): string|false => $trait->getFileName(), $screen->getTraits());

        if ($screen->getFileName() === $path || in_array($path, $traits, strict: true)) {
            $screens[] = $screen;
        }
    }

    return $screens;
}

it('every poll is declared by a screen', function (): void {
    $unclaimed = [];
    $polling = 0;

    foreach (everyFileThatPolls() as $path) {
        $polling++;

        if (theScreensThatPollThrough($path) === []) {
            $unclaimed[] = sprintf('%s — polls and no screen this rule can find stands behind it', basename($path));
        }
    }

    expect($polling)->toBeGreaterThan(0, 'nothing in the application polls, so this rule read nothing');

    expect($unclaimed)->toBe([], sprintf(
        "These poll with no screen behind them:\n  %s\n",
        implode("\n  ", $unclaimed),
    ));
});

it('no screen hands a template a cadence to show', function (): void {
    $shown = [];

    foreach (Screens::all() as $screen) {
        foreach ($screen->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $type = $method->getReturnType();

            if ($type instanceof ReflectionNamedType && $type->getName() === HowOftenAScreenLooks::class) {
                $shown[] = sprintf('%s::%s()', $screen->getShortName(), $method->getName());
            }
        }
    }

    foreach (Tree::filesUnder(Tree::at('app-modules'), '.blade.php') as $view) {
        if (str_contains((string) file_get_contents($view), 'HowOftenAScreenLooks')) {
            $shown[] = basename($view);
        }
    }

    expect($shown)->toBe([], sprintf(
        "These hand a cadence to what the operator sees:\n  %s\n\n"
        . '`N1-R27` asks for a cadence the screen declares and does not show. What tells an '
        . "operator whether a reading is current is the age it carries once it is not.\n",
        implode("\n  ", $shown),
    ));
});

it('the cadences this app declares are each a whole number of seconds', function (): void {
    // A wait is measured in seconds, so an interval that is not a whole number
    // of them would be a wait shorter than the one declared — 2500
    // milliseconds waiting as two seconds. `intdiv` makes that silent, which
    // is why it is asked here rather than left to arithmetic.
    $ragged = [];

    foreach (HowOftenAScreenLooks::cases() as $often) {
        if ($often->seconds() * 1_000 !== $often->milliseconds()) {
            $ragged[] = sprintf('%s — %dms', $often->name, $often->milliseconds());
        }
    }

    expect($ragged)->toBe([], sprintf(
        "These cadences are not a whole number of seconds:\n  %s\n",
        implode("\n  ", $ragged),
    ));
});
