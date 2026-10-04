<?php

declare(strict_types=1);

use Illuminate\Contracts\Translation\Translator;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AMember;
use Modules\Kernel\Api\ARemoval;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowFarTheRemovalReached;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheMembers;
use Modules\Kernel\Api\WhatBecameOfTheRemoval;
use Modules\Kernel\Api\WhatTheRemovalFound;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\AskingSomebodyIn;
use Modules\Operator\Internal\Screens\TakingSomebodyOut;
use Modules\Operator\Internal\ViewModels\ARemovalAsShown;
use Modules\Operator\Internal\ViewModels\TheRemovalTurnedOutToBe;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\ACodeOfWhatItWasGiven;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AShareSheetThatWasOffered;
use Tests\Support\Fakes\AStackThatInvites;
use Tests\Support\Fakes\AStackThatTakesThemOut;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\NoticingWhatIsNew;
use Tests\Support\WhatTheDeviceWouldDraw;

// Taking somebody out of the household: what it would cost before anything is
// agreed to, the yes, and how far it reached, in the stack's three words.
//
// Here rather than in the operator module's own tests because a screen
// renders, and rendering needs the application.

/** The machine somebody is taken out of. */
function theStackSomebodyLeaves(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The screen, opened on a member, with a keychain holding whatever a case says. */
function theRemovalScreen(
    AStackThatTakesThemOut $removing,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
    string $member = 'anna',
): TakingSomebodyOut {
    $stack = theStackSomebodyLeaves();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new TakingSomebodyOut($removing, $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening(), NoticingWhatIsNew::fromNothing());
    $screen->setParams(['stack' => $stack->id()->stored(), 'service' => $member]);

    return $screen;
}

/** What taking Anna out would cost, with nobody taken out. */
function whatTakingAnnaOutCosts(int $requests = 3, string ...$findings): ARemoval
{
    return ARemoval::described(
        SomebodyInTheHousehold::called('Anna'),
        $requests,
        asksThroughTheRequestService: true,
        revoked: HowFarTheRemovalReached::Nothing,
        findings: WhatTheRemovalFound::of(...$findings),
    );
}

/** What taking Anna out did, reaching as far as a case says. */
function annaTakenOut(HowFarTheRemovalReached $revoked, int $requests = 3, string ...$findings): ARemoval
{
    return ARemoval::carriedOut(
        SomebodyInTheHousehold::called('Anna'),
        $requests,
        asksThroughTheRequestService: true,
        revoked: $revoked,
        findings: WhatTheRemovalFound::of(...$findings),
    );
}

/**
 * The work, and then what it came to.
 *
 * @return list<WhatBecameOfTheRemoval>
 */
function theRemovalWorkThenIts(ARemoval $removal): array
{
    return [WhatBecameOfTheRemoval::underway(Job::named('j-1')), WhatBecameOfTheRemoval::answered($removal)];
}

/** A screen that has read what taking Anna out would cost, with more answers lined up behind it. */
function annasCostReadOn(AStackThatTakesThemOut $removing): TakingSomebodyOut
{
    $screen = theRemovalScreen($removing);
    $screen->answer();
    $screen->whileItRuns();
    $screen->answer();

    return $screen;
}

/**
 * Every field of a state as drawn, in one comparable row.
 *
 * @return array<string, mixed>
 */
function everythingTheRemovalScreenHolds(TheRemovalTurnedOutToBe $shown): array
{
    return [
        'cameBack' => $shown->went->cameBack(),
        'isSignedIn' => $shown->went->isSignedIn,
        'met' => $shown->went->met,
        'name' => $shown->name,
        'namesNobody' => $shown->namesNobody,
        'wasAgreed' => $shown->wasAgreed,
        'isWorking' => $shown->isWorking,
        'hasEnded' => $shown->hasEnded,
        'refusal' => $shown->refusal,
        'removal' => $shown->removal instanceof ARemovalAsShown
            ? [$shown->removal->name, $shown->removal->carriedOut, $shown->removal->revokedSaid, $shown->removal->isDone, $shown->removal->requests, $shown->removal->asksSaid, $shown->removal->findings]
            : null,
    ];
}

/**
 * What a state with nothing in it holds, changed where a case says.
 *
 * @param  array<string, mixed> $changed
 * @return array<string, mixed>
 */
function nothingHeldOfTheRemoval(array $changed): array
{
    return [
        'cameBack' => true,
        'isSignedIn' => true,
        'met' => '',
        'name' => 'anna',
        'namesNobody' => false,
        'wasAgreed' => false,
        'isWorking' => false,
        'hasEnded' => false,
        'refusal' => '',
        'removal' => null,
        ...$changed,
    ];
}

/** One line of the catalogue, with a count, as the words it holds. */
function aCountedLineOfTheRemoval(string $key, int $count): string
{
    return trans_choice($key, $count);
}

it('asks what taking them out would cost as it opens, and says it is asking', function (): void {
    $removing = AStackThatTakesThemOut::answering(WhatBecameOfTheRemoval::underway(Job::named('j-1')));
    $screen = theRemovalScreen($removing);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($removing->asked())->toBe(['would:anna'])
        ->and($screen->answer()->isWorking)->toBeTrue()
        ->and($drawn->said())->toContain(__('stacks.removal.reading', ['name' => 'anna']))
        ->and($drawn->offers())->not->toContain(__('stacks.removal.take_them_out', ['name' => 'anna']));
});

it('draws what taking them out would cost, said to be only that, and offers the yes beneath it', function (): void {
    $removing = AStackThatTakesThemOut::answering(...theRemovalWorkThenIts(whatTakingAnnaOutCosts(3, 'The request service answered slowly')));
    $screen = annasCostReadOn($removing);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($removing->asked())->toBe(['would:anna', 'after:j-1'])
        ->and($drawn->said())->toContain(__('stacks.removal.not_yet', ['name' => 'Anna']))
        ->and($drawn->said())->toContain(__('stacks.removal.revoked.nothing', ['name' => 'Anna']))
        ->and($drawn->said())->toContain(aCountedLineOfTheRemoval('stacks.removal.requests_go', 3))
        ->and($drawn->said())->toContain(__('stacks.removal.asks'))
        ->and($drawn->said())->toContain('The request service answered slowly')
        ->and($drawn->offers())->toContain(__('stacks.removal.take_them_out', ['name' => 'Anna']))
        ->and($drawn->offers())->toContain(__('stacks.removal.back_to_who_is_in'))
        ->and($screen->answer()->removal?->isDone)->toBeFalse();
});

it('says it when they have no requests to lose and no account on the request service', function (): void {
    $described = ARemoval::described(SomebodyInTheHousehold::called('Anna'), 0, asksThroughTheRequestService: false, revoked: HowFarTheRemovalReached::Nothing, findings: WhatTheRemovalFound::of());
    $drawn = WhatTheDeviceWouldDraw::by(annasCostReadOn(AStackThatTakesThemOut::answering(...theRemovalWorkThenIts($described))));

    expect($drawn->said())->toContain(aCountedLineOfTheRemoval('stacks.removal.requests_go', 0))
        ->and($drawn->said())->toContain(__('stacks.removal.does_not_ask'))
        ->and($drawn->said())->toContain(__('stacks.removal.found_nothing'));
});

it('takes them out by the name the stack answered under, and draws everywhere as done', function (): void {
    $removing = AStackThatTakesThemOut::answering(
        ...theRemovalWorkThenIts(whatTakingAnnaOutCosts(1)),
        ...theRemovalWorkThenIts(annaTakenOut(HowFarTheRemovalReached::Everywhere, 1)),
    );
    $screen = annasCostReadOn($removing);
    $screen->agree();

    expect($screen->answer()->isWorking)->toBeTrue()
        ->and($screen->answer()->wasAgreed)->toBeTrue()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.removal.removing', ['name' => 'anna']));

    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($removing->asked())->toBe(['would:anna', 'after:j-1', 'remove:Anna', 'after:j-1'])
        ->and($screen->answer()->removal?->isDone)->toBeTrue()
        ->and($drawn->said())->toContain(__('stacks.removal.revoked.everywhere', ['name' => 'Anna']))
        ->and($drawn->said())->toContain(aCountedLineOfTheRemoval('stacks.removal.requests_went', 1))
        ->and($drawn->said())->not->toContain(__('stacks.removal.not_yet', ['name' => 'Anna']))
        ->and($drawn->offers())->not->toContain(__('stacks.removal.take_them_out', ['name' => 'Anna']))
        ->and($drawn->offers())->not->toContain(__('stacks.removal.read_again', ['name' => 'Anna']));
});

it('never draws the media server alone as done, shows what the stack found after, and offers reading the cost again', function (): void {
    $removing = AStackThatTakesThemOut::answering(...[
        ...theRemovalWorkThenIts(whatTakingAnnaOutCosts()),
        ...theRemovalWorkThenIts(annaTakenOut(HowFarTheRemovalReached::MediaServerOnly, 3, 'The request service did not answer, so her account there is still held')),
        WhatBecameOfTheRemoval::underway(Job::named('j-2')),
    ]);
    $screen = annasCostReadOn($removing);
    $screen->agree();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->answer()->removal?->isDone)->toBeFalse()
        ->and($drawn->said())->toContain(__('stacks.removal.revoked.media-server-only', ['name' => 'Anna']))
        ->and($drawn->said())->not->toContain(__('stacks.removal.revoked.everywhere', ['name' => 'Anna']))
        ->and($drawn->said())->toContain('The request service did not answer, so her account there is still held')
        ->and($drawn->offers())->toContain(__('stacks.removal.read_again', ['name' => 'Anna']))
        ->and($drawn->offers())->not->toContain(__('stacks.removal.take_them_out', ['name' => 'Anna']));

    $screen->again();
    $screen->answer();

    expect($removing->asked())->toBe(['would:anna', 'after:j-1', 'remove:Anna', 'after:j-1', 'would:anna'])
        ->and($screen->agreed)->toBeFalse();
});

