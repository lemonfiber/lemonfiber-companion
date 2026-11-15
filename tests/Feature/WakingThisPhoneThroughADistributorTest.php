<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Lemonfiber\Native\Wakes;
use Modules\Connection\Api\ChoosingWhatReachesThePhone;
use Modules\Connection\Api\TurningNotificationsOn;
use Modules\Connection\Api\WakingThisPhone;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ADistributorToInstall;
use Modules\Kernel\Api\APhoneKeysName;
use Modules\Kernel\Api\APushName;
use Modules\Kernel\Api\AWakeToRegister;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfNotification;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\KindsOfNotification;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\RegisteringAWake;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SetApart;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\WhatRegisteringAWakeCameTo;
use Modules\Kernel\Api\WhatRemovingAWakeCameTo;
use Modules\Kernel\Api\WhatStoppedMoving;
use Modules\Kernel\Api\WhatTheOperatorIsTold;
use Modules\Kernel\Api\WhatWasHeard;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhyNoEndpoint;
use Modules\Operator\Internal\Screens\ThisStackOnThisPhone;
use Modules\Operator\Internal\Screens\WhatEachStackMayTellThisPhone;
use Modules\Operator\Internal\WhereRemovingTheStackStands;
use Modules\Wayfinding\Api\HearingWhileAway;
use Modules\Wayfinding\Api\HowTheWakeWent;
use Tests\Support\APhoneOnItsStackSettings;
use Tests\Support\AWakeOnThePhone;
use Tests\Support\AWakingPhone;
use Tests\Support\Fakes\ACodeOfWhatItWasGiven;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\ANotifierInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\AStackThatKeepsWakes;
use Tests\Support\Fakes\AStackThatOffers;
use Tests\Support\Fakes\AStackThatSaysWhatItTells;
use Tests\Support\Fakes\AStackThatSpeaksUp;
use Tests\Support\Fakes\ConnectionSettingsInMemory;
use Tests\Support\Fakes\DistributorsOnAPhone;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\NotifyingKeptInMemory;
use Tests\Support\Fakes\PhoneKeysNamesKeptInMemory;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\Fakes\WakesInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// Waking an Android phone through a UnifiedPush distributor: the switch on a
// stack's settings, the choice of distributor on the app's settings, what the
// notification page says of a closed app, and a push waking the app for one
// stack.

/** A stack the phone may be woken for. Named for this file. */
function aStackThatMayWakeThePhone(string $seed, string $name): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST))),
        StackName::of($name),
        Address::of('https://192.168.1.48'),
        Fingerprint::of(str_repeat($seed, Fingerprint::CHARACTERS)),
    );
}

/** The names this phone's key carries on these stacks. */
function theKeysNamedOn(Stack ...$stacks): PhoneKeysNamesKeptInMemory
{
    $names = PhoneKeysNamesKeptInMemory::working();

    foreach ($stacks as $stack) {
        $names->keepTheName($stack->id(), APhoneKeysName::of('Galaxy A51 · 7f3a'));
    }

    return $names;
}

/** A stack's settings page on a phone with these distributors, signed in as the operator. */
function theSettingsOfAStackThatMayWake(Stack $stack, DistributorsOnAPhone $phone, AStackThatKeepsWakes $keeping, ?WakingThisPhone $woken = null): ThisStackOnThisPhone
{
    return new APhoneOnItsStackSettings($stack)->pageOf(
        $stack,
        woken: $woken ?? AWakingPhone::waking($phone, $keeping, theKeysNamedOn($stack)),
        wakers: AWakingPhone::choosing($phone),
    );
}

it('draws no switch on an iPhone, where nothing can wake the app', function (): void {
    $loft = aStackThatMayWakeThePhone('a', 'The loft');
    $drawn = WhatTheDeviceWouldDraw::by(theSettingsOfAStackThatMayWake($loft, DistributorsOnAPhone::notHere(), AStackThatKeepsWakes::reachable()));

    expect($drawn->said())->not->toContain(__('waking.switch'))
        ->and($drawn->said())->toContain(__('settings.remove_from_phone'));
});

it('draws no switch for a member\'s session', function (): void {
    $loft = aStackThatMayWakeThePhone('a', 'The loft');
    $phone = new APhoneOnItsStackSettings($loft);
    $phone->keychain->keep($loft->id(), Session::of('a-member-session'), Whose::member('robin'));
    $page = $phone->pageOf($loft, woken: AWakingPhone::waking(DistributorsOnAPhone::with()), wakers: AWakingPhone::choosing(DistributorsOnAPhone::with()));

    expect(WhatTheDeviceWouldDraw::by($page)->said())->not->toContain(__('waking.switch'));
});

