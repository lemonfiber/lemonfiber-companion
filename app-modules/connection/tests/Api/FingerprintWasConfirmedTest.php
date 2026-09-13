<?php

declare(strict_types=1);

namespace Modules\Connection\Tests\Api;

use function expect;
use function it;

use Modules\Connection\Api\FingerprintWasConfirmed;
use Modules\Kernel\Api\AtAGlance;
use Modules\Kernel\Api\Fingerprint;

use function str_repeat;

/** The comparable form of a digest made of one repeated character. */
function glanceAt(string $character): AtAGlance
{
    return AtAGlance::of(Fingerprint::of(str_repeat($character, Fingerprint::CHARACTERS)));
}

it('covers the form the operator was actually shown', function (): void {
    expect(FingerprintWasConfirmed::byTheOperator(glanceAt('a'))->covers(glanceAt('a')))->toBeTrue();
});

it('does not cover a certificate the operator never compared', function (): void {
    // What makes this a value rather than a flag: a `true` cannot be asked
    // which machine it was about, so a confirmation kept from an earlier
    // attempt would pair whatever came next.
    expect(FingerprintWasConfirmed::byTheOperator(glanceAt('a'))->covers(glanceAt('b')))->toBeFalse();
});
