<?php

declare(strict_types=1);

use Illuminate\Contracts\Translation\Translator;
use Modules\Kernel\Api\AClientToHandOver;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AHandoff;
use Modules\Kernel\Api\AMember;
use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\ASignedInDevice;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheClientsToHandOver;
use Modules\Kernel\Api\TheMembers;
use Modules\Kernel\Api\TheSignedInDevices;
use Modules\Kernel\Api\TheStepsOnTheirDevice;
use Modules\Kernel\Api\WhatBecameOfTheHandoff;
use Modules\Kernel\Api\WhatTheHandoffNeedsNext;
use Modules\Kernel\Api\WhatToHandThem;
use Modules\Kernel\Api\WhereTheHandoffStands;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\AskingSomebodyIn;
use Modules\Operator\Internal\Screens\ConnectingADeviceForThem;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Internal\TheMenu;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\ACodeOfWhatItWasGiven;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AShareSheetThatWasOffered;
use Tests\Support\Fakes\AStackThatHandsDevicesOver;
use Tests\Support\Fakes\AStackThatInvites;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// Connecting a member's device: nothing asked on open, the hand-off asked for
// on a tap and followed while the stack works it out, the address as a code
// with the steps and the apps, the devices signed in, what there is to do
// next, and a refusal in the stack's own words.
//
// Here rather than in the operator module's own tests because a screen
// renders, and rendering needs the application.

/** The moment the screen's clock reads: an hour after the code was first given. */
const THE_HOUR_AFTER = 1_790_802_000;

/** The machine a device is connected to. */
function theStackADeviceConnectsTo(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** A hand-off standing where a case says, with what it would say there. */
function aHandoffThatStands(WhereTheHandoffStands $stands, WhatTheHandoffNeedsNext $next = WhatTheHandoffNeedsNext::AskAgain, string $reason = ''): AHandoff
{
    return AHandoff::answered(
        SomebodyInTheHousehold::called('Sam'),
        $stands,
        $reason,
        $next,
        WhatToHandThem::of(
            AnAddressToHand::at('https://den.local:8920', 'That address answers only on the home network.'),
            TheStepsOnTheirDevice::of('Open the app on their device and give it the server.', 'Sign in as Sam with their own password.'),
            TheClientsToHandOver::of(
                AClientToHandOver::named('An iPhone', 'Swiftfin', openSource: true, code: 'swiftfin://server?url=https://den.local:8920', deepLink: true),
                AClientToHandOver::named('A smart TV', 'Jellyfin for TV', openSource: false, code: 'https://den.local:8920', deepLink: false),
            ),
        ),
        AMomentAsWritten::of('2026-09-30T20:00:00Z'),
        TheSignedInDevices::of(
            ASignedInDevice::listed('Sam\'s laptop', 'Jellyfin Web', AMomentAsWritten::of('2026-09-30T20:55:00Z')),
            ASignedInDevice::listed('An old tablet', 'Jellyfin Android', AMomentAsWritten::of('')),
        ),
    );
}

/** The screen, about Sam, with a stack it knows and a session where a case says. */
function theConnectingScreen(
    AStackThatHandsDevicesOver $handing,
    ?ACodeOfWhatItWasGiven $encoding = null,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
    string $named = 'Sam',
): ConnectingADeviceForThem {
    $stack = theStackADeviceConnectsTo();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new ConnectingADeviceForThem(
        $handing,
        $encoding ?? ACodeOfWhatItWasGiven::working(),
        FrozenClock::at(Instant::atEpochSeconds(THE_HOUR_AFTER)),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        new AppsSettingsThatOpen(),
    );
    $screen->setParams(['stack' => $stack->id()->stored(), 'service' => $named]);

    return $screen;
}

/** The screen, after the stack answered where the hand-off stands. */
function theConnectingScreenAnswered(AHandoff $handoff, ?ACodeOfWhatItWasGiven $encoding = null): ConnectingADeviceForThem
{
    $screen = theConnectingScreen(AStackThatHandsDevicesOver::answering(WhatBecameOfTheHandoff::answered($handoff)), $encoding);
    $screen->show();

    return $screen;
}

it('asks nothing on open, and offers to show the code for the member it names', function (): void {
    $handing = AStackThatHandsDevicesOver::whichTookItOn(WhatBecameOfTheHandoff::answered(aHandoffThatStands(WhereTheHandoffStands::Ready)));
    $screen = theConnectingScreen($handing);
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($handing->asked())->toBe([])
        ->and($drawn)->toContain(__('stacks.handoff.title'))
        ->and($drawn)->toContain('Sam')
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('stacks.handoff.show'));
});