it('draws the switch unusable without a distributor, saying why, with ntfy\'s page as a link and as a code', function (): void {
    $loft = aStackThatMayWakeThePhone('a', 'The loft');
    $code = ACodeOfWhatItWasGiven::working();
    $page = new APhoneOnItsStackSettings($loft)->pageOf(
        $loft,
        woken: AWakingPhone::waking(DistributorsOnAPhone::withNone(), AStackThatKeepsWakes::reachable(), theKeysNamedOn($loft)),
        wakers: AWakingPhone::choosing(DistributorsOnAPhone::withNone()),
        encoding: $code,
    );
    $drawn = WhatTheDeviceWouldDraw::by($page);

    expect($drawn->said())->toContain(__('waking.none_installed'))
        ->and($drawn->said())->toContain(ADistributorToInstall::ntfy()->carried())
        ->and($drawn->said())->not->toContain(__('waking.no_code'))
        ->and($drawn->offersThatWait())->toContain(__('waking.switch'))
        ->and($code->carried())->toBe([ADistributorToInstall::ntfy()->carried()]);
});

it('says what waking does while it is off, and turns it on through the one distributor installed', function (): void {
    $loft = aStackThatMayWakeThePhone('a', 'The loft');
    $keeping = AStackThatKeepsWakes::reachable();
    $page = theSettingsOfAStackThatMayWake($loft, DistributorsOnAPhone::with(), $keeping);
    $off = WhatTheDeviceWouldDraw::by($page);

    $page->wakingTurned(on: true);
    $on = WhatTheDeviceWouldDraw::by($page);

    expect($off->said())->toContain(__('waking.off', ['distributor' => 'ntfy']))
        ->and($on->said())->toContain(__('waking.on', ['distributor' => 'ntfy']))
        ->and($keeping->holds($loft, 'galaxy-a51-7f3a'))->toStartWith(DistributorsOnAPhone::PUSHED_AT)
        ->and($page->wakingSaid)->toBeNull();

    $page->wakingTurned(on: true);

    expect($keeping->timesAsked())->toBe(1);
});

it('says a refusal in the stack\'s words, and the switch stays off', function (): void {
    $loft = aStackThatMayWakeThePhone('a', 'The loft');
    $page = theSettingsOfAStackThatMayWake($loft, DistributorsOnAPhone::with(), AStackThatKeepsWakes::refusingTheAddress());

    $page->wakingTurned(on: true);
    $drawn = WhatTheDeviceWouldDraw::by($page);

    expect($drawn->said())->toContain(AStackThatKeepsWakes::NOT_HTTPS)
        ->and($drawn->said())->toContain(__('waking.off', ['distributor' => 'ntfy']));
});

it('says why waking stayed off in this app\'s words where the stack did not refuse it', function (DistributorsOnAPhone $phone, AStackThatKeepsWakes $keeping, bool $named, string $said): void {
    $loft = aStackThatMayWakeThePhone('a', 'The loft');
    $page = theSettingsOfAStackThatMayWake($loft, $phone, $keeping, AWakingPhone::waking($phone, $keeping, $named ? theKeysNamedOn($loft) : PhoneKeysNamesKeptInMemory::working()));

    $page->wakingTurned(on: true);

    expect(WhatTheDeviceWouldDraw::by($page)->said())->toContain(__($said));
})->with([
    'no key on the stack' => [DistributorsOnAPhone::with(), AStackThatKeepsWakes::reachable(), false, 'waking.no_key_name'],
    'no answer from the distributor' => [DistributorsOnAPhone::givingNone(WhyNoEndpoint::NoAnswer), AStackThatKeepsWakes::reachable(), true, 'waking.no_endpoint.no_answer'],
    'a stack that is not answering' => [DistributorsOnAPhone::with(), AStackThatKeepsWakes::unreachable(), true, Obstacle::of(KindOfObstacle::StackDidNotAnswer)->said()],
]);

