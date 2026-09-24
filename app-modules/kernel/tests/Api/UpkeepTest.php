<?php

declare(strict_types=1);

use Modules\Kernel\Api\AgainstThePins;
use Modules\Kernel\Api\HowServicesTookIt;
use Modules\Kernel\Api\NothingToTake;
use Modules\Kernel\Api\Release;
use Modules\Kernel\Api\Releases;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\TakingAnUpdate;
use Modules\Kernel\Api\Upkeep;
use Modules\Kernel\Api\WhatAReleaseDelivers;

/** A release in the record, standing or withdrawn. */
function aReleaseInTheRecord(string $version, bool $withdrawn = false): Release
{
    return Release::called($version, noticeable: true, withdrawn: $withdrawn, delivers: WhatAReleaseDelivers::saidNothing());
}

/** A reading running `4.1.0`, saying what the pins say and moving what it is given. */
function aReadingWhosePinsSay(AgainstThePins $pins, Services $changing, bool $runningWithdrawn = false): Upkeep
{
    return Upkeep::runningOn(
        $pins,
        aReleaseInTheRecord('4.1.0', $runningWithdrawn),
        Releases::these(aReleaseInTheRecord('4.1.0', $runningWithdrawn), aReleaseInTheRecord('4.0.16')),
        $changing,
        Services::none(),
        HowServicesTookIt::none(),
    );
}

it('says there is an update to take only where the pins say one is available', function (): void {
    $taking = [];

    foreach (AgainstThePins::cases() as $pins) {
        if ($pins->hasAnUpdateToTake()) {
            $taking[] = $pins;
        }
    }

    expect($taking)->toBe([AgainstThePins::UpdatesAvailable]);
});

it('offers nothing where the stack said every service is on its pin', function (): void {
    // Releases are listed whatever the pins say, because they are history. A
    // reading that offered an update for having a history would offer one to
    // every stack there is.
    $current = aReadingWhosePinsSay(AgainstThePins::Current, Services::these(ServiceId::called('jellyfin')));

    expect($current->hasSomethingToOffer())->toBeFalse()
        ->and($current->history()->count())->toBe(2)
        ->and(fn(): TakingAnUpdate => TakingAnUpdate::offeredBy($current))->toThrow(NothingToTake::class, 'current');
});

it('offers the update where the stack said one is available', function (): void {
    $available = aReadingWhosePinsSay(AgainstThePins::UpdatesAvailable, Services::these(ServiceId::called('jellyfin')));

    $named = [];

    foreach (TakingAnUpdate::offeredBy($available)->changing() as $service) {
        $named[] = $service->named();
    }

    expect($available->hasSomethingToOffer())->toBeTrue()
        ->and($named)->toBe(['jellyfin']);
});

it('offers nothing where every change was one the stack refused', function (): void {
    // `changing` holds only what the stack would move. Available with none of
    // it left is a confirmation naming nothing.
    $refusedAll = aReadingWhosePinsSay(AgainstThePins::UpdatesAvailable, Services::none());

    expect($refusedAll->hasSomethingToOffer())->toBeFalse()
        ->and(fn(): TakingAnUpdate => TakingAnUpdate::offeredBy($refusedAll))->toThrow(NothingToTake::class);
});

it('offers nothing onto the pins a withdrawn release carries', function (): void {
    $withdrawn = aReadingWhosePinsSay(
        AgainstThePins::UpdatesAvailable,
        Services::these(ServiceId::called('jellyfin')),
        runningWithdrawn: true,
    );

    expect($withdrawn->runningAWithdrawnRelease())->toBeTrue()
        ->and($withdrawn->hasSomethingToOffer())->toBeFalse();
});

it('keeps a withdrawn release in the history rather than dropping it', function (): void {
    $upkeep = Upkeep::reported(
        AgainstThePins::Current,
        Releases::these(aReleaseInTheRecord('4.0.17', withdrawn: true), aReleaseInTheRecord('4.0.16')),
        Services::none(),
        Services::none(),
        HowServicesTookIt::none(),
    );

    $said = [];

    foreach ($upkeep->history() as $release) {
        $said[] = sprintf('%s:%s', $release->version(), $release->wasWithdrawn() ? 'withdrawn' : 'standing');
    }

    expect($said)->toBe(['4.0.17:withdrawn', '4.0.16:standing'])
        ->and($upkeep->runningAWithdrawnRelease())->toBeFalse();
});
