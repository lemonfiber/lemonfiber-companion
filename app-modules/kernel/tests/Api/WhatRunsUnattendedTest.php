<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\HowItIsHosted;
use Modules\Kernel\Api\InstructionSaysNothing;
use Modules\Kernel\Api\Unattended;
use Modules\Kernel\Api\WhatDidNotComeBack;
use Modules\Kernel\Api\WhatKeepsItRunning;
use Modules\Kernel\Api\WhatRunsUnattended;

/** One word carried out of whichever arm a listing takes. */
final readonly class WhatTheMachineSaidAboutKeepingThings
{
    public function __construct(public string $said) {}
}

/**
 * What to do instead, or the word for the machine doing it itself.
 *
 * Named for this file (`G10`), and read through the fold rather than off a
 * getter for {@see whatIsGoneFrom}'s reason: what is owed is about what reaches
 * a screen, and a listing that held the sentence and handed over nothing would
 * draw the same empty box it draws for *off*.
 */
function whatTheMachineSays(WhatRunsUnattended $running): string
{
    return $running->whereItCannot(
        instead: static fn(string $what): WhatTheMachineSaidAboutKeepingThings
            => new WhatTheMachineSaidAboutKeepingThings($what),
        itself: static fn(): WhatTheMachineSaidAboutKeepingThings
            => new WhatTheMachineSaidAboutKeepingThings('the machine does this itself'),
    )->said;
}

/**
 * The names of what did not come back, which is what a screen draws.
 *
 * A list, because the assertions below read it by position. The collection is
 * walked rather than counted: a count agrees with itself whichever rows it
 * holds, and the rows are the claim.
 *
 * @return list<string>
 */
function theNamesThatDidNotComeBack(WhatRunsUnattended $running): array
{
    $names = [];

    foreach ($running->didNotComeBack() as $command) {
        $names[] = $command->name();
    }

    return $names;
}

/** One command, named, at whatever standing a case wants. */
function aCommandCalled(string $name, HowItIsHosted $standing): Unattended
{
    return Unattended::called($name, 'lemonfiber watch', 'Files are noticed', $standing);
}

it('N16-R5 — a machine with a manager says the machine does this itself', function (): void {
    $running = WhatRunsUnattended::keptBy(WhatKeepsItRunning::Launchd, aCommandCalled('A', HowItIsHosted::Hosted));

    expect($running->whatKeepsThem())->toBe(WhatKeepsItRunning::Launchd)
        ->and(whatTheMachineSays($running))->toBe('the machine does this itself');
});

it('N16-R5 — a machine with no manager carries what to do instead', function (): void {
    // The whole of *say so rather than rendering it as off*. A screen reaching
    // this arm has a sentence to draw and no control; one reaching the other
    // has a machine that already does this.
    $running = WhatRunsUnattended::unsupported('Start it from your own login items.');

    expect($running->whatKeepsThem())->toBe(WhatKeepsItRunning::Unsupported)
        ->and(whatTheMachineSays($running))->toBe('Start it from your own login items.');
});

it('N16-R5 — an unsupported machine is `unsupported` by construction', function (): void {
    // The keeper is not a parameter, which makes two mistakes unspellable at
    // once: an unsupported machine with no instruction, and an instruction
    // attached to a machine that has a manager — telling an operator to go and
    // do by hand what the product already did.
    expect(WhatRunsUnattended::unsupported('Do it yourself.')->whatKeepsThem())
        ->toBe(WhatKeepsItRunning::Unsupported);
});

it('N16-R5 — an unsupported machine that says nothing to do instead is refused', function (): void {
    // *Not available here* with no sentence beside it is the empty box that
    // reads as *off*, which is the reading the requirement exists to prevent.
    expect(fn(): WhatRunsUnattended => WhatRunsUnattended::unsupported('   '))
        ->toThrow(InstructionSaysNothing::class);
});

it('N16-R6 — names what did not come back, and only that', function (): void {
    // `Orphaned` is in and `NotHosted` is not: nothing was installed for the
    // second, so nothing failed to start, and listing it would invent a failure.
    $running = WhatRunsUnattended::keptBy(
        WhatKeepsItRunning::Systemd,
        aCommandCalled('Running', HowItIsHosted::Hosted),
        aCommandCalled('Stopped', HowItIsHosted::Stopped),
        aCommandCalled('Orphan', HowItIsHosted::Orphaned),
        aCommandCalled('Terminal only', HowItIsHosted::NotHosted),
        aCommandCalled('Unconfirmed', HowItIsHosted::InstalledUnverified),
    );

    expect(theNamesThatDidNotComeBack($running))->toBe(['Stopped', 'Orphan'])
        ->and($running->count())->toBe(5);
});

