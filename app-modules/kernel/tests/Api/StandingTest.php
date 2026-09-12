<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function it;

use Modules\Kernel\Api\Standing;

it('reads the five the server sends', function (): void {
    expect(array_map(
        static fn(Standing $standing): string => $standing->value,
        Standing::cases(),
    ))->toBe(['actionable', 'guided', 'remediable', 'unknown', 'suppressed']);
});

it('offers a button where something here can act', function (): void {
    expect(Standing::Actionable->offersAButton())->toBeTrue()
        ->and(Standing::Remediable->offersAButton())->toBeTrue();
});

it('offers no button where the operator has to act elsewhere', function (): void {
    // The distinction this enum exists for. A remedy that reads as an
    // instruction — check the router, plug the drive back in — is not one this
    // application can carry out, and a button claiming otherwise fails in
    // front of somebody who then has to work out what actually happened.
    expect(Standing::Guided->offersAButton())->toBeFalse();
});

it('offers no button where there is no remedy and none where it is silenced', function (): void {
    expect(Standing::Unknown->offersAButton())->toBeFalse()
        ->and(Standing::Suppressed->offersAButton())->toBeFalse();
});
