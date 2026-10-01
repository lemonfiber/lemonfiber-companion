<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ADownloadOnDisk;
use Modules\Kernel\Api\HowTheOfferToLetGoIsGoing;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatLettingItGoCosts;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** Which arm following an offer to let go takes, and what it carried there. */
function howTheOfferToLetGoIsGoingReads(HowTheOfferToLetGoIsGoing $going): string
{
    return $going->either(
        stillRunning: static fn(): TheWordCarriedOut => new TheWordCarriedOut('running'),
        offering: static fn(WhatLettingItGoCosts $offer): TheWordCarriedOut => new TheWordCarriedOut(sprintf('offered:%s', $offer->agreement())),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met:%s', $why->kind()->value)),
    )->said;
}

it('takes the arm for each state, and carries the offer and the obstacle', function (): void {
    $offer = WhatLettingItGoCosts::offered(ADownloadOnDisk::neverImported('Show.Season1', 1), 'It goes', 'stop-seeding-show-season1');

    expect(howTheOfferToLetGoIsGoingReads(HowTheOfferToLetGoIsGoing::stillRunning()))->toBe('running')
        ->and(howTheOfferToLetGoIsGoingReads(HowTheOfferToLetGoIsGoing::offering($offer)))->toBe('offered:stop-seeding-show-season1')
        ->and(howTheOfferToLetGoIsGoingReads(HowTheOfferToLetGoIsGoing::ended()))->toBe('ended')
        ->and(howTheOfferToLetGoIsGoingReads(HowTheOfferToLetGoIsGoing::met(Obstacle::of(KindOfObstacle::CredentialWasRefused))))->toBe(sprintf('met:%s', KindOfObstacle::CredentialWasRefused->value));
});
