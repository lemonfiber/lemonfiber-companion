<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Capability;
use Modules\Kernel\Api\CapabilityIsUnnamed;

it('carries the name the core gave it', function (): void {
    expect(Capability::called('download-client')->named())->toBe('download-client');
});

it('trims a name that arrived padded', function (): void {
    // The same normalisation `ServiceId` makes, and for the same reason: a
    // capability is compared against the stack's own word, and a trailing
    // space is a mismatch nobody can see on a screen.
    expect(Capability::called("  indexer\n")->named())->toBe('indexer');
});

it('refuses a capability nobody named', function (): void {
    // A contest over nothing is not a question an operator can answer. The
    // sentence on the screen is *two services both claim ▒*, and with the noun
    // gone it asks somebody to choose for a purpose nobody stated.
    expect(static fn(): Capability => Capability::called('   '))
        ->toThrow(CapabilityIsUnnamed::class);
});

it('is the same capability as another with the same name', function (): void {
    expect(Capability::called('indexer')->isTheSameAs(Capability::called('indexer')))->toBeTrue();
});

it('is not the same capability as one with a different name', function (): void {
    // The comparison is the whole of the type's identity, so a mutant that
    // answered true here would make every capability equal to every other and
    // a contest would resolve itself.
    expect(Capability::called('indexer')->isTheSameAs(Capability::called('download-client')))->toBeFalse();
});