it('says the stack is working on it, follows it by its handle, and asks for the member named', function (): void {
    $handing = AStackThatHandsDevicesOver::whichTookItOn(WhatBecameOfTheHandoff::underway(Job::named(AStackThatHandsDevicesOver::THE_JOB)));
    $screen = theConnectingScreen($handing);
    $screen->show();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.invitation.working'))
        ->and($screen->following)->toBe(AStackThatHandsDevicesOver::THE_JOB)
        ->and($handing->asked())->toBe(['Sam']);

    $screen->whileItRuns();
    WhatTheDeviceWouldDraw::by($screen);

    expect($handing->followed())->toHaveCount(1);
});

it('shows the address as a code and as text, the steps, the apps and when the code was first given', function (): void {
    $encoding = ACodeOfWhatItWasGiven::working();
    $screen = theConnectingScreenAnswered(aHandoffThatStands(WhereTheHandoffStands::Ready), $encoding);
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($encoding->given()?->carried())->toBe('https://den.local:8920')
        ->and($drawn)->toContain(__('stacks.handoff.stands.ready'))
        ->and($drawn)->toContain('https://den.local:8920')
        ->and($drawn)->toContain('That address answers only on the home network.')
        ->and($drawn)->toContain(__('stacks.handoff.on_their_device'))
        ->and($drawn)->toContain('Sign in as Sam with their own password.')
        ->and($drawn)->toContain(__('stacks.handoff.which_app'))
        ->and($drawn)->toContain('Swiftfin')
        ->and($drawn)->toContain(__('stacks.handoff.opens_at_this_server', ['client' => 'Swiftfin']))
        ->and($drawn)->toContain('swiftfin://server?url=https://den.local:8920')
        ->and($drawn)->not->toContain(__('stacks.handoff.opens_at_this_server', ['client' => 'Jellyfin for TV']))
        ->and(array_filter($drawn, static fn(string $line): bool => $line === __('stacks.clients.not_open_source')))->toHaveCount(1)
        ->and($drawn)->toContain(__('stacks.handoff.first_given', ['when' => trans_choice('health.ago.hours', 1)]));
});

it('lists the devices signed in, with when each was last seen where the media server said', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theConnectingScreenAnswered(aHandoffThatStands(WhereTheHandoffStands::Connected, next: WhatTheHandoffNeedsNext::Nothing)))->said();
    $lastSeen = __('stacks.handoff.last_seen', ['when' => '']);

    expect($drawn)->toContain(__('stacks.handoff.stands.connected'))
        ->and($drawn)->toContain(__('stacks.handoff.signed_in_devices'))
        ->and($drawn)->toContain('Sam\'s laptop')
        ->and($drawn)->toContain('An old tablet')
        ->and($drawn)->toContain(__('stacks.handoff.last_seen', ['when' => trans_choice('health.ago.minutes', 5, ['count' => 5])]))
        ->and(array_filter($drawn, static fn(string $line): bool => is_string($lastSeen) && str_starts_with($line, $lastSeen)))->toHaveCount(1);
});

it('hands no code over once a device has signed in, and still offers to ask again', function (): void {
    $screen = theConnectingScreenAnswered(aHandoffThatStands(WhereTheHandoffStands::Connected, next: WhatTheHandoffNeedsNext::Nothing));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->not->toContain(__('stacks.handoff.on_their_device'))
        ->and($drawn)->not->toContain('https://den.local:8920')
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('health.ask_again'));
});

