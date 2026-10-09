<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AgainstThePins;
use Modules\Kernel\Api\AnOffer;
use Modules\Kernel\Api\HowServicesTookIt;
use Modules\Kernel\Api\HowTheNotesStand;
use Modules\Kernel\Api\Releases;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\TakingAnUpdate;
use Modules\Kernel\Api\TheStackEdits;
use Modules\Kernel\Api\Upkeep;
use Tests\Support\TheWordCarriedOut;

/** An update to `jellyfin`, offered under the name it is given. */
function anUpdateOffered(AnOffer $offer): Upkeep
{
    return Upkeep::reported(
        AgainstThePins::UpdatesAvailable,
        Releases::none(),
        Services::these(ServiceId::called('jellyfin')),
        Services::none(),
        HowServicesTookIt::none(),
        HowTheNotesStand::Current,
        TheStackEdits::none(),
    )->offering($offer);
}

/** The offer an update carries back, as a word. */
function theOfferCarried(TakingAnUpdate $taking): string
{
    return $taking->offer()->either(
        named: static fn(string $named): TheWordCarriedOut => new TheWordCarriedOut($named),
        none: static fn(): TheWordCarriedOut => new TheWordCarriedOut('none'),
    )->said;
}

it('carries back the offer the operator read', function (): void {
    expect(theOfferCarried(TakingAnUpdate::offeredBy(anUpdateOffered(AnOffer::named('3f2a91c0')))))->toBe('3f2a91c0')
        ->and(theOfferCarried(TakingAnUpdate::offeredBy(anUpdateOffered(AnOffer::none()))))->toBe('none');
});

it('is asked for by the name lemonfiber gives the action, before and after there is one', function (): void {
    $taking = TakingAnUpdate::offeredBy(Upkeep::reported(
        AgainstThePins::UpdatesAvailable,
        Releases::none(),
        Services::these(ServiceId::called('jellyfin')),
        Services::none(),
        HowServicesTookIt::none(),
        HowTheNotesStand::Current,
        TheStackEdits::none(),
    ));

    expect($taking->asked())->toBe('update')
        ->and(TakingAnUpdate::named())->toBe('update');
});