it('draws a refusal with the stack\'s reason and the name, and offers no way to send it again', function (): void {
    $removing = AStackThatTakesThemOut::answering(WhatBecameOfTheRemoval::refused('Nobody is called anna here'));
    $screen = theRemovalScreen($removing);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__('stacks.removal.refused', ['name' => 'anna']))
        ->and($drawn->said())->toContain('Nobody is called anna here')
        ->and($drawn->offers())->toContain(__('stacks.removal.back_to_who_is_in'))
        ->and($drawn->offers())->not->toContain(__('health.ask_again'))
        ->and($drawn->offers())->not->toContain(__('stacks.removal.take_them_out', ['name' => 'anna']))
        ->and($screen->following)->toBeNull();
});

it('tells work the stack has no outcome for apart before the yes and after it', function (): void {
    $before = theRemovalScreen(AStackThatTakesThemOut::answering(WhatBecameOfTheRemoval::underway(Job::named('j-1')), WhatBecameOfTheRemoval::ended()));
    $before->answer();
    $before->whileItRuns();

    $removing = AStackThatTakesThemOut::answering(...theRemovalWorkThenIts(whatTakingAnnaOutCosts()));
    $after = annasCostReadOn($removing);
    $after->agree();
    $after->whileItRuns();

    expect(WhatTheDeviceWouldDraw::by($before)->said())->toContain(__('stacks.removal.no_outcome', ['name' => 'anna']))
        ->and(WhatTheDeviceWouldDraw::by($after)->said())->toContain(__('stacks.removal.no_outcome_after_yes', ['name' => 'anna']))
        ->and(WhatTheDeviceWouldDraw::by($after)->offers())->toContain(__('stacks.removal.read_again', ['name' => 'anna']))
        ->and($after->following)->toBeNull();
});

