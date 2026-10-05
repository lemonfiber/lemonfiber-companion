<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;

use Modules\Sdk\Api\Fields\WiringField;
use Modules\Sdk\Api\LinksAreUnreadable;
use Modules\Sdk\Api\OriginIsUnreadable;
use Modules\Sdk\Api\WireField;

use function sprintf;

it('names the list a wiring left out', function (): void {
    expect(LinksAreUnreadable::missing(WiringField::Unfilled)->getMessage())
        ->toBe('The wiring envelope has no `unfilled`, or it is not what the contract says it is. This answer did not come from a lemonfiber of a version this app can read.');
});

it('names the list, the row and the field of an entry it could not read', function (): void {
    expect(LinksAreUnreadable::entry(WiringField::Wired, WireField::By, 2)->getMessage())
        ->toBe('Row 2 of `wired` in the wiring envelope has no readable `by`. It is refused rather than dropped: a wiring one row short says the stack does not have a link it has.');
});

it('names the field and the word it has no case for', function (): void {
    expect(LinksAreUnreadable::unnamed(WiringField::Settled, 'first-installed', 1)->getMessage())
        ->toBe('Row 1 of `wired` in the wiring envelope says `settled` is `first-installed`, which this app has no word for.');
});

it('carries why a claimant\'s origin would not read', function (): void {
    $why = OriginIsUnreadable::nobodyHere('elsewhere');
    $refused = LinksAreUnreadable::origin(0, $why);

    expect($refused->getMessage())->toBe(sprintf('Row 0 of `wired` in the wiring envelope names a claimant whose origin cannot be read: %s', $why->getMessage()))
        ->and($refused->getPrevious())->toBe($why);
});
