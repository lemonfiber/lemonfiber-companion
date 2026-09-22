<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function it;

use Modules\Kernel\Api\HowItWasReached;

it('spells both cases the way the contract spells them', function (): void {
    expect(array_map(
        static fn(HowItWasReached $case): string => $case->value,
        HowItWasReached::cases(),
    ))->toBe(['asked', 'by-name']);
});

it('keeps a capability the core resolved apart from a name somebody gave', function (): void {
    // The distinction is whose decision it was, and a plugin may not make the
    // second one. A type with one case would let an instruction an operator
    // gave be rendered as something the stack worked out.
    expect(HowItWasReached::Asked)->not->toBe(HowItWasReached::ByName);
});