it('says whether they were taken out could not be read where the yes met something, and never sends it again on its own', function (): void {
    $removing = AStackThatTakesThemOut::answering(...[
        ...theRemovalWorkThenIts(whatTakingAnnaOutCosts()),
        WhatBecameOfTheRemoval::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)),
        WhatBecameOfTheRemoval::underway(Job::named('j-2')),
    ]);
    $screen = annasCostReadOn($removing);
    $screen->agree();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__('stacks.removal.unread_after_yes', ['name' => 'anna']))
        ->and($drawn->said())->toContain(__(KindOfObstacle::StackDidNotAnswer->said()))
        ->and($drawn->offers())->toContain(__('health.ask_again'));

    $screen->again();
    $screen->answer();

    expect($removing->asked())->toBe(['would:anna', 'after:j-1', 'remove:Anna', 'would:anna']);
});

it('asks after the same work again where following it met something, without drawing a yes it never sent', function (): void {
    $removing = AStackThatTakesThemOut::answering(
        WhatBecameOfTheRemoval::underway(Job::named('j-1')),
        WhatBecameOfTheRemoval::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)),
        WhatBecameOfTheRemoval::underway(Job::named('j-1')),
    );
    $screen = theRemovalScreen($removing);
    $screen->answer();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->not->toContain(__('stacks.removal.unread_after_yes', ['name' => 'anna']));

    $screen->again();
    $screen->answer();

    expect($removing->asked())->toBe(['would:anna', 'after:j-1', 'after:j-1']);
});