it('lets go of a session the stack refused as waking was turned on, and draws no switch over it', function (): void {
    $loft = aStackThatMayWakeThePhone('a', 'The loft');
    $phone = new APhoneOnItsStackSettings($loft);
    $refusing = new class implements RegisteringAWake {
        public function registeredOn(Stack $stack, Session $session, AWakeToRegister $wake): WhatRegisteringAWakeCameTo
        {
            return WhatRegisteringAWakeCameTo::met(Obstacle::of(KindOfObstacle::CredentialWasRefused));
        }

        public function removedFrom(Stack $stack, Session $session, APushName $name): WhatRemovingAWakeCameTo
        {
            return WhatRemovingAWakeCameTo::removed();
        }
    };
    $page = $phone->pageOf($loft, woken: AWakingPhone::waking(DistributorsOnAPhone::with(), $refusing, theKeysNamedOn($loft)), wakers: AWakingPhone::choosing(DistributorsOnAPhone::with()));

    $page->wakingTurned(on: true);

    expect($page->wakingSaid?->line)->toBe('connection.session_has_ended')
        ->and($phone->keychain->keepsAnythingOf($loft->id()))->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($page)->said())->not->toContain(__('waking.switch'));
});

it('asks which distributor where several are installed and none is chosen, and wakes through the one chosen', function (): void {
    $loft = aStackThatMayWakeThePhone('a', 'The loft');
    $phone = DistributorsOnAPhone::with(DistributorsOnAPhone::ntfy(), DistributorsOnAPhone::nextPush());
    $keeping = AStackThatKeepsWakes::reachable();
    $page = theSettingsOfAStackThatMayWake($loft, $phone, $keeping);

    $before = WhatTheDeviceWouldDraw::by($page);
    $page->wakingTurned(on: true);
    $asking = WhatTheDeviceWouldDraw::by($page);
    $page->wakingTurned(on: false);
    $leftOff = WhatTheDeviceWouldDraw::by($page);
    $page->wakingTurned(on: true);
    $page->wakeThrough('not.installed');
    $notInstalled = WhatTheDeviceWouldDraw::by($page);
    $page->wakingTurned(on: true);
    $page->wakeThrough(DistributorsOnAPhone::nextPush()->package());
    $chosen = WhatTheDeviceWouldDraw::by($page);
    $heldOnceChosen = $keeping->holds($loft, 'galaxy-a51-7f3a');
    $page->wakingTurned(on: false);
    $page->wakingTurned(on: true);

    expect($before->said())->toContain(__('waking.off_choosing'))
        ->and($before->said())->not->toContain(__('waking.choose'))
        ->and($asking->said())->toContain(__('waking.choose'))
        ->and($leftOff->said())->not->toContain(__('waking.choose'))
        ->and($notInstalled->said())->not->toContain(__('waking.choose'))
        ->and($asking->offers())->toContain('NextPush')
        ->and($asking->offers())->toContain('ntfy')
        ->and($chosen->said())->toContain(__('waking.on', ['distributor' => 'NextPush']))
        ->and($heldOnceChosen)->toContain(DistributorsOnAPhone::nextPush()->package())
        ->and($keeping->holds($loft, 'galaxy-a51-7f3a'))->toContain(DistributorsOnAPhone::nextPush()->package())
        ->and(WhatTheDeviceWouldDraw::by($page)->said())->toContain(__('waking.on', ['distributor' => 'NextPush']))
        ->and(WhatTheDeviceWouldDraw::by($page)->said())->not->toContain(__('waking.choose'));
});

it('stops at once when turned off, and says the stack holds the address until it is reached where it could not be told', function (): void {
    $loft = aStackThatMayWakeThePhone('a', 'The loft');
    $phone = DistributorsOnAPhone::with();
    $keeping = AStackThatKeepsWakes::reachable();
    $page = theSettingsOfAStackThatMayWake($loft, $phone, $keeping);
    $page->wakingTurned(on: true);

    $keeping->goesAway();
    $page->wakingTurned(on: false);

    expect(WhatTheDeviceWouldDraw::by($page)->said())->toContain(__('waking.owed'))
        ->and($phone->holdsARegistrationFor($loft->id()))->toBeFalse();
});

it('tells a stack that wakes the phone to forget it before removing it, saying so as it asks', function (): void {
    $loft = aStackThatMayWakeThePhone('a', 'The loft');
    $keeping = AStackThatKeepsWakes::reachable();
    $phone = new APhoneOnItsStackSettings($loft);
    $woken = AWakingPhone::waking(DistributorsOnAPhone::with(), $keeping, theKeysNamedOn($loft));
    $page = $phone->pageOf($loft, woken: $woken, wakers: AWakingPhone::choosing(DistributorsOnAPhone::with()));
    $page->wakingTurned(on: true);

    $page->askToRemove();
    $asking = WhatTheDeviceWouldDraw::by($page);
    $page->removeTheStack();

    expect($asking->said())->toContain(__('waking.removing_tells', ['name' => 'The loft']))
        ->and($keeping->holds($loft, 'galaxy-a51-7f3a'))->toBe('')
        ->and($phone->stacks->configured()->knows($loft->id()))->toBeFalse()
        ->and($woken->keepsAnythingOf($loft->id()))->toBeFalse();
});

