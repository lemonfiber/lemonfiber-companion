<?php

declare(strict_types=1);

use Illuminate\Contracts\Translation\Translator;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AMember;
use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\AnInvitation;
use Modules\Kernel\Api\AnInvitationToHand;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowOften;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheLibraries;
use Modules\Kernel\Api\TheMembers;
use Modules\Kernel\Api\WhatBecameOfTheInvitation;
use Modules\Kernel\Api\WhatBecomesOfUnrated;
use Modules\Kernel\Api\WhatWasGranted;
use Modules\Kernel\Api\WhereTheInvitationStands;
use Modules\Kernel\Api\WhetherTheyCanAsk;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhoWasTakenBack;
use Modules\Kernel\Api\WhyNothingWasShared;
use Modules\Operator\Internal\Screens\AskingSomebodyIn;
use Modules\Operator\Internal\ViewModels\AMemberAsShown;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\ACodeOfWhatItWasGiven;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AShareSheetThatWasOffered;
use Tests\Support\Fakes\AStackThatInvites;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// Asking somebody in: what an invitation grants before it is sent, sending
// it, handing it over, and letting somebody choose a new password.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

/** The machine somebody is asked in to, at an address no line on the screen may be built from. */
function theStackSomebodyIsAskedIn(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** What the stack answers for Anna, rehearsed or carried out, finding what is given. */
function annasInvitation(bool $rehearsed, WhereTheInvitationStands $standing = WhereTheInvitationStands::Made): AnInvitation
{
    $toHand = AnInvitationToHand::to('anna', AnAddressToHand::at('http://192.168.1.42:8096', 'The number can change when the router restarts'), 72);
    $linked = $rehearsed ? WhetherTheyCanAsk::NotTried : WhetherTheyCanAsk::NotYet;
    $withdrawn = WhoWasTakenBack::of('bob');
    $invitation = $rehearsed
        ? AnInvitation::rehearsed($toHand, $standing, $linked, $withdrawn)
        : AnInvitation::carriedOut($toHand, $standing, $linked, $withdrawn);

    return $invitation->granting(WhatWasGranted::granted(TheLibraries::of('Films', 'Kids'), WhatBecomesOfUnrated::HeldBack, $linked, 'A limit holds back what is rated above it, and nothing else', 'PG-13'));
}

/**
 * The work, and then what it came to.
 *
 * @return list<WhatBecameOfTheInvitation>
 */
function theWorkThenIts(AnInvitation $invitation): array
{
    return [WhatBecameOfTheInvitation::underway(Job::named('j-1')), WhatBecameOfTheInvitation::answered($invitation)];
}

/** A line of the catalogue, as the words it holds. Named for this file (`G10`). */
function whatTheInvitationCatalogueSays(string $key): string
{
    $said = __($key);

    return is_string($said) ? $said : $key;
}

/** The screen, with a stack it knows and a keychain holding whatever a test says. Named for this file (`G10`). */
function theInvitationScreen(
    AStackThatInvites $inviting,
    ?AShareSheetThatWasOffered $sharing = null,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
    ?ACodeOfWhatItWasGiven $encoding = null,
): AskingSomebodyIn {
    $stack = theStackSomebodyIsAskedIn();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new AskingSomebodyIn(
        $inviting,
        $encoding ?? ACodeOfWhatItWasGiven::working(),
        $sharing ?? AShareSheetThatWasOffered::working(),
        $keychain,
        StacksInMemory::holding($stack),
        app(Translator::class),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/** A screen with Anna's invitation typed into it. */
function annaTypedInto(AskingSomebodyIn $screen): AskingSomebodyIn
{
    $screen->name = ' anna ';
    $screen->libraries = 'Films, , Kids';
    $screen->age = ' 12 ';
    $screen->unratedIs('held-back');

    return $screen;
}

/** A screen that has asked what inviting Anna would do, and been answered. */
function annaRehearsedOn(AStackThatInvites $inviting, ?AShareSheetThatWasOffered $sharing = null): AskingSomebodyIn
{
    $screen = annaTypedInto(theInvitationScreen($inviting, $sharing));
    $screen->offer();
    $screen->whileItRuns();
    $screen->howItIsGoing();

    return $screen;
}

it('opens on the fields and the question, and asks the stack nothing', function (): void {
    $inviting = AStackThatInvites::answering();
    $drawn = WhatTheDeviceWouldDraw::by(theInvitationScreen($inviting));

    expect($drawn->offers())->toContain(__('stacks.invitation.what_would_it_grant'))
        ->and($drawn->said())->toContain(__('stacks.invitation.unrated_is', ['choice' => whatTheInvitationCatalogueSays('stacks.invitation.unrated.left_to_the_stack')]))
        ->and($inviting->asked())->toBe([]);
});

it('says what was typed cannot be asked, and asks nothing', function (): void {
    $inviting = AStackThatInvites::answering();
    $unnamed = theInvitationScreen($inviting);
    $unnamed->name = '  ';
    $unnamed->offer();
    $aged = theInvitationScreen($inviting);
    $aged->name = 'anna';
    $aged->age = 'twelve';
    $aged->offer();

    expect(WhatTheDeviceWouldDraw::by($unnamed)->said())->toContain(__('stacks.invitation.needs_a_name'))
        ->and(WhatTheDeviceWouldDraw::by($aged)->said())->toContain(__('stacks.invitation.age_is_a_number'))
        ->and($inviting->asked())->toBe([]);
});

it('asks for the rehearsal with what was typed, and says it is working while it runs', function (): void {
    $inviting = AStackThatInvites::answering(WhatBecameOfTheInvitation::underway(Job::named('j-1')));
    $screen = annaTypedInto(theInvitationScreen($inviting));
    $screen->offer();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();
    $asked = $inviting->rehearsedWith();

    expect($inviting->asked())->toBe(['would'])
        ->and($asked?->name())->toBe('anna')
        ->and(iterator_to_array($asked?->libraries() ?? TheLibraries::of(), preserve_keys: true))->toBe(['Films', 'Kids'])
        ->and($asked?->age(
            upTo: static fn(int $age): WhatTheScreenSent => new WhatTheScreenSent((string) $age),
            none: static fn(): WhatTheScreenSent => new WhatTheScreenSent('none'),
        )->said)->toBe('12')
        ->and($asked?->unrated(
            chosen: static fn(WhatBecomesOfUnrated $unrated): WhatTheScreenSent => new WhatTheScreenSent($unrated->value),
            unsaid: static fn(): WhatTheScreenSent => new WhatTheScreenSent('unsaid'),
        )->said)->toBe(WhatBecomesOfUnrated::HeldBack->value)
        ->and($drawn)->toContain(__('stacks.invitation.working'))
        ->and($drawn)->toContain(__(HowOften::WhileWorkRuns->saidOnTheScreen(), ['count' => HowOften::WhileWorkRuns->seconds()]));
});

it('reads no age and leaves unrated material to the stack where nothing was said of either', function (): void {
    $inviting = AStackThatInvites::answering(WhatBecameOfTheInvitation::underway(Job::named('j-1')));
    $screen = theInvitationScreen($inviting);
    $screen->name = 'anna';
    $screen->unratedIs('whatever');
    $screen->offer();
    $asked = $inviting->rehearsedWith();

    expect($screen->unrated)->toBe('')
        ->and($asked?->age(
            upTo: static fn(int $age): WhatTheScreenSent => new WhatTheScreenSent((string) $age),
            none: static fn(): WhatTheScreenSent => new WhatTheScreenSent('none'),
        )->said)->toBe('none')
        ->and($asked?->unrated(
            chosen: static fn(WhatBecomesOfUnrated $unrated): WhatTheScreenSent => new WhatTheScreenSent($unrated->value),
            unsaid: static fn(): WhatTheScreenSent => new WhatTheScreenSent('unsaid'),
        )->said)->toBe('unsaid')
        ->and(iterator_to_array($asked?->libraries() ?? TheLibraries::of('x'), preserve_keys: true))->toBe([]);
});

/** One word carried out of an arm. Named for this file (`G10`). */
final readonly class WhatTheScreenSent
{
    public function __construct(public string $said) {}
}

it('says what the invitation would grant, and when it lapses, before anything is sent', function (): void {
    $inviting = AStackThatInvites::answering(...theWorkThenIts(annasInvitation(rehearsed: true)));
    $screen = annaRehearsedOn($inviting);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($inviting->asked())->toBe(['would', 'after:j-1'])
        ->and($drawn->said())->toContain(__('stacks.invitation.rehearsed', ['name' => 'anna']))
        ->and($drawn->said())->toContain('Films')
        ->and($drawn->said())->toContain('Kids')
        ->and($drawn->said())->toContain(__('stacks.invitation.limited_to', ['limit' => 'PG-13']))
        ->and($drawn->said())->toContain('A limit holds back what is rated above it, and nothing else')
        ->and($drawn->said())->toContain(__('stacks.invitation.unrated_is', ['choice' => whatTheInvitationCatalogueSays(WhatBecomesOfUnrated::HeldBack->saidOnTheScreen())]))
        ->and($drawn->said())->toContain(__(WhetherTheyCanAsk::NotTried->saidOnTheScreen()))
        ->and($drawn->said())->toContain(trans_choice('stacks.invitation.lapses', 72))
        ->and($drawn->said())->toContain(__('stacks.invitation.would_withdraw'))
        ->and($drawn->said())->toContain('bob')
        ->and($drawn->offers())->toContain(__('stacks.invitation.send', ['name' => 'anna']));
});

it('never draws a rehearsal as an account that exists: no address, no code, nothing to hand over', function (): void {
    $screen = annaRehearsedOn(AStackThatInvites::answering(...theWorkThenIts(annasInvitation(rehearsed: true))));
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->not->toContain('http://192.168.1.42:8096')
        ->and($drawn->said())->not->toContain(__('stacks.invitation.code'))
        ->and($drawn->offers())->not->toContain(__('stacks.invitation.pass_on'));
});

it('sends what the rehearsal was asked with, not what the fields say now', function (): void {
    $inviting = AStackThatInvites::answering(...theWorkThenIts(annasInvitation(rehearsed: true)), ...theWorkThenIts(annasInvitation(rehearsed: false)));
    $screen = annaRehearsedOn($inviting);
    $shown = $inviting->rehearsedWith();
    $screen->name = 'somebody else';
    $screen->age = '';
    $screen->send();

    expect($inviting->asked())->toBe(['would', 'after:j-1', 'invite'])
        ->and($inviting->agreedTo()?->asked())->toBe($shown);
});

it('hands over the address the stack gave, with its caution, as text, as a code, and through the device\'s sharing', function (): void {
    $encoding = ACodeOfWhatItWasGiven::working();
    $inviting = AStackThatInvites::answering(...theWorkThenIts(annasInvitation(rehearsed: true)), ...theWorkThenIts(annasInvitation(rehearsed: false)));
    $screen = annaTypedInto(theInvitationScreen($inviting, encoding: $encoding));
    $screen->offer();
    $screen->again();
    $screen->howItIsGoing();
    $screen->send();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->not->toContain(__('stacks.invitation.rehearsed', ['name' => 'anna']))
        ->and($drawn->said())->toContain(__(WhereTheInvitationStands::Made->saidOnTheScreen(), ['name' => 'anna']))
        ->and($drawn->said())->toContain('http://192.168.1.42:8096')
        ->and($drawn->said())->toContain('The number can change when the router restarts')
        ->and($drawn->said())->toContain(__('stacks.invitation.code'))
        ->and($drawn->said())->toContain(__(WhetherTheyCanAsk::NotYet->saidOnTheScreen()))
        ->and($drawn->said())->toContain(__('stacks.invitation.withdrew'))
        ->and($drawn->offers())->toContain(__('stacks.invitation.pass_on'))
        ->and($drawn->offers())->not->toContain(__('stacks.invitation.send', ['name' => 'anna']))
        ->and($encoding->given()?->url())->toBe('http://192.168.1.42:8096')
        ->and(implode("\n", $drawn->said()))->not->toContain('8443');
});

it('says no code could be drawn, and still offers the address as text', function (): void {
    $inviting = AStackThatInvites::answering(WhatBecameOfTheInvitation::answered(annasInvitation(rehearsed: false)));
    $screen = theInvitationScreen($inviting, encoding: ACodeOfWhatItWasGiven::drawingNothing());
    $screen->name = 'anna';
    $screen->offer();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__('stacks.invitation.no_code'))
        ->and($drawn->said())->toContain('http://192.168.1.42:8096')
        ->and($drawn->said())->not->toContain(__('stacks.invitation.code'));
});

it('passes the invitation on with the address and its caution, and says it went to the sheet', function (): void {
    $sharing = AShareSheetThatWasOffered::working();
    $inviting = AStackThatInvites::answering(WhatBecameOfTheInvitation::answered(annasInvitation(rehearsed: false)));
    $screen = theInvitationScreen($inviting, $sharing);
    $screen->name = 'anna';
    $screen->offer();
    $screen->passOn();

    expect($sharing->passed()?->named())->toBe('anna')
        ->and($sharing->passed()?->text())->toBe(sprintf(
            "%s\n\nhttp://192.168.1.42:8096\n\nThe number can change when the router restarts",
            trans_choice('stacks.invitation.covering', 72, ['name' => 'anna', 'stack' => 'The loft']),
        ))
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.invitation.passed_on'));
});

it('says nothing was sent where the device would not pass it on, and keeps the address on the screen', function (): void {
    $inviting = AStackThatInvites::answering(WhatBecameOfTheInvitation::answered(annasInvitation(rehearsed: false)));
    $screen = theInvitationScreen($inviting, AShareSheetThatWasOffered::refusing(WhyNothingWasShared::TheDeviceWouldNotOffer));
    $screen->name = 'anna';
    $screen->offer();
    $screen->passOn();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain(__('stacks.invitation.not_passed_on'))
        ->and($drawn)->toContain('http://192.168.1.42:8096');
});

it('neither sends nor passes on anything that was not offered for it', function (): void {
    $sharing = AShareSheetThatWasOffered::working();
    $inviting = AStackThatInvites::answering(...theWorkThenIts(annasInvitation(rehearsed: true)));
    $fresh = theInvitationScreen($inviting, $sharing);
    $fresh->send();
    $fresh->passOn();
    $rehearsed = annaRehearsedOn($inviting, $sharing);
    $rehearsed->passOn();

    expect($inviting->asked())->toBe(['would', 'after:j-1'])
        ->and($sharing->passed())->toBeNull();
});

it('offers nothing to send for somebody already in the household, nothing to hand them, and no lapse', function (): void {
    $inviting = AStackThatInvites::answering(...theWorkThenIts(annasInvitation(rehearsed: true, standing: WhereTheInvitationStands::Joined)));
    $screen = annaRehearsedOn($inviting);
    $drawn = WhatTheDeviceWouldDraw::by($screen);
    $screen->send();

    expect($drawn->said())->toContain(__(WhereTheInvitationStands::Joined->saidOnTheScreen(), ['name' => 'anna']))
        ->and($drawn->offers())->not->toContain(__('stacks.invitation.send', ['name' => 'anna']))
        ->and($drawn->said())->not->toContain(trans_choice('stacks.invitation.lapses', 72))
        ->and($inviting->asked())->toBe(['would', 'after:j-1']);

    $carriedOut = theInvitationScreen(AStackThatInvites::answering(WhatBecameOfTheInvitation::answered(annasInvitation(rehearsed: false, standing: WhereTheInvitationStands::Joined))));
    $carriedOut->name = 'anna';
    $carriedOut->offer();

    expect(WhatTheDeviceWouldDraw::by($carriedOut)->said())->not->toContain('http://192.168.1.42:8096')
        ->and(WhatTheDeviceWouldDraw::by($carriedOut)->offers())->not->toContain(__('stacks.invitation.pass_on'));
});

it('says an invitation wrote nothing about access, rather than that it opens every library', function (): void {
    $bare = AnInvitation::carriedOut(
        AnInvitationToHand::to('anna', AnAddressToHand::at('http://loft.local:8096', ''), 1),
        WhereTheInvitationStands::Waiting,
        WhetherTheyCanAsk::Made,
        WhoWasTakenBack::of(),
    );
    $open = $bare->granting(WhatWasGranted::granted(TheLibraries::of(), WhatBecomesOfUnrated::LetThrough, WhetherTheyCanAsk::Made, 'A limit is not a lock', ''));
    $drawnBare = WhatTheDeviceWouldDraw::by(anInvitationAnsweredWith($bare))->said();
    $drawnOpen = WhatTheDeviceWouldDraw::by(anInvitationAnsweredWith($open))->said();

    expect($drawnBare)->toContain(__('stacks.invitation.grants_nothing'))
        ->and($drawnBare)->not->toContain(__('stacks.invitation.every_library'))
        ->and($drawnBare)->toContain(trans_choice('stacks.invitation.lapses', 1))
        ->and($drawnBare)->toContain(__('stacks.invitation.nobody_withdrawn'))
        ->and($drawnOpen)->toContain(__('stacks.invitation.every_library'))
        ->and($drawnOpen)->toContain(__('stacks.invitation.no_limit'))
        ->and($drawnOpen)->not->toContain(__('stacks.invitation.grants_nothing'));
});

/** A screen the stack answered at once with this invitation. */
function anInvitationAnsweredWith(AnInvitation $invitation): AskingSomebodyIn
{
    $screen = theInvitationScreen(AStackThatInvites::answering(WhatBecameOfTheInvitation::answered($invitation)));
    $screen->name = 'anna';
    $screen->offer();

    return $screen;
}

it('shows a refusal with the stack\'s reason and the name asked for, and not as something to try again', function (): void {
    $screen = theInvitationScreen(AStackThatInvites::answering(WhatBecameOfTheInvitation::refused('There is no library called Cartoons')));
    $screen->name = 'anna';
    $screen->offer();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__('stacks.invitation.refused', ['name' => 'anna']))
        ->and($drawn->said())->toContain('There is no library called Cartoons')
        ->and($drawn->offers())->not->toContain(__('health.ask_again'))
        ->and($drawn->offers())->toContain(__('stacks.invitation.start_again'));
});

it('says the stack has no outcome for work it no longer knows, which is not a refusal', function (): void {
    $inviting = AStackThatInvites::answering(WhatBecameOfTheInvitation::underway(Job::named('j-1')));
    $screen = annaRehearsedOn($inviting);
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain(__('stacks.invitation.no_outcome', ['name' => 'anna']))
        ->and($inviting->asked())->toBe(['would', 'after:j-1']);
});

it('asks after running work only while it runs', function (): void {
    $inviting = AStackThatInvites::answering(...theWorkThenIts(annasInvitation(rehearsed: true)));
    $screen = annaRehearsedOn($inviting);
    $screen->whileItRuns();
    $screen->howItIsGoing();

    expect($inviting->asked())->toBe(['would', 'after:j-1'])
        ->and($screen->cadence())->toBe(HowOften::WhileWorkRuns);
});

it('starts again keeping what was typed, and drops what the stack said', function (): void {
    $screen = annaRehearsedOn(AStackThatInvites::answering(...theWorkThenIts(annasInvitation(rehearsed: true))));
    $screen->startAgain();

    expect($screen->howItIsGoing()->invitation)->toBeNull()
        ->and($screen->name)->toBe(' anna ')
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('stacks.invitation.what_would_it_grant'));
});

