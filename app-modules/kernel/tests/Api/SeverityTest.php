<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function it;

use Modules\Kernel\Api\Severity;

it('G4-R2 — reads the four the server sends, worst first', function (): void {
    // `G4-R2` is that errors use exactly the four defined levels, and this is
    // where *exactly* is kept: the enum could grow a fifth without anything
    // else in the repository objecting, and a fifth level is a severity no
    // other surface of this product knows how to draw. Pinned by name rather
    // than counted, because a rename is the same harm as an addition — a screen
    // sorting by a level nobody else has is a screen disagreeing with the tool
    // beside it about how bad something is.
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
