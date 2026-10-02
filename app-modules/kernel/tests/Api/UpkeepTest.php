<?php

declare(strict_types=1);

use Modules\Kernel\Api\AgainstThePins;
use Modules\Kernel\Api\AStackEdit;
use Modules\Kernel\Api\HowServicesTookIt;
use Modules\Kernel\Api\HowTheNotesStand;
use Modules\Kernel\Api\NothingToTake;
use Modules\Kernel\Api\Release;
use Modules\Kernel\Api\Releases;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\TakingAnUpdate;
use Modules\Kernel\Api\TheStackEdits;
use Modules\Kernel\Api\Upkeep;
use Modules\Kernel\Api\WhatAReleaseDelivers;
use Tests\Support\TheWordCarriedOut;

/** A release in the record, standing or withdrawn. */
function aReleaseInTheRecord(string $version, bool $withdrawn = false): Release
{
    return Release::called($version, noticeable: true, withdrawn: $withdrawn, delivers: WhatAReleaseDelivers::saidNothing());
}

/** A reading running `4.1.0`, saying what the pins say and moving what it is given. */
function aReadingWhosePinsSay(AgainstThePins $pins, Services $changing, bool $runningWithdrawn = false): Upkeep
{
    return Upkeep::reported(
        $pins,
        Releases::these(aReleaseInTheRecord('4.1.0', $runningWithdrawn), aReleaseInTheRecord('4.0.16')),
        $changing,
        Services::none(),
        HowServicesTookIt::none(),
        HowTheNotesStand::Current,
        TheStackEdits::none(),
    )->runningOn(aReleaseInTheRecord('4.1.0', $runningWithdrawn));
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
        HowTheNotesStand::Current,
        TheStackEdits::none(),
    );

    $said = [];

    foreach ($upkeep->history() as $release) {
        $said[] = sprintf('%s:%s', $release->version(), $release->wasWithdrawn() ? 'withdrawn' : 'standing');
    }

    expect($said)->toBe(['4.0.17:withdrawn', '4.0.16:standing'])
        ->and($upkeep->runningAWithdrawnRelease())->toBeFalse();
});

it('keeps everything it read when it is told which release is running', function (): void {
    $edits = TheStackEdits::these(AStackEdit::at('compose.yaml', "- image: mine\n+ image: ours\n"));
    $went = HowServicesTookIt::none();
    $read = Upkeep::reported(
        AgainstThePins::Partial,
        Releases::these(aReleaseInTheRecord('4.0.16')),
        Services::these(ServiceId::called('sonarr')),
        Services::these(ServiceId::called('jellyfin')),
        $went,
        HowTheNotesStand::Stale,
        $edits,
    );
    $running = $read->runningOn(aReleaseInTheRecord('4.1.0'));

    expect($running->againstThePins())->toBe(AgainstThePins::Partial)
        ->and($running->history())->toEqual($read->history())
        ->and($running->changing())->toEqual($read->changing())
        ->and($running->cannotBePutBack())->toEqual($read->cannotBePutBack())
        ->and($running->howItWent())->toBe($went)
        ->and($running->notes())->toBe(HowTheNotesStand::Stale)
        ->and($running->editsKept())->toBe($edits)
        ->and($running->inUse(
            named: static fn(Release $release): TheWordCarriedOut => new TheWordCarriedOut($release->version()),
            unstated: static fn(): TheWordCarriedOut => new TheWordCarriedOut('unstated'),
        )->said)->toBe('4.1.0')
        ->and($read->inUse(
            named: static fn(Release $release): TheWordCarriedOut => new TheWordCarriedOut($release->version()),
            unstated: static fn(): TheWordCarriedOut => new TheWordCarriedOut('unstated'),
        )->said)->toBe('unstated');
});
