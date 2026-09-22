<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function it;

use Modules\Kernel\Api\Cost;

it('says which costs have to be agreed to before they happen', function (): void {
    // Both arms, because the screen branches on this and each answer puts a
    // different thing in front of somebody: one a warning above the control,
    // the other nothing at all. A test naming only the costly arm leaves the
    // cheap one free to become costly without anything saying so — and the
    // failure that produces is a warning on every change, which reads as
    // caution rather than as a defect.
    expect(Cost::Consequential->mustBeAgreedFirst())->toBeTrue()
        ->and(Cost::Cheap->mustBeAgreedFirst())->toBeFalse();
});

it('is the two words the wire has and no others', function (): void {
    // Held to the wire rather than to a list written here. A third cost
    // published by the core arrives as a case this enum does not have, and
    // `mustBeAgreedFirst()` is a `match` with no default — so it raises rather
    // than guessing, which is the behaviour the screen needs.
    expect(array_map(static fn(Cost $cost): string => $cost->value, Cost::cases()))
        ->toBe(['cheap', 'consequential']);
});