it('says a stack that could not be told still holds the address before removing it, and removes it once told so', function (): void {
    $loft = aStackThatMayWakeThePhone('a', 'The loft');
    $keeping = AStackThatKeepsWakes::reachable();
    $phone = new APhoneOnItsStackSettings($loft);
    $woken = AWakingPhone::waking(DistributorsOnAPhone::with(), $keeping, theKeysNamedOn($loft));
    $page = $phone->pageOf($loft, woken: $woken, wakers: AWakingPhone::choosing(DistributorsOnAPhone::with()));
    $page->wakingTurned(on: true);
    $keeping->goesAway();

    $page->askToRemove();
    $page->removeTheStack();
    $told = WhatTheDeviceWouldDraw::by($page);

    expect($page->removal)->toBe(WhereRemovingTheStackStands::StillHoldsTheAddress)
        ->and($told->said())->toContain(__('waking.still_held', ['name' => 'The loft']))
        ->and($phone->stacks->configured()->knows($loft->id()))->toBeTrue();

    $page->removeAnyway();

    expect($phone->stacks->configured()->knows($loft->id()))->toBeFalse()
        ->and($woken->keepsAnythingOf($loft->id()))->toBeFalse();
});

it('removes a stack that never woke the phone without asking it anything', function (): void {
    $loft = aStackThatMayWakeThePhone('a', 'The loft');
    $keeping = AStackThatKeepsWakes::reachable();
    $page = theSettingsOfAStackThatMayWake($loft, DistributorsOnAPhone::with(), $keeping);

    $page->askToRemove();

    expect(WhatTheDeviceWouldDraw::by($page)->said())->not->toContain(__('waking.removing_tells', ['name' => 'The loft']));

    $page->removeTheStack();

    expect($keeping->timesAsked())->toBe(0)
        ->and($page->removal)->not->toBe(WhereRemovingTheStackStands::StillHoldsTheAddress);
});

/** The notification page over these stacks, each signed in as the operator, with notifications on. */
function theNotificationPageWakingThrough(WakingThisPhone $woken, Stack ...$stacks): WhatEachStackMayTellThisPhone
{
    $keychain = AKeychainInMemory::working();

    foreach ($stacks as $stack) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $switch = new TurningNotificationsOn(ASealInMemory::working(), ConnectionSettingsInMemory::empty(), ANotifierInMemory::allowed(), FrozenClock::at(Instant::atEpochSeconds(0)), WakesInMemory::thatWake());
    $switch->turnOn();

    return new WhatEachStackMayTellThisPhone(
        StacksInMemory::holding(...$stacks),
        $keychain,
        AStackThatSaysWhatItTells::with(WhatTheOperatorIsTold::byPreset('quiet', 'Only what needs you today', SetApart::of(), KindsOfNotification::these(KindOfNotification::Problem))),
        new ChoosingWhatReachesThePhone(ASealInMemory::working(), NotifyingKeptInMemory::empty(), FrozenClock::at(Instant::atEpochSeconds(0))),
        AStackThatOffers::everything(),
        $switch,
        new AppsSettingsThatOpen(),
        $woken,
    );
}

it('says on the notification page which stacks wake the phone, and that the others may be heard late or not at all', function (): void {
    $loft = aStackThatMayWakeThePhone('a', 'The loft');
    $shed = aStackThatMayWakeThePhone('b', 'The shed');
    $woken = AWakingPhone::waking(DistributorsOnAPhone::with(), AStackThatKeepsWakes::reachable(), theKeysNamedOn($loft));
    $woken->turnOn($loft, Session::of('a-session-not-a-secret'), DistributorsOnAPhone::ntfy());
    $page = theNotificationPageWakingThrough($woken, $loft, $shed);

    do {
        $drawn = WhatTheDeviceWouldDraw::by($page);
    } while ($page->waitsForTheNextFrame());

    expect($drawn->said())->toContain(__('waking.woken_through', ['distributor' => 'ntfy']))
        ->and($drawn->said())->toContain(__('waking.may_be_late'))
        ->and($page->wokenThrough($loft->id()->stored()))->toBe('ntfy')
        ->and($page->wokenThrough($shed->id()->stored()))->toBe('');
});