it('N16-R6 — a machine where everything came back names nothing', function (): void {
    $running = WhatRunsUnattended::keptBy(WhatKeepsItRunning::Launchd, aCommandCalled('A', HowItIsHosted::Hosted));

    expect(theNamesThatDidNotComeBack($running))->toBe([]);
});

it('a machine that hosts nothing is a state rather than a gap', function (): void {
    // It still says what keeps things running. A machine nobody has installed
    // anything on has a manager all the same, and a listing that refused to
    // exist without a row would make the ordinary case unspellable.
    $running = WhatRunsUnattended::keptBy(WhatKeepsItRunning::Launchd);

    expect($running->count())->toBe(0)
        ->and(iterator_to_array($running, preserve_keys: false))->toBe([]);
});

it('hands the commands out as a list rather than whatever keys a variadic brought', function (): void {
    // A variadic collected from named arguments has string keys, and everything
    // reading this reads it by position.
    $running = WhatRunsUnattended::keptBy(
        WhatKeepsItRunning::Systemd,
        aCommandCalled('First', HowItIsHosted::Hosted),
        aCommandCalled('Second', HowItIsHosted::Stopped),
    );

    expect(array_keys(iterator_to_array($running, preserve_keys: true)))->toBe([0, 1]);
});

it('what did not come back is a list rather than whatever keys the walk left', function (): void {
    // The listing builds it with a spread, and a spread over an array with
    // gaps in its keys is the shape `array_values` exists for. Read by
    // position everywhere, so a gap is a row nothing draws.
    $running = WhatRunsUnattended::keptBy(
        WhatKeepsItRunning::Systemd,
        aCommandCalled('Running', HowItIsHosted::Hosted),
        aCommandCalled('Stopped', HowItIsHosted::Stopped),
        aCommandCalled('Orphan', HowItIsHosted::Orphaned),
    );

    $missing = $running->didNotComeBack();

    expect(array_keys(iterator_to_array($missing, preserve_keys: true)))->toBe([0, 1])
        ->and($missing->count())->toBe(2);
});

it('an unsupported machine still lists what it has, which is the point of it', function (): void {
    // The rows are what make *not available here* worth reading: an operator
    // learns which commands would have survived a reboot on a machine that
    // could do it.
    $running = WhatRunsUnattended::unsupported(
        'Start it from your own login items.',
        aCommandCalled('Watching', HowItIsHosted::Unsupported),
    );

    expect($running->count())->toBe(1)
        ->and(theNamesThatDidNotComeBack($running))->toBe([]);
});

// Three constructors reindex a variadic, and each is pinned the same way
// `Effects::of()` is: called with named arguments rather than positionally.
//
// Positionally the reindex cannot be observed — a variadic collected from
// positional arguments is already a list, so an assertion about its keys holds
// with the reindex and without it. Spread a string-keyed array and PHP passes
// them as named arguments, which a variadic collects *under those names*. That
// is the one call shape where dropping the reindex changes the value, and the
// shape a listing built by spreading a walk actually takes.
//
// Everything downstream reads these by position, so a string key is a row
// nothing draws.

it('keptBy survives named arguments without gaining string keys', function (): void {
    $running = WhatRunsUnattended::keptBy(WhatKeepsItRunning::Launchd, ...[
        'first' => aCommandCalled('Watching', HowItIsHosted::Hosted),
        'second' => aCommandCalled('Seeding', HowItIsHosted::Stopped),
    ]);

    expect(array_keys(iterator_to_array($running, preserve_keys: true)))->toBe([0, 1]);
});

it('unsupported survives named arguments without gaining string keys', function (): void {
    $running = WhatRunsUnattended::unsupported('Add it to your own login items.', ...[
        'first' => aCommandCalled('Watching', HowItIsHosted::Unsupported),
        'second' => aCommandCalled('Seeding', HowItIsHosted::Unsupported),
    ]);

    expect(array_keys(iterator_to_array($running, preserve_keys: true)))->toBe([0, 1]);
});

it('what did not come back survives named arguments without gaining string keys', function (): void {
    // Reached directly rather than through `didNotComeBack()`, which builds its
    // own list and so cannot hand this one string keys. The constructor is
    // public, so the call this guards against is one somebody can make.
    $missing = WhatDidNotComeBack::these(...[
        'first' => aCommandCalled('Seeding', HowItIsHosted::Orphaned),
        'second' => aCommandCalled('Watching', HowItIsHosted::Stopped),
    ]);

    expect(array_keys(iterator_to_array($missing, preserve_keys: true)))->toBe([0, 1]);
});
