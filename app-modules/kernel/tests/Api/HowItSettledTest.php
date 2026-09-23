<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function it;

use Modules\Kernel\Api\HowItSettled;

it('spells each case the way the contract spells it', function (): void {
    // The backing values are the wire's, and the adapter maps onto them with
    // `tryFrom`. A case respelled here is a word the core sends that nothing
    // reads, which is the drift this assertion exists to catch — the one
    // failure that would otherwise show up as a blank screen for one arm.
    expect(array_map(
        static fn(HowItSettled $case): string => $case->value,
        HowItSettled::cases(),
    ))->toBe(['outright', 'each', 'contested', 'chosen', 'unfilled']);
});

it('has a case for a contest, because the core declines to settle one', function (): void {
    // Named on its own rather than left to the list above: this is the case
    // the surface exists for, and a set that lost it would still pass a test
    // about the other four.
    expect(HowItSettled::tryFrom('contested'))->toBe(HowItSettled::Contested);
});

it('does not read a word the contract does not carry', function (): void {
    expect(HowItSettled::tryFrom('resolved'))->toBeNull();
});
