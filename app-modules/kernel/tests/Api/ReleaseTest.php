<?php

declare(strict_types=1);

use Modules\Kernel\Api\HowCurrent;
use Modules\Kernel\Api\HowServicesTookIt;
use Modules\Kernel\Api\Release;
use Modules\Kernel\Api\Releases;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\TakingAnUpdate;
use Modules\Kernel\Api\Upkeep;
use Modules\Kernel\Api\VersionIsBlank;

it('refuses a release with no version to call it by', function (): void {
    // A payload short of the name is the stack's half of the conversation gone
    // wrong, not a release with an empty name — and a screen handed one would
    // draw a row an operator could tap with nothing behind it.
    expect(fn(): Release => Release::called('   ', noticeable: true, withdrawn: false))
        ->toThrow(VersionIsBlank::class);
});

it('takes the version as it was written, without the space around it', function (): void {
    expect(Release::called('  4.1.0  ', noticeable: true, withdrawn: false)->version())->toBe('4.1.0');
});

it('N2-R20 — offers nothing where the stack reported it is current', function (): void {
    // Both halves, because either alone would be wrong. A stack saying current
    // is not asked further; a stack saying pending with every release withdrawn
    // has nothing to offer either.
    $current = Upkeep::reported(
        HowCurrent::Current,
        Releases::these(Release::called('4.1.0', noticeable: true, withdrawn: false)),
        Services::none(),
        HowServicesTookIt::none(),
    );

    $allTakenBack = Upkeep::reported(
        HowCurrent::Pending,
        Releases::these(Release::called('4.0.17', noticeable: true, withdrawn: true)),
        Services::none(),
        HowServicesTookIt::none(),
    );

    $waiting = Upkeep::reported(
        HowCurrent::Pending,
        Releases::these(Release::called('4.1.0', noticeable: true, withdrawn: false)),
        Services::none(),
        HowServicesTookIt::none(),
    );

    expect($current->hasSomethingToOffer())->toBeFalse()
        ->and($allTakenBack->hasSomethingToOffer())->toBeFalse()
        ->and($waiting->hasSomethingToOffer())->toBeTrue();
});

it('N2-R15 — stale is not pending, and neither is an update waiting to be taken', function (): void {
    // Three cases and not two. Pending is an update to take; stale is a stack
    // that has not looked recently enough to know, and an operator can act on
    // the first and refresh on the second.
    expect(HowCurrent::Pending->hasSomethingWaiting())->toBeTrue()
        ->and(HowCurrent::Current->hasSomethingWaiting())->toBeFalse()
        ->and(HowCurrent::Stale->hasSomethingWaiting())->toBeFalse();
});

it('says when an update would leave the stack alone', function (): void {
    // A release that changes no service is a changelog entry rather than an
    // evening, and a screen can say so instead of asking somebody to confirm
    // nothing.
    $release = Release::called('4.1.0', noticeable: true, withdrawn: false);

    expect(TakingAnUpdate::agreed($release, Services::none())->changesNothing())->toBeTrue()
        ->and(TakingAnUpdate::agreed($release, Services::these(ServiceId::called('jellyfin')))
            ->changesNothing())->toBeFalse();
});
