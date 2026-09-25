<?php

declare(strict_types=1);

use Modules\Kernel\Api\HowOften;
use Tests\Support\Screens;
use Tests\Support\Tree;

// A screen whose content can change while it is open refreshes on a
// **stated** cadence, and does not rely on the operator leaving and returning
// to see a change.
//
// Two halves, and the second is the one that goes missing. Adding `#[Poll]` to
// a screen takes ten seconds and satisfies the visible half of the requirement;
// saying so on the screen is a catalogue line, a template branch and a number
// that has to match the attribute, and it is the half a reviewer cannot see is
// absent — the screen refreshes, which is what everybody was looking for.
//
// A cadence nobody is told about is worse than none. An operator reading a
// screen that quietly re-reads cannot tell a second-old answer from a
// minute-old one, and whether something has changed is the only reason they are
// looking at it.
//
// So this asks two things of every poll in the application: that its interval
// is one `HowOften` declares, and that the screen carrying it renders a
// cadence. The first is what keeps the attribute and the sentence from drifting
// apart — `#[Poll]` takes a constant expression and a literal there is a number
// no sentence reads.
//
// Read as tokens rather than as prose, in files a generator does not write.

/**
 * What a screen must publish, and a template must call, to state its cadence.
 *
 * Spelled once because it is read against two files. The accessor hands out the
 * `HowOften` case rather than a key and a count, so the screen names the cadence
 * it keeps in one place and the sentence reads what it needs off the case.
 */
const THE_CADENCE_ACCESSOR = 'cadence';

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
 * PHP lets a group carry several — `#[Lazy, Poll(HowOften::WHILE_WORK_RUNS_MS)]`
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

it('N1-R27 — every cadence is one `HowOften` declares, never a number at the attribute', function (): void {
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
            if (str_starts_with($said, 'HowOften::')) {
                continue;
            }

            $written[] = sprintf('%s — #[Poll(%s)]', basename($path), $said);
        }
    }

    expect($written)->toBe([], sprintf(
        "These poll at a number written at the attribute:\n  %s\n\n"
        . '`#[Poll]` takes a constant expression and a literal there is a number no sentence '
        . 'reads, so the screen can state one cadence while keeping another. `HowOften` holds '
        . "each interval once, and the sentence counts on the same constant.\n",
        implode("\n  ", $written),
    ));
});

it('N1-R27 — the reading finds a poll however the attributes were grouped', function (): void {
    // The judgement, handed both spellings. Planting the grouped one would mean
    // rewriting a real screen's attributes for the length of a run, and what
    // would be proven is the same thing this asserts in one line.
    expect(everyPollIntervalIn('#[Poll(HowOften::WHILE_WORK_RUNS_MS)]'))->toBe(['HowOften::WHILE_WORK_RUNS_MS']);
    expect(everyPollIntervalIn('#[Lazy, Poll(HowOften::WHILE_WORK_RUNS_MS)]'))->toBe(['HowOften::WHILE_WORK_RUNS_MS']);
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

/**
 * What a screen that polls fails to say about how often, or nothing where it says it.
 *
 * @param ReflectionClass<object> $screen
 */
function whatAScreenThatPollsLeavesUnsaid(ReflectionClass $screen): string
{
    $path = (string) $screen->getFileName();

    // Asked of the class rather than of its file, because a screen may hold
    // the accessor in a trait — two screens about one reading share the
    // cadence exactly so they cannot state different ones — and a file
    // search would call that silence. It is also the stricter question: a
    // file mentioning `cadence()` in a docblock satisfied the search, and
    // a docblock is where every screen carrying `#[Poll]` explains itself.
    if (! $screen->hasMethod(THE_CADENCE_ACCESSOR)) {
        return sprintf('%s — polls and offers no cadence to render', basename($path));
    }

    // The accessor is only worth having if a template calls it, so the
    // view the screen renders is read too. A screen holding a sentence
    // nothing shows is the same silence one indirection along.
    if (preg_match("/view\\('([a-z]+)::([a-z-]+)'\\)/", (string) file_get_contents($path), $named) !== 1) {
        return sprintf('%s — polls and renders no view this rule can find', basename($path));
    }

    $view = Tree::at(sprintf('app-modules/%s/resources/views/%s.blade.php', $named[1], $named[2]));

    if (! is_file($view) || ! str_contains((string) file_get_contents($view), sprintf('%s()', THE_CADENCE_ACCESSOR))) {
        return sprintf('%s — polls, and %s never says how often', basename($path), basename($view));
    }

    return '';
}

it('N1-R27 — every screen that refreshes says how often', function (): void {
    $silent = [];
    $polling = 0;

    foreach (everyFileThatPolls() as $path) {
        $polling++;

        $screens = theScreensThatPollThrough($path);

        if ($screens === []) {
            $silent[] = sprintf('%s — polls and no screen this rule can find stands behind it', basename($path));

            continue;
        }

        foreach ($screens as $screen) {
            $said = whatAScreenThatPollsLeavesUnsaid($screen);

            if ($said !== '') {
                $silent[] = $said;
            }
        }
    }

    expect($polling)->toBeGreaterThan(0, 'nothing in the application polls, so this rule read nothing');

    expect($silent)->toBe([], sprintf(
        "These refresh without telling anybody how often:\n  %s\n\n"
        . '`N1-R27` asks for a **stated** cadence. A screen that re-reads silently leaves an '
        . 'operator unable to tell a second-old answer from a minute-old one, which is the one '
        . "thing they opened it to find out.\n",
        implode("\n  ", $silent),
    ));
});

it('the cadences this app declares are each a whole number of seconds', function (): void {
    // The sentence counts in seconds, so an interval that is not a whole number
    // of them would be stated as a figure the screen does not keep — 2500
    // milliseconds rendering as *every 2 seconds*. `intdiv` makes that silent,
    // which is why it is asked here rather than left to arithmetic.
    $ragged = [];

    foreach (HowOften::cases() as $often) {
        if ($often->seconds() * 1_000 !== $often->milliseconds()) {
            $ragged[] = sprintf('%s — %dms', $often->name, $often->milliseconds());
        }
    }

    expect($ragged)->toBe([], sprintf(
        "These cadences are not a whole number of seconds:\n  %s\n",
        implode("\n  ", $ragged),
    ));
});