it('says it is waiting for them in the stack\'s words, and asks again only when tapped', function (): void {
    $handing = AStackThatHandsDevicesOver::answering(WhatBecameOfTheHandoff::answered(aHandoffThatStands(WhereTheHandoffStands::Pending, reason: 'No device of theirs has signed in since the code was given.')));
    $screen = theConnectingScreen($handing);
    $screen->show();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain(__('stacks.handoff.stands.pending'))
        ->and($drawn)->toContain('No device of theirs has signed in since the code was given.')
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('health.ask_again'))
        ->and($handing->asked())->toBe(['Sam'])
        ->and($handing->followed())->toBe([]);

    $screen->show();
    WhatTheDeviceWouldDraw::by($screen);

    expect($handing->asked())->toBe(['Sam', 'Sam']);
});

it('offers to invite somebody with no account, with their name already typed', function (): void {
    $screen = theConnectingScreenAnswered(aHandoffThatStands(WhereTheHandoffStands::Unprovisioned, WhatTheHandoffNeedsNext::Invite, 'Nobody called Sam has an account yet.'));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();
    $stack = theStackADeviceConnectsTo()->id();

    expect($drawn)->toContain('Nobody called Sam has an account yet.')
        ->and($drawn)->not->toContain(__('stacks.handoff.on_their_device'))
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('stacks.handoff.invite_them'))
        ->and($screen->goes()->whoGetsIn()->inviting('Sam'))->toBe(AStacksScreen::InviteNamed->forTheStacksMember($stack, SomebodyInTheHousehold::called('Sam')))
        ->and(NativeRouter::resolve($screen->goes()->whoGetsIn()->inviting('a/b')))->not->toBeNull();
});

it('sends what else there is to do to the screen that does it', function (WhatTheHandoffNeedsNext $next, string $said): void {
    $screen = theConnectingScreenAnswered(aHandoffThatStands(WhereTheHandoffStands::Failed, $next, 'It could not go ahead.'));

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.handoff.stands.failed'))
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__($said))
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->not->toContain(__('health.ask_again'));
})->with([
    'starting the media server' => [WhatTheHandoffNeedsNext::StartServer, 'navigation.services'],
    'recording the address' => [WhatTheHandoffNeedsNext::RecordAddress, TheMenu::FrontDoor->said()],
]);

it('says in words where the address could not be drawn, and still gives it as text', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theConnectingScreenAnswered(aHandoffThatStands(WhereTheHandoffStands::Ready), ACodeOfWhatItWasGiven::drawingNothing()))->said();

    expect($drawn)->toContain(__('stacks.invitation.no_code'))
        ->and($drawn)->toContain('https://den.local:8920');
});

it('draws the answer once rather than on every look', function (): void {
    $encoding = ACodeOfWhatItWasGiven::working();
    $handing = AStackThatHandsDevicesOver::answering(WhatBecameOfTheHandoff::answered(aHandoffThatStands(WhereTheHandoffStands::Ready)));
    $screen = theConnectingScreen($handing, $encoding);
    $screen->show();
    WhatTheDeviceWouldDraw::by($screen);
    $screen->whileItRuns();
    WhatTheDeviceWouldDraw::by($screen);

    expect($handing->followed())->toBe([])
        ->and($handing->asked())->toBe(['Sam']);
});

it('says a refusal in the stack\'s own words, and offers to ask again', function (): void {
    $said = 'Sam is not a name the media server knows';
    $screen = theConnectingScreen(AStackThatHandsDevicesOver::answering(WhatBecameOfTheHandoff::refused($said)));
    $screen->show();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain($said)
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('stacks.handoff.show'));
});

it('goes back to offering the code where the stack has no outcome for the one asked for', function (): void {
    $screen = theConnectingScreen(AStackThatHandsDevicesOver::whichTookItOn(WhatBecameOfTheHandoff::ended()));
    $screen->show();
    $screen->whileItRuns();

    expect(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('stacks.handoff.show'))
        ->and($screen->following)->toBeNull();
});

