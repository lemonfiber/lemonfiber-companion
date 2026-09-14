<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal;

use function expect;
use function it;

use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\StackId;
use Modules\Operator\Internal\WhatTheLaunchWas;

use function sprintf;
use function str_repeat;

/**
 * `N1-R37` — the four a launch can be, kept apart on the way to a template.
 *
 * `Launch::either()` requires all four arms, which is the requirement expressed
 * as a signature. This is where those four become fields, and the failure it
 * guards against is the collapse happening here instead: folding three of them
 * into *not locked* would rebuild exactly what the type refuses, one layer down
 * and out of its sight.
 *
 * Only `isLocked` is drawn today. The other three are asserted all the same,
 * because a value nothing reads is a value nothing can tell from any other —
 * and the next screen to want *why the stack could not be reached* should find
 * the answer carried rather than discarded.
 */
it('says the app is shut, and nothing else about a launch that stopped there', function (): void {
    $locked = WhatTheLaunchWas::locked();

    expect($locked->isLocked)->toBeTrue()
        ->and($locked->isPaired)->toBeFalse()
        ->and($locked->met)->toBe('')
        ->and($locked->remedy)->toBe('')
        ->and($locked->opensOn)->toBe('');
});

it('N1-R35 — says no machine is paired, which is not an obstacle', function (): void {
    // A first run rather than a failure to reach. Carrying an obstacle key here
    // would have the app describe its own first launch as broken.
    $unpaired = WhatTheLaunchWas::unpaired();

    expect($unpaired->isLocked)->toBeFalse()
        ->and($unpaired->isPaired)->toBeFalse()
        ->and($unpaired->met)->toBe('')
        ->and($unpaired->remedy)->toBe('')
        ->and($unpaired->opensOn)->toBe('');
});

it('N1-R37 — says what stood in the way, by a key built from the obstacle', function (): void {
    // Every case, because the key is derived rather than listed — so a seventh
    // obstacle needs no edit here, and must not arrive without a catalogue line.
    foreach (Obstacle::cases() as $why) {
        $blocked = WhatTheLaunchWas::blockedBy($why);

        // That the key resolves is `EveryDerivedKeyResolvesTest`'s job — it has
        // the application container and this does not. Here the claim is only
        // that the key is built from the case rather than listed against it.
        expect($blocked->met)->toBe(sprintf('connection.%s', $why->value), $why->value)
            // `N1-R10` asks for both, and they are not the same sentence: what
            // happened is a fact about the world, what to do about it is advice.
            ->and($blocked->remedy)->toBe(sprintf('connection.%s_action', $why->value), $why->value)
            ->and($blocked->isLocked)->toBeFalse($why->value)
            ->and($blocked->isPaired)->toBeTrue($why->value)
            ->and($blocked->opensOn)->toBe('', $why->value);
    }
});

it('N1-R36 — names which machine a ready launch is about', function (): void {
    $stack = StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST)));
    $ready = WhatTheLaunchWas::readyFor($stack);

    expect($ready->opensOn)->toBe($stack->stored())
        ->and($ready->isLocked)->toBeFalse()
        ->and($ready->isPaired)->toBeTrue()
        ->and($ready->met)->toBe('')
        ->and($ready->remedy)->toBe('');
});

it('N1-R37 — tells blocked from unpaired, which both have no stack to open on', function (): void {
    // The pair most easily collapsed: neither opens on a machine, and a screen
    // reading only `opensOn` would draw the same frame for a first run and for
    // a stack that could not be reached.
    expect(WhatTheLaunchWas::unpaired()->isPaired)
        ->not->toBe(WhatTheLaunchWas::blockedBy(Obstacle::StackDidNotAnswer)->isPaired);
});
