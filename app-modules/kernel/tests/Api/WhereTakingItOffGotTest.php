<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AnUninstall;
use Modules\Kernel\Api\HowMuchWasRead;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\NamedOnTheManifest;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TakingItOff;
use Modules\Kernel\Api\UninstallSaysNothing;
use Modules\Kernel\Api\WhatBecameOfTheUninstall;
use Modules\Kernel\Api\WhatGoesAndWhatStays;
use Modules\Kernel\Api\WhatIsNotLemonfibers;
use Modules\Kernel\Api\WhatIsStillComing;
use Modules\Kernel\Api\WhatItCannotTake;
use Modules\Kernel\Api\WhatItReaches;
use Modules\Kernel\Api\WhatSortItIs;
use Modules\Kernel\Api\WhatTakingItOffComesTo;
use Modules\Kernel\Api\WhatToKnowFirst;
use Modules\Kernel\Api\WhatWasFoundOfTheUninstall;
use Modules\Kernel\Api\WhatWasLeftBehind;
use Modules\Kernel\Api\WhereTakingItOffGot;
use Modules\Kernel\Api\WhetherToWait;
use Modules\Kernel\Api\WhichRemoval;

use function sprintf;

/** A line carried out of an `either()` arm. */
final readonly class WhereItGotAs
{
    public function __construct(public string $said) {}
}

/** Which arm a removal answers on, and how much it carried there. */
function theArmTakingItOffGotTo(WhereTakingItOffGot $got): string
{
    return $got->either(
        surveyed: static fn(): WhereItGotAs => new WhereItGotAs('surveyed'),
        rehearsed: static fn(): WhereItGotAs => new WhereItGotAs('rehearsed'),
        complete: static fn(NamedOnTheManifest $gone, NamedOnTheManifest $credentials): WhereItGotAs => new WhereItGotAs(sprintf('complete %d/%d', $gone->count(), $credentials->count())),
        partial: static fn(NamedOnTheManifest $gone, NamedOnTheManifest $credentials, WhatWasLeftBehind $left): WhereItGotAs => new WhereItGotAs(sprintf('partial %d/%d/%d', $gone->count(), $credentials->count(), $left->count())),
    )->said;
}

/** Which arm the work answers on. */
function theArmTheUninstallWorkIsOn(WhatBecameOfTheUninstall $became): string
{
    return $became->either(
        underway: static fn(Job $job): WhereItGotAs => new WhereItGotAs(sprintf('underway %s', $job->shown())),
        answered: static fn(AnUninstall $uninstall): WhereItGotAs => new WhereItGotAs(sprintf('answered %s', $uninstall->manifest()->agreement())),
        ended: static fn(): WhereItGotAs => new WhereItGotAs('ended'),
        refused: static fn(string $because): WhereItGotAs => new WhereItGotAs(sprintf('refused %s', $because)),
        met: static fn(Obstacle $why): WhereItGotAs => new WhereItGotAs(sprintf('met %s', $why->value)),
    )->said;
}

/** A reading of stopping, with nothing on it. */
function aReadingOfStopping(): AnUninstall
{
    return AnUninstall::of(
        WhatTakingItOffComesTo::read(WhichRemoval::Stop, WhatGoesAndWhatStays::said('Nothing', 'Everything'), WhatItReaches::of(), 0, WhatToKnowFirst::said(WhatIsNotLemonfibers::of(), WhatIsStillComing::of(), WhatItCannotTake::of()), HowMuchWasRead::everything(), 'stop-0'),
        WhereTakingItOffGot::surveyed(),
    );
}

it('answers on the arm for where it got, and is a reading only when surveyed', function (): void {
    $gone = NamedOnTheManifest::under('gone', 'a');
    $credentials = NamedOnTheManifest::under('credentials');

    expect(theArmTakingItOffGotTo(WhereTakingItOffGot::surveyed()))->toBe('surveyed')
        ->and(theArmTakingItOffGotTo(WhereTakingItOffGot::rehearsed()))->toBe('rehearsed')
        ->and(theArmTakingItOffGotTo(WhereTakingItOffGot::complete($gone, $credentials)))->toBe('complete 1/0')
        ->and(theArmTakingItOffGotTo(WhereTakingItOffGot::partial($gone, $credentials, WhatWasLeftBehind::of())))->toBe('partial 1/0/0')
        ->and(WhereTakingItOffGot::surveyed()->isAReading())->toBeTrue()
        ->and(WhereTakingItOffGot::rehearsed()->isAReading())->toBeFalse()
        ->and(WhereTakingItOffGot::complete($gone, $credentials)->isAReading())->toBeFalse();
});

it('answers the work on the arm for where it is', function (): void {
    expect(theArmTheUninstallWorkIsOn(WhatBecameOfTheUninstall::underway(Job::named('j-1'))))->toBe('underway j-1')
        ->and(theArmTheUninstallWorkIsOn(WhatBecameOfTheUninstall::answered(aReadingOfStopping())))->toBe('answered stop-0')
        ->and(theArmTheUninstallWorkIsOn(WhatBecameOfTheUninstall::ended()))->toBe('ended')
        ->and(theArmTheUninstallWorkIsOn(WhatBecameOfTheUninstall::refused('No')))->toBe('refused No')
        ->and(theArmTheUninstallWorkIsOn(WhatBecameOfTheUninstall::met(Obstacle::StackDidNotAnswer)))->toBe(sprintf('met %s', Obstacle::StackDidNotAnswer->value))
        ->and(fn(): WhatBecameOfTheUninstall => WhatBecameOfTheUninstall::refused(' '))->toThrow(UninstallSaysNothing::class, 'its `reason` blank');
});

it('answers a reading on the arm for what it found', function (): void {
    $found = WhatWasFoundOfTheUninstall::found(aReadingOfStopping())->either(
        found: static fn(AnUninstall $uninstall): WhereItGotAs => new WhereItGotAs($uninstall->manifest()->agreement()),
        met: static fn(Obstacle $why): WhereItGotAs => new WhereItGotAs($why->value),
    );
    $met = WhatWasFoundOfTheUninstall::met(Obstacle::CredentialWasRefused)->either(
        found: static fn(AnUninstall $uninstall): WhereItGotAs => new WhereItGotAs($uninstall->manifest()->agreement()),
        met: static fn(Obstacle $why): WhereItGotAs => new WhereItGotAs($why->value),
    );

    expect($found->said)->toBe('stop-0')
        ->and($met->said)->toBe(Obstacle::CredentialWasRefused->value);
});

it('says which removal takes what admits this app, and which takes the library', function (): void {
    foreach (WhichRemoval::cases() as $tier) {
        expect($tier->takesWhatAdmitsThisApp())->toBe($tier === WhichRemoval::Configuration, $tier->value)
            ->and($tier->takesTheLibrary())->toBe($tier === WhichRemoval::Media, $tier->value)
            ->and($tier->saidOnTheScreen())->toBe(sprintf('uninstall.tier.%s', $tier->value))
            ->and($tier->agreedToAs())->toBe(sprintf('uninstall.agree.%s', $tier->value));
    }
});

it('names each sort, asks the stack to wait only when told to, and asks for the removal by the wire\'s word', function (): void {
    expect(WhatSortItIs::Network->saidOnTheScreen())->toBe('uninstall.sort.network')
        ->and(WhetherToWait::ForTheDownloads->waits())->toBeTrue()
        ->and(WhetherToWait::GoAheadNow->waits())->toBeFalse()
        ->and(TakingItOff::TakeItOff->asked())->toBe('uninstall');
});
