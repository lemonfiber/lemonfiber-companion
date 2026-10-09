<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AnOffer;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** Which arm an offer takes, and the name it carried there. */
function howTheOfferReads(AnOffer $offer): string
{
    return $offer->either(
        named: static fn(string $named): TheWordCarriedOut => new TheWordCarriedOut(sprintf('named:%s', $named)),
        none: static fn(): TheWordCarriedOut => new TheWordCarriedOut('none'),
    )->said;
}

it('carries the name the stack gave, trimmed', function (): void {
    expect(howTheOfferReads(AnOffer::named('  3f2a91c0 ')))->toBe('named:3f2a91c0');
});

it('is none where the stack named nothing', function (): void {
    expect(howTheOfferReads(AnOffer::none()))->toBe('none')
        ->and(howTheOfferReads(AnOffer::named('')))->toBe('none')
        ->and(howTheOfferReads(AnOffer::named('   ')))->toBe('none');
});