it('drops what was handed over and a password about to be taken off when starting again', function (): void {
    $carriedOut = WhatBecameOfTheInvitation::answered(annasInvitation(rehearsed: false));
    $inviting = AStackThatInvites::answering($carriedOut, $carriedOut)
        ->holding(TheMembers::of(AMember::joined('anna')));
    $screen = theInvitationScreen($inviting);
    $screen->name = 'anna';
    $screen->offer();
    $screen->passOn();
    $screen->wouldTakeThePasswordOff('anna');
    $screen->startAgain();
    $drawn = WhatTheDeviceWouldDraw::by($screen);
    $screen->takeThePasswordOff();
    $screen->offer();
    $handedOverAgain = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->offers())->not->toContain(__('stacks.invitation.take_it_off', ['name' => 'anna']))
        ->and($handedOverAgain->offers())->toContain(__('stacks.invitation.pass_on'))
        ->and($handedOverAgain->said())->not->toContain(__('stacks.invitation.passed_on'))
        ->and($screen->passedOn)->toBe('')
        ->and($inviting->asked())->toBe(['would', 'would']);
});

it('sends nothing once another name is asked about, even before the stack has answered it', function (): void {
    $inviting = AStackThatInvites::answering(...[
        ...theWorkThenIts(annasInvitation(rehearsed: true)),
        WhatBecameOfTheInvitation::underway(Job::named('j-2')),
    ]);
    $screen = annaRehearsedOn($inviting);
    $screen->name = 'bob';
    $screen->offer();
    $screen->send();

    expect($inviting->asked())->toBe(['would', 'after:j-1', 'would']);
});

