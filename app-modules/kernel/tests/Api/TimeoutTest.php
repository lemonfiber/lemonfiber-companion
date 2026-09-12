<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ReachWaitsTooLong;
use Modules\Kernel\Api\Timeout;

it('N1-R26 — carries a bound, in the unit a person feels', function (): void {
    expect(Timeout::of(5)->inSeconds())->toBe(5);
    expect(Timeout::ordinary()->inSeconds())->toBe(Timeout::CEILING);
});

it('N1-R26 — the ceiling and the floor are both allowed, which is what makes them bounds', function (): void {
    // The boundary, asserted from `of()` rather than from `ordinary()`, which
    // constructs directly and so proves nothing about the comparison. Mutation
    // testing found this: `> CEILING` and `>= CEILING` behave identically until
    // something asks for exactly ten, and a bound that refuses its own stated
    // maximum is an off-by-one nobody reads in a docblock.
    expect(Timeout::of(Timeout::CEILING)->inSeconds())->toBe(Timeout::CEILING);
    expect(Timeout::of(Timeout::FLOOR)->inSeconds())->toBe(Timeout::FLOOR);
});

it('N1-R26 — the bound is not raised to accommodate a slow stack', function (): void {
    // The clause that needs a type. Carrying a bound is a habit and habits
    // hold; raising one is a decision, and it arrives at three in the morning
    // while somebody is debugging a stack that takes eleven seconds to answer,
    // where raising the number makes the symptom go away.
    expect(fn(): Timeout => Timeout::of(Timeout::CEILING + 1))
        ->toThrow(ReachWaitsTooLong::class, 'must not be raised');
});

it('refuses a bound so short a healthy stack loses', function (): void {
    // The overcorrection, which is a different mistake and gets a different
    // sentence: an app that reports a working stack as unreachable is worse
    // than one that waits.
    expect(fn(): Timeout => Timeout::of(Timeout::FLOOR - 1))
        ->toThrow(ReachWaitsTooLong::class, 'at least');
});

it('N1-R26 — the ceiling is ten seconds, and moving it fails this test on purpose', function (): void {
    // Pinned rather than derived, and this is the one place in the repository
    // where restating a constant is the assertion rather than a duplicate of
    // it. Nothing can stop somebody editing a `const`. What this does is make
    // the edit fail a test whose name says why the number is what it is, so the
    // change is a decision taken in front of the requirement instead of a digit
    // altered while looking at something else.
    //
    // If the number should move, move it here too — and say in the commit
    // message what changed about the operator, because nothing about the stack
    // is a reason.
    expect(Timeout::CEILING)->toBe(10);
    expect(Timeout::FLOOR)->toBe(1);
});

it('is the same bound, and is not another', function (): void {
    expect(Timeout::of(5)->is(Timeout::of(5)))->toBeTrue();
    expect(Timeout::of(5)->is(Timeout::ordinary()))->toBeFalse();
});