it('asks after nothing while nothing is running', function (): void {
    $removing = AStackThatTakesThemOut::answering(WhatBecameOfTheRemoval::refused('No'));
    $screen = theRemovalScreen($removing);
    $screen->answer();
    $screen->whileItRuns();

    expect($removing->asked())->toBe(['would:anna']);
});

it('sends no yes where there is no reading to agree to, or the answer was already carried out', function (): void {
    $unread = theRemovalScreen(AStackThatTakesThemOut::answering(WhatBecameOfTheRemoval::underway(Job::named('j-1'))));
    $unread->agree();

    $removing = AStackThatTakesThemOut::answering(...theRemovalWorkThenIts(annaTakenOut(HowFarTheRemovalReached::Everywhere)));
    $done = annasCostReadOn($removing);
    $done->described = annaTakenOut(HowFarTheRemovalReached::Everywhere);
    $done->agree();

    expect($unread->agreed)->toBeFalse()
        ->and($done->agreed)->toBeFalse()
        ->and($removing->asked())->toBe(['would:anna', 'after:j-1'])
        ->and($done->described)->not->toBeNull();
});

it('opened on nobody, asks the stack nothing and says so', function (): void {
    $removing = AStackThatTakesThemOut::answering();
    $screen = theRemovalScreen($removing, member: '  ');
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->answer()->namesNobody)->toBeTrue()
        ->and($drawn->said())->toContain(__('stacks.removal.names_nobody'))
        ->and($drawn->offers())->toContain(__('stacks.removal.back_to_who_is_in'))
        ->and($removing->asked())->toBe([]);

    $screen->setParams(['stack' => theStackSomebodyLeaves()->id()->stored(), 'service' => 7]);

    expect($screen->named())->toBe('');
});

it('a session that has ended asks the stack nothing', function (): void {
    $removing = AStackThatTakesThemOut::answering();
    $screen = theRemovalScreen($removing, signedIn: false);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($screen->answer()->name)->toBe('anna')
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('connection.session_has_ended'))
        ->and($removing->asked())->toBe([]);
});

it('a credential the stack refused signs this device out and lets the session go', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theRemovalScreen(AStackThatTakesThemOut::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)), keychain: $keychain);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackSomebodyLeaves()->id()))->toBeFalse();
});

it('holds each state and only its own', function (): void {
    $running = theRemovalScreen(AStackThatTakesThemOut::answering(WhatBecameOfTheRemoval::underway(Job::named('j-1'))));
    $refused = theRemovalScreen(AStackThatTakesThemOut::answering(WhatBecameOfTheRemoval::refused('Nobody is called anna here')));
    $ended = theRemovalScreen(AStackThatTakesThemOut::answering(WhatBecameOfTheRemoval::ended()));
    $met = theRemovalScreen(AStackThatTakesThemOut::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));
    $signedOut = theRemovalScreen(AStackThatTakesThemOut::answering(), signedIn: false);
    $nobody = theRemovalScreen(AStackThatTakesThemOut::answering(), member: ' ');
    $cost = annasCostReadOn(AStackThatTakesThemOut::answering(...theRemovalWorkThenIts(whatTakingAnnaOutCosts(2, 'Slow'))));
    $done = annasCostReadOn(AStackThatTakesThemOut::answering(...theRemovalWorkThenIts(annaTakenOut(HowFarTheRemovalReached::Everywhere, 2))));

    expect(everythingTheRemovalScreenHolds($running->answer()))->toBe(nothingHeldOfTheRemoval(['isWorking' => true]))
        ->and(everythingTheRemovalScreenHolds($refused->answer()))->toBe(nothingHeldOfTheRemoval(['refusal' => 'Nobody is called anna here']))
        ->and(everythingTheRemovalScreenHolds($ended->answer()))->toBe(nothingHeldOfTheRemoval(['hasEnded' => true]))
        ->and(everythingTheRemovalScreenHolds($met->answer()))->toBe(nothingHeldOfTheRemoval(['cameBack' => false, 'met' => KindOfObstacle::StackDidNotAnswer->said()]))
        ->and(everythingTheRemovalScreenHolds($signedOut->answer()))->toBe(nothingHeldOfTheRemoval(['cameBack' => false, 'isSignedIn' => false]))
        ->and(everythingTheRemovalScreenHolds($nobody->answer()))->toBe(nothingHeldOfTheRemoval(['name' => '', 'namesNobody' => true]))
        ->and(everythingTheRemovalScreenHolds($cost->answer()))->toBe(nothingHeldOfTheRemoval([
            'name' => 'Anna',
            'removal' => ['Anna', false, 'stacks.removal.revoked.nothing', false, 2, 'stacks.removal.asks', ['Slow']],
        ]))
        ->and(everythingTheRemovalScreenHolds($done->answer()))->toBe(nothingHeldOfTheRemoval([
            'name' => 'Anna',
            'wasAgreed' => true,
            'removal' => ['Anna', true, 'stacks.removal.revoked.everywhere', true, 2, 'stacks.removal.asks', []],
        ]));
});