it('sends nothing where what was asked, or what was offered, is no longer held', function (): void {
    $inviting = AStackThatInvites::answering(...theWorkThenIts(annasInvitation(rehearsed: true)), ...theWorkThenIts(annasInvitation(rehearsed: true)));
    $forgotTheAsking = annaRehearsedOn($inviting);
    $forgotTheAsking->asked = null;
    $forgotTheAsking->send();
    $forgotTheOffer = annaRehearsedOn($inviting);
    $forgotTheOffer->invitation = null;
    $forgotTheOffer->send();

    expect($inviting->asked())->toBe(['would', 'after:j-1', 'would', 'after:j-1']);
});

it('lets go of the one picked when a name the reading did not list is picked after them', function (): void {
    $inviting = AStackThatInvites::answering()->holding(TheMembers::of(AMember::joined('anna')));
    $screen = theInvitationScreen($inviting);
    $screen->wouldTakeThePasswordOff('anna');
    $screen->wouldTakeThePasswordOff('somebody not listed');
    $screen->takeThePasswordOff();

    expect($screen->member)->toBe('')
        ->and($inviting->asked())->toBe([]);
});

it('lets unrated material be held back, let through, or left to the stack', function (): void {
    $screen = theInvitationScreen(AStackThatInvites::answering());

    $screen->unratedIs('let-through');
    $through = $screen->unratedSaid();
    $screen->unratedIs('held-back');
    $back = $screen->unratedSaid();
    $screen->unratedIs('');

    expect([$through, $back, $screen->unratedSaid()])->toBe([
        WhatBecomesOfUnrated::LetThrough->saidOnTheScreen(),
        WhatBecomesOfUnrated::HeldBack->saidOnTheScreen(),
        'stacks.invitation.unrated.left_to_the_stack',
    ]);
});