it('says what stood in the way, and lets go of a session the stack refused', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theConnectingScreen(AStackThatHandsDevicesOver::answering(WhatBecameOfTheHandoff::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))), keychain: $keychain);
    $screen->show();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__(KindOfObstacle::StackDidNotAnswer->said()));

    $refusing = theConnectingScreen(AStackThatHandsDevicesOver::answering(WhatBecameOfTheHandoff::met(Obstacle::of(KindOfObstacle::CredentialWasRefused))), keychain: $keychain);
    $refusing->show();

    expect($keychain->isHolding(theStackADeviceConnectsTo()->id()))->toBeFalse();
});

it('asks nothing of a stack it holds no session for, and says the session has ended', function (): void {
    $handing = AStackThatHandsDevicesOver::answering(WhatBecameOfTheHandoff::answered(aHandoffThatStands(WhereTheHandoffStands::Ready)));
    $screen = theConnectingScreen($handing, signedIn: false);
    $screen->show();

    expect($handing->asked())->toBe([])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('connection.session_has_ended'));
});

it('asks about nobody when the route names nobody', function (): void {
    $handing = AStackThatHandsDevicesOver::answering(WhatBecameOfTheHandoff::answered(aHandoffThatStands(WhereTheHandoffStands::Ready)));
    $screen = theConnectingScreen($handing, named: ' ');
    $screen->show();

    expect($handing->asked())->toBe([])
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('stacks.handoff.show'));
});

it('is offered on each member\'s card, told apart by their name', function (): void {
    $keychain = AKeychainInMemory::working();
    $keychain->keep(theStackADeviceConnectsTo()->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    $household = new AskingSomebodyIn(
        AStackThatInvites::answering()->holding(TheMembers::of(AMember::joined('anna'), AMember::stillInvited('bob'))),
        ACodeOfWhatItWasGiven::working(),
        AShareSheetThatWasOffered::working(),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding(theStackADeviceConnectsTo())),
        app(Translator::class),
        new AppsSettingsThatOpen(),
    );
    $household->setParams(['stack' => theStackADeviceConnectsTo()->id()->stored()]);
    $offers = WhatTheDeviceWouldDraw::by($household)->offers();

    expect($offers)->toContain(__('stacks.handoff.title_for', ['name' => 'anna']))
        ->and($offers)->toContain(__('stacks.handoff.title_for', ['name' => 'bob']));
});

it('opens asking somebody in with the name it was sent with already typed', function (): void {
    $household = new AskingSomebodyIn(
        AStackThatInvites::answering(),
        ACodeOfWhatItWasGiven::working(),
        AShareSheetThatWasOffered::working(),
        AKeychainInMemory::working(),
        AroundThePhone::holding(StacksInMemory::holding(theStackADeviceConnectsTo())),
        app(Translator::class),
        new AppsSettingsThatOpen(),
    );
    $household->mount('Sam');
    $blank = clone $household;
    $blank->name = '';
    $blank->mount(' ');

    expect($household->name)->toBe('Sam')
        ->and($blank->name)->toBe('');
});

it('the way here is a route, with the name encoded', function (): void {
    $screen = theConnectingScreen(AStackThatHandsDevicesOver::answering(WhatBecameOfTheHandoff::ended()));
    $stack = theStackADeviceConnectsTo()->id();

    expect(NativeRouter::resolve($screen->goes()->whoGetsIn()->connecting('a/b')))->not->toBeNull()
        ->and($screen->goes()->whoGetsIn()->connecting('a/b'))->toBe(sprintf('/stacks/%s/device/a%%2Fb', $stack->stored()))
        ->and(NativeRouter::resolve($screen->goes()->whoGetsIn()->frontDoor()))->not->toBeNull();
});

it('renders its own view', function (): void {
    expect(theConnectingScreen(AStackThatHandsDevicesOver::answering(WhatBecameOfTheHandoff::ended()))->render()->name())->toBe('operator::connecting-a-device');
});