it('never draws a reading nobody agreed to as done, whatever reach it names', function (): void {
    $everywhere = ARemoval::described(SomebodyInTheHousehold::called('Anna'), 0, asksThroughTheRequestService: true, revoked: HowFarTheRemovalReached::Everywhere, findings: WhatTheRemovalFound::of());
    $screen = annasCostReadOn(AStackThatTakesThemOut::answering(...theRemovalWorkThenIts($everywhere)));

    expect($screen->answer()->removal?->isDone)->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('stacks.removal.take_them_out', ['name' => 'Anna']));
});

it('sends the yes once, however often it is tapped while the stack works', function (): void {
    $removing = AStackThatTakesThemOut::answering(...[
        ...theRemovalWorkThenIts(whatTakingAnnaOutCosts()),
        WhatBecameOfTheRemoval::underway(Job::named('j-2')),
        WhatBecameOfTheRemoval::underway(Job::named('j-3')),
    ]);
    $screen = annasCostReadOn($removing);
    $screen->agree();
    $screen->agree();

    expect($removing->asked())->toBe(['would:anna', 'after:j-1', 'remove:Anna']);
});

it('lets go of the reading when the cost is asked again, so a refusal leaves nothing to agree to', function (): void {
    $removing = AStackThatTakesThemOut::answering(...[
        ...theRemovalWorkThenIts(whatTakingAnnaOutCosts()),
        WhatBecameOfTheRemoval::refused('Somebody else is removing them'),
        WhatBecameOfTheRemoval::underway(Job::named('j-2')),
    ]);
    $screen = annasCostReadOn($removing);
    $screen->again();
    $screen->answer();
    $screen->agree();

    expect($removing->asked())->toBe(['would:anna', 'after:j-1', 'would:anna'])
        ->and($screen->described)->toBeNull();
});

it('refuses a route parameter that is not text', function (): void {
    $screen = theRemovalScreen(AStackThatTakesThemOut::answering());
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('is offered for each member of the household, where who is in is read', function (): void {
    $keychain = AKeychainInMemory::working();
    $keychain->keep(theStackSomebodyLeaves()->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    $household = new AskingSomebodyIn(
        AStackThatInvites::answering()->holding(TheMembers::of(AMember::joined('anna'), AMember::stillInvited('bob'))),
        ACodeOfWhatItWasGiven::working(),
        AShareSheetThatWasOffered::working(),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding(theStackSomebodyLeaves())),
        app(Translator::class),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(),
        NoticingWhatIsNew::fromNothing(),
    );
    $household->setParams(['stack' => theStackSomebodyLeaves()->id()->stored()]);
    $offers = WhatTheDeviceWouldDraw::by($household)->offers();

    expect($offers)->toContain(__('stacks.removal.would_take_them_out', ['name' => 'anna']))
        ->and($offers)->toContain(__('stacks.removal.would_take_them_out', ['name' => 'bob']));
});

it('the way here and the way back are routes, with the name encoded', function (): void {
    $screen = theRemovalScreen(AStackThatTakesThemOut::answering());
    $stack = theStackSomebodyLeaves()->id();

    expect(NativeRouter::resolve($screen->goes()->whoGetsIn()->takingOut('anna')))->not->toBeNull()
        ->and($screen->goes()->whoGetsIn()->takingOut('a/b'))->toBe(AStacksScreen::TakeOut->forTheStacksMember($stack, SomebodyInTheHousehold::called('a/b')))
        ->and($screen->goes()->whoGetsIn()->takingOut('a/b'))->toBe(sprintf('/stacks/%s/household/a%%2Fb', $stack->stored()))
        ->and(NativeRouter::resolve($screen->goes()->whoGetsIn()->invite()))->not->toBeNull();
});

it('renders its own view', function (): void {
    expect(theRemovalScreen(AStackThatTakesThemOut::answering())->render()->name())->toBe('operator::taking-somebody-out');
});