it('opens on who is in: joined, or an invitation still out, and asks once a frame', function (): void {
    $inviting = AStackThatInvites::answering()->holding(TheMembers::of(AMember::joined('anna'), AMember::stillInvited('bob')));
    $screen = theInvitationScreen($inviting);
    $drawn = WhatTheDeviceWouldDraw::by($screen);
    $screen->answer();

    expect($drawn->said())->toContain(__('stacks.invitation.who_is_in'))
        ->and($drawn->said())->toContain('anna')
        ->and($drawn->said())->toContain(__('stacks.invitation.member.joined'))
        ->and($drawn->said())->toContain('bob')
        ->and($drawn->said())->toContain(__('stacks.invitation.member.still_invited'))
        ->and($drawn->offers())->toContain(__('stacks.invitation.would_take_it_off', ['name' => 'anna']))
        ->and($drawn->offers())->toContain(__('stacks.invitation.would_take_it_off', ['name' => 'bob']))
        ->and(array_map(static fn(AMemberAsShown $member): array => [$member->name, $member->standingSaid], $screen->answer()->members))->toBe([
            ['anna', 'stacks.invitation.member.joined'],
            ['bob', 'stacks.invitation.member.still_invited'],
        ])
        ->and($inviting->readings())->toBe(1);
});

