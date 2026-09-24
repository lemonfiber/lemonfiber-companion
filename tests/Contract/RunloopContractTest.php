<?php

declare(strict_types=1);

use Bootstrap\Composition\NativePHP\Runloop;
use Bootstrap\Composition\NativePHP\TheHarnessInstead;
use Bootstrap\Composition\NativePHP\TheRunloop;
use Modules\Operator\Internal\Screens\YourStacks;
use Tests\Support\Fakes\ARunloopThatOnlyRemembers;

// Runs the composition root rather than reading it, so its mutants are judged
// here: see `scripts/mutation.php`.
pest()->group('holds:bootstrap/Composition');

// The Runloop contract, run against the harness and against the fake.
//
// `G2`'s shape at the seam the composition root keeps so that exactly one file
// in this repository cannot be covered. Three implementations answer it:
// `TheHarnessInstead` is what a machine with no device gets, `TheRunloop` is
// what a handset enters, and `ARunloopThatOnlyRemembers` is what
// `ScreenRoutesReplaceTheVendorsTest` hands the route closure.
//
// **`TheRunloop` is named here and not entered.** It calls
// `NativeRouter::start()`, which blocks against the real bridge — with a live
// session that is a minute and a half of reconnect spinning per request — so a
// contract that ran it would hang rather than fail, which is the worst way for
// a test to go wrong. What can be asserted about it without entering it is how
// much is out of reach, and that is the last test in this file.
//
// What the other two must both promise is small and was not true of both. A
// runloop is handed *how to build a screen* rather than a router, and the
// reason the harness gives for building it — a route naming a screen that
// cannot be constructed would otherwise answer 200 to every smoke test and
// fail on a handset — is a reason about the port rather than about the
// harness. The fake never built anything, so three tests drove a route to a
// screen and none of them would have noticed that the screen could not be made.

/** The screen every assertion here asks a runloop to run. */
const A_SCREEN_TO_RUN = YourStacks::class;

/**
 * Every implementation a suite can actually enter.
 *
 * A plain function rather than a Pest dataset, matching the other contract
 * suites: a dataset whose value is a closure is resolved by Pest and handed
 * back as one argument, so a pair returns as an array where the test wanted two
 * parameters.
 *
 * @return array<string, Runloop>
 */
function everyRunloopThatCanBeEntered(): array
{
    return [
        'the harness' => new TheHarnessInstead(),
        'the fake' => new ARunloopThatOnlyRemembers(),
    ];
}

/**
 * The screens one runloop built when it was asked to run this one.
 *
 * The builder answers from its argument rather than ignoring it, so a runloop
 * that called it with the wrong name is told apart from one that called it with
 * the right one — and a runloop that never called it at all comes back empty.
 *
 * Named for this file: the root suites share one namespace (`G10`).
 *
 * @return list<string>
 */
function whatARunloopBuilt(Runloop $runloop, string $screen): array
{
    /** @var ArrayObject<int, string> $built */
    $built = new ArrayObject();

    $runloop->enter(static function (string $named) use ($built): string {
        $built[] = $named;

        return sprintf('a screen called %s', $named);
    }, $screen, [], '/');

    return array_values($built->getArrayCopy());
}

/** What one runloop handed back to the route that entered it. */
function whatARunloopAnswered(Runloop $runloop, string $screen): mixed
{
    return $runloop->enter(
        static fn(string $named): string => sprintf('a screen called %s', $named),
        $screen,
        [],
        '/',
    );
}

it('builds the screen it was given', function (): void {
    // The promise the harness carries the argument for and the fake did not
    // keep. A route naming a screen this application cannot construct — a typo,
    // a port with no binding — answers 200 to anything that does not build it,
    // and fails on a handset where somebody is looking at it.
    foreach (everyRunloopThatCanBeEntered() as $which => $runloop) {
        expect(whatARunloopBuilt($runloop, A_SCREEN_TO_RUN))->toBe([A_SCREEN_TO_RUN], $which);
    }
});

it('builds that screen once', function (): void {
    // One screen, built once. A runloop that built twice would construct a
    // screen — and everything the container hands it — for a frame nobody
    // asked for, and nothing about the answer would say so.
    foreach (everyRunloopThatCanBeEntered() as $which => $runloop) {
        expect(whatARunloopBuilt($runloop, A_SCREEN_TO_RUN))->toHaveCount(1, $which);
    }
});

it('answers the route with something rather than nothing', function (): void {
    // What comes back is what the route closure hands Laravel, and the three
    // answers are deliberately different: a redirect or an empty string from a
    // device, a sentence saying where to test the screen from the harness,
    // an empty string from the fake. Nothing is the one answer none of them may
    // give — a route closure returning null renders an empty 200, which is what
    // a passing smoke test looks like when the route is wrong.
    foreach (everyRunloopThatCanBeEntered() as $which => $runloop) {
        expect(whatARunloopAnswered($runloop, A_SCREEN_TO_RUN))->not->toBeNull($which);
    }
});

it('holds the one implementation it cannot enter to a single method', function (): void {
    // `TheRunloop` is the one file in this repository excluded from the
    // coverage floor, and the argument for excluding it is that everything
    // which decides anything sits on this side of the seam. That argument is
    // only as good as the file staying small: a second method there is a
    // second thing no test reaches, arriving without anybody deciding it
    // should.
    //
    // Asserted rather than trusted, because it is the one implementation of
    // this port the assertions above cannot be run against.
    expect(get_class_methods(TheRunloop::class))->toBe(['enter']);
});
