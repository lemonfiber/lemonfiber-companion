<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function it;

use Modules\Kernel\Api\Severity;

it('reads the four the server sends, worst first', function (): void {
    // The values are the wire's. A rename here would make every refusal
    // unparsable, and the parse is in an adapter where nothing else is
    // looking at these strings.
    //
    // The order is this app's, and it is load-bearing: `isWorseThan` reads the
    // declaration and `WorstFirst` reads that, so moving a case moves rows on a
    // screen. Asserted here so it is a failing test rather than a quiet
    // reordering — the same claim `ConclusionTest` makes about its own cases.
    expect(array_map(
        static fn(Severity $severity): string => $severity->value,
        Severity::cases(),
    ))->toBe(['critical', 'error', 'warning', 'advisory']);
});

it('puts a worse severity before a lesser one, and neither before itself', function (): void {
    expect(Severity::Critical->isWorseThan(Severity::Error))->toBeTrue()
        ->and(Severity::Error->isWorseThan(Severity::Warning))->toBeTrue()
        ->and(Severity::Warning->isWorseThan(Severity::Advisory))->toBeTrue()
        ->and(Severity::Error->isWorseThan(Severity::Critical))->toBeFalse()
        ->and(Severity::Error->isWorseThan(Severity::Error))->toBeFalse();
});