it('says nobody is in yet, rather than drawing nothing', function (): void {
    expect(WhatTheDeviceWouldDraw::by(theInvitationScreen(AStackThatInvites::answering()))->said())->toContain(__('stacks.invitation.nobody_in'));
});

it('asks who is in again once an account is made or changed, and not for a rehearsal', function (): void {
    $inviting = AStackThatInvites::answering(...theWorkThenIts(annasInvitation(rehearsed: true)), ...theWorkThenIts(annasInvitation(rehearsed: false)));
    $screen = annaTypedInto(theInvitationScreen($inviting));
    $screen->answer();
    $screen->offer();
    $screen->again();
    $screen->howItIsGoing();
    $screen->answer();
    $readAfterTheRehearsal = $inviting->readings();
    $screen->send();
    $screen->whileItRuns();
    $screen->howItIsGoing();
    $screen->answer();

    expect($readAfterTheRehearsal)->toBe(2)
        ->and($inviting->readings())->toBe(3);
});

it('takes a password off only once asked twice, for somebody listed, naming them and nothing else', function (): void {
    $inviting = AStackThatInvites::answering(...theWorkThenIts(annasInvitation(rehearsed: false, standing: WhereTheInvitationStands::Reset)))
        ->holding(TheMembers::of(AMember::joined('anna')));
    $screen = theInvitationScreen($inviting);
    $screen->takeThePasswordOff();
    $screen->wouldTakeThePasswordOff('somebody not listed');
    $notListed = $screen->member;
    $screen->takeThePasswordOff();
    $screen->wouldTakeThePasswordOff('anna');
    $asking = WhatTheDeviceWouldDraw::by($screen);
    $screen->takeThePasswordOff();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($notListed)->toBe('')
        ->and($asking->said())->toContain(__('stacks.invitation.taking_it_off_means', ['name' => 'anna']))
        ->and($asking->offers())->toContain(__('stacks.invitation.take_it_off', ['name' => 'anna']))
        ->and($asking->offers())->not->toContain(__('stacks.invitation.would_take_it_off', ['name' => 'anna']))
        ->and($inviting->asked())->toBe(['reset', 'after:j-1'])
        ->and($inviting->tookTheirsOff()?->name())->toBe('anna')
        ->and($screen->member)->toBe('')
        ->and($drawn->said())->toContain(__(WhereTheInvitationStands::Reset->saidOnTheScreen(), ['name' => 'anna']))
        ->and($drawn->said())->toContain('http://192.168.1.42:8096');
});