it('wakes the app for one stack a push came for, where that stack wakes the phone, and drops a push for any other', function (): void {
    $loft = aStackThatMayWakeThePhone('a', 'The loft');
    $shed = aStackThatMayWakeThePhone('b', 'The shed');
    $woken = AWakingPhone::waking(DistributorsOnAPhone::with(), AStackThatKeepsWakes::reachable(), theKeysNamedOn($loft, $shed));
    $woken->turnOn($loft, Session::of('a-session-for-The loft'), DistributorsOnAPhone::ntfy());
    $phone = AWakeOnThePhone::holding(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::aSignOfLife()), Instant::atEpochSeconds(1_790_000_000), $loft, $shed)
        ->signedInTo($loft, Whose::theOperator())
        ->signedInTo($shed, Whose::theOperator());

    expect($phone->hearing($woken)->hearOnly($shed->id()))->toBe(HowTheWakeWent::NotWokenFor)
        ->and($phone->hearing->asked())->toBe(0)
        ->and($phone->hearing($woken)->hearOnly($loft->id()))->toBe(HowTheWakeWent::GaveUpOnSome)
        ->and($phone->hearing->asked())->toBe(40);
});

it('drops a push that comes while notifications are off or the app is open', function (): void {
    $loft = aStackThatMayWakeThePhone('a', 'The loft');
    $woken = AWakingPhone::waking(DistributorsOnAPhone::with(), AStackThatKeepsWakes::reachable(), theKeysNamedOn($loft));
    $woken->turnOn($loft, Session::of('a-session'), DistributorsOnAPhone::ntfy());
    $off = AWakeOnThePhone::withNotificationsOff(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::aSignOfLife()), Instant::atEpochSeconds(1_790_000_000), $loft)->signedInTo($loft, Whose::theOperator());
    $open = AWakeOnThePhone::holding(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::aSignOfLife()), Instant::atEpochSeconds(1_790_000_000), $loft)->signedInTo($loft, Whose::theOperator())->withTheAppOpen();

    expect($off->hearing($woken)->hearOnly($loft->id()))->toBe(HowTheWakeWent::NothingToTell)
        ->and($open->hearing($woken)->hearOnly($loft->id()))->toBe(HowTheWakeWent::TheAppIsOpen);
});

it('sends a removal owed to a stack on the next read of it that comes back', function (): void {
    $loft = aStackThatMayWakeThePhone('a', 'The loft');
    $keeping = AStackThatKeepsWakes::reachable();
    $woken = AWakingPhone::waking(DistributorsOnAPhone::with(), $keeping, theKeysNamedOn($loft));
    $woken->turnOn($loft, Session::of('a-session'), DistributorsOnAPhone::ntfy());
    $keeping->goesAway();
    $woken->turnOff($loft, Session::of('a-session'));
    $keeping->comesBack();
    $phone = AWakeOnThePhone::holding(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::said(TheHealthSummary::of(HowItStands::Healthy, 0, '', WhatStoppedMoving::nothing()))), Instant::atEpochSeconds(1_790_000_000), $loft)
        ->signedInTo($loft, Whose::theOperator());

    $phone->hearing($woken)->hear();

    expect($woken->forTheStack($loft->id())->owesARemoval())->toBeFalse()
        ->and($woken->keepsAnythingOf($loft->id()))->toBeFalse();
});

/** Run the command a push for this stack runs, and answer its exit code. */
function theCommandAPushRuns(Stack $stack): int
{
    return Artisan::call(Wakes::COMMAND, ['--stack' => $stack->id()->stored()]);
}

it('is what the command a push runs does, for the stack it names', function (): void {
    $loft = aStackThatMayWakeThePhone('a', 'The loft');
    $woken = AWakingPhone::waking(DistributorsOnAPhone::with(), AStackThatKeepsWakes::reachable(), theKeysNamedOn($loft));
    $woken->turnOn($loft, Session::of('a-session'), DistributorsOnAPhone::ntfy());
    $phone = AWakeOnThePhone::holding(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::aSignOfLife()), Instant::atEpochSeconds(1_790_000_000), $loft)
        ->signedInTo($loft, Whose::theOperator());
    app()->instance(HearingWhileAway::class, $phone->hearing($woken));

    expect(theCommandAPushRuns($loft))->toBe(0)
        ->and($phone->hearing->asked())->toBe(40)
        ->and(theCommandAPushRuns(aStackThatMayWakeThePhone('b', 'The shed')))->toBe(0)
        ->and($phone->hearing->asked())->toBe(40);
});
