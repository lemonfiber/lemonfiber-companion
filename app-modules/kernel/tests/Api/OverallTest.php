<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function it;

use Modules\Kernel\Api\Overall;

it('reads the four the server sends', function (): void {
    // The values are the wire's. A rename here would make every report
    // unreadable, and the parse is in an adapter where nothing else is looking
    // at these strings.
    expect(array_map(
        static fn(Overall $overall): string => $overall->value,
        Overall::cases(),
    ))->toBe(['broken', 'unknown', 'degraded', 'healthy']);
});

it('is declared worst first, like a conclusion', function (): void {
    // Pinned so the two enums cannot drift into reading opposite ways in a
    // file. Nothing else would notice: the enum would still compile and every
    // match would still be exhaustive.
    expect(Overall::cases())->toBe([
        Overall::Broken,
        Overall::Unknown,
        Overall::Degraded,
        Overall::Healthy,
    ]);
});

it('puts unknown above degraded rather than between it and healthy', function (): void {
    // The distinction the subsystem turns on, one level up from `Conclusion`.
    // A run that could not check the tunnel is not a milder version of a run
    // that checked it and found a warning.
    $cases = Overall::cases();

    expect($cases[1])->toBe(Overall::Unknown);
    expect($cases[2])->toBe(Overall::Degraded);
});