it('leaves a password where it is when the operator says never mind', function (): void {
    $inviting = AStackThatInvites::answering()->holding(TheMembers::of(AMember::joined('anna')));
    $screen = theInvitationScreen($inviting);
    $screen->wouldTakeThePasswordOff('anna');
    $screen->neverMind();
    $screen->takeThePasswordOff();

    expect($inviting->asked())->toBe([])
        ->and($screen->member)->toBe('')
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('stacks.invitation.would_take_it_off', ['name' => 'anna']));
});

it('an act the stack could not be reached for is an obstacle beside the rest, that can be asked again', function (): void {
    $screen = theInvitationScreen(AStackThatInvites::answering(WhatBecameOfTheInvitation::met(Obstacle::StackDidNotAnswer)));
    $screen->name = 'anna';
    $screen->offer();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->howItIsGoing()->went->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($drawn->said())->toContain(__(Obstacle::StackDidNotAnswer->said()))
        ->and($drawn->said())->toContain(__('stacks.invitation.who_is_in'))
        ->and($drawn->offers())->toContain(__('health.ask_again'));

    $screen->again();

    expect($screen->howItIsGoing()->went->cameBack())->toBeTrue();
});

it('a household that could not be read is an obstacle, not nobody in', function (): void {
    $inviting = AStackThatInvites::answering()->readingAs(Obstacle::StackDidNotAnswer);
    $screen = theInvitationScreen($inviting);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->answer()->went->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($drawn->said())->not->toContain(__('stacks.invitation.nobody_in'))
        ->and($drawn->offers())->toContain(__('health.ask_again'))
        ->and($drawn->offers())->not->toContain(__('stacks.invitation.what_would_it_grant'));

    $screen->again();
    $screen->answer();

    expect($inviting->readings())->toBe(2);
});

