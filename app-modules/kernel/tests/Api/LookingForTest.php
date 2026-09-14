<?php

declare(strict_types=1);

use Modules\Kernel\Api\LookingFor;

it('N2-R10 — a blank box is not a search', function (): void {
    // A blank term selects every line, so a screen treating it as a search
    // would announce *200 of 200 lines match* the moment somebody cleared the
    // field — which reads as a result rather than the absence of one.
    expect(LookingFor::nothing()->isSearching())->toBeFalse()
        ->and(LookingFor::text('')->isSearching())->toBeFalse()
        ->and(LookingFor::text('   ')->isSearching())->toBeFalse();
});

it('something typed is a search, and comes back as it will be matched', function (): void {
    expect(LookingFor::text('timeout')->isSearching())->toBeTrue()
        ->and(LookingFor::text('timeout')->typed())->toBe('timeout');
});

it('trims what a phone keyboard adds after a word', function (): void {
    // Matching on a trailing space would silently find nothing, which is the
    // worst answer available: it looks exactly like a service that never said
    // the thing.
    expect(LookingFor::text(' timeout ')->typed())->toBe('timeout');
});

it('nothing typed shows nothing back', function (): void {
    expect(LookingFor::nothing()->typed())->toBe('');
});
