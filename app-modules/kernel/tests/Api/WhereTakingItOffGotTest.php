<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AnUninstall;
use Modules\Kernel\Api\HowMuchWasRead;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
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

use Tests\Support\TheWordCarriedOut;

/** Which arm a removal answers on, and how much it carried there. */
function theArmTakingItOffGotTo(WhereTakingItOffGot $got): string
{
    return $got->either(
        surveyed: static fn(): TheWordCarriedOut => new TheWordCarriedOut('surveyed'),
        rehearsed: static fn(): TheWordCarriedOut => new TheWordCarriedOut('rehearsed'),
        complete: static fn(NamedOnTheManifest $gone, NamedOnTheManifest $credentials): TheWordCarriedOut => new TheWordCarriedOut(sprintf('complete %d/%d', $gone->count(), $credentials->count())),
        partial: static fn(NamedOnTheManifest $gone, NamedOnTheManifest $credentials, WhatWasLeftBehind $left): TheWordCarriedOut => new TheWordCarriedOut(sprintf('partial %d/%d/%d', $gone->count(), $credentials->count(), $left->count())),
    )->said;
}

/** Which arm the work answers on. */
function theArmTheUninstallWorkIsOn(WhatBecameOfTheUninstall $became): string
{
    return $became->either(
        underway: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('underway %s', $job->shown())),
        answered: static fn(AnUninstall $uninstall): TheWordCarriedOut => new TheWordCarriedOut(sprintf('answered %s', $uninstall->manifest()->agreement())),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        refused: static fn(string $because): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused %s', $because)),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met %s', $why->kind()->value)),
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
        ->and(theArmTheUninstallWorkIsOn(WhatBecameOfTheUninstall::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe(sprintf('met %s', KindOfObstacle::StackDidNotAnswer->value))
        ->and(fn(): WhatBecameOfTheUninstall => WhatBecameOfTheUninstall::refused(' '))->toThrow(UninstallSaysNothing::class, 'its `reason` blank');
});

it('answers a reading on the arm for what it found', function (): void {
    $found = WhatWasFoundOfTheUninstall::found(aReadingOfStopping())->either(
        found: static fn(AnUninstall $uninstall): TheWordCarriedOut => new TheWordCarriedOut($uninstall->manifest()->agreement()),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    );
    $met = WhatWasFoundOfTheUninstall::met(Obstacle::of(KindOfObstacle::CredentialWasRefused))->either(
        found: static fn(AnUninstall $uninstall): TheWordCarriedOut => new TheWordCarriedOut($uninstall->manifest()->agreement()),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    );

    expect($found->said)->toBe('stop-0')
        ->and($met->said)->toEqual(KindOfObstacle::CredentialWasRefused->value);
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