it('a credential the stack refused signs this device out and lets the session go', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theInvitationScreen(AStackThatInvites::met(Obstacle::CredentialWasRefused), keychain: $keychain);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackSomebodyIsAskedIn()->id()))->toBeFalse();

    $acting = AKeychainInMemory::working();
    $refused = theInvitationScreen(AStackThatInvites::answering(WhatBecameOfTheInvitation::met(Obstacle::CredentialWasRefused)), keychain: $acting);
    $refused->name = 'anna';
    $refused->offer();

    expect($refused->howItIsGoing()->went->isSignedIn)->toBeFalse()
        ->and($acting->isHolding(theStackSomebodyIsAskedIn()->id()))->toBeFalse();
});

it('a session that has ended asks the stack nothing', function (): void {
    $inviting = AStackThatInvites::answering();
    $screen = theInvitationScreen($inviting, signedIn: false);
    $screen->name = 'anna';
    $screen->offer();

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($inviting->readings())->toBe(0)
        ->and($screen->howItIsGoing()->went->isSignedIn)->toBeFalse()
        ->and($screen->howItIsGoing()->askedFor)->toBe('anna')
        ->and($inviting->asked())->toBe([]);
});

it('refuses a route parameter that is not text', function (): void {
    $screen = theInvitationScreen(AStackThatInvites::answering());
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('the way here and the way back are routes', function (): void {
    $screen = theInvitationScreen(AStackThatInvites::answering());

    expect(NativeRouter::resolve($screen->goes()->health()))->not->toBeNull()
        ->and(NativeRouter::resolve($screen->goes()->whoGetsIn()->invite()))->not->toBeNull();
});

it('renders its own view', function (): void {
    expect(theInvitationScreen(AStackThatInvites::answering())->render()->name())->toBe('operator::asking-somebody-in');
});
