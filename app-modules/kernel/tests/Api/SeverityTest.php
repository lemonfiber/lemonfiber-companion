<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function it;

use Modules\Kernel\Api\Severity;

it('reads the four the server sends', function (): void {
    // The values are the wire's. A rename here would make every refusal
    // unparsable, and the parse is in an adapter where nothing else is
    // looking at these strings.
    expect(array_map(
        static fn(Severity $severity): string => $severity->value,
        Severity::cases(),
    ))->toBe(['advisory', 'warning', 'error', 'critical']);
});

it('leaves advisory and warning for the operator to find', function (): void {
    expect(Severity::Advisory->demandsAttention())->toBeFalse()
        ->and(Severity::Warning->demandsAttention())->toBeFalse();
});

it('puts an error and a critical in front of the operator', function (): void {
    expect(Severity::Error->demandsAttention())->toBeTrue()
        ->and(Severity::Critical->demandsAttention())->toBeTrue();
});
