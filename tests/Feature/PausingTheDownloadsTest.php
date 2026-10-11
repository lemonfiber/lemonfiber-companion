<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ADownloadClientAsked;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowPausingIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatADownloadClientSaid;
use Modules\Kernel\Api\WhatPausingComesTo;
use Modules\Kernel\Api\WhatToDoWithTheDownloads;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\PausingTheDownloads;
use Modules\Operator\Internal\ViewModels\ADownloadClientAsShown;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatPausesDownloads;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

/**
 * One line of the catalogue, as text, for filling another.
 *
 * @param array<string, string> $with
 */
function aLineOfPausing(string $key, array $with = []): string
{
    $said = __($key, $with);

    return is_string($said) ? $said : $key;
}

/** The machine whose downloads are paused. */
function theStackWhoseDownloadsPause(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('c', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The screen, opened on one direction, with a keychain holding whatever a case says. */
function thePausingScreen(AStackThatPausesDownloads $pausing, ?AKeychainInMemory $keychain = null, bool $signedIn = true, mixed $asked = 'pause'): PausingTheDownloads
{
    $stack = theStackWhoseDownloadsPause();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new PausingTheDownloads($pausing, $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening());
    $screen->setParams(['stack' => $stack->id()->stored(), 'service' => $asked]);

    return $screen;
}

/** The rehearsal of a pause: one client fetching, one already stopped, one out of reach. */
function aRehearsedPause(WhatToDoWithTheDownloads $asked = WhatToDoWithTheDownloads::Pause, string $caution = ''): WhatPausingComesTo
{
    return WhatPausingComesTo::said(
        $asked,
        WhetherItWasRehearsed::Rehearsed,
        'pause-qbittorrent-fetching',
        $caution,
        ADownloadClientAsked::reached('qbittorrent', WhatADownloadClientSaid::Fetching, WhatADownloadClientSaid::Unsaid),
        ADownloadClientAsked::reached('sabnzbd', WhatADownloadClientSaid::Stopped, WhatADownloadClientSaid::Unsaid),
        ADownloadClientAsked::unreached('nzbget', WhatADownloadClientSaid::Unsaid, 'connection refused'),
    );
}

/** What carrying that pause out read back. */
function aPauseCarriedOut(): WhatPausingComesTo
{
    return WhatPausingComesTo::said(
        WhatToDoWithTheDownloads::Pause,
        WhetherItWasRehearsed::CarriedOut,
        'pause-qbittorrent-fetching',
        '',
        ADownloadClientAsked::reached('qbittorrent', WhatADownloadClientSaid::Fetching, WhatADownloadClientSaid::Stopped),
        ADownloadClientAsked::reached('sabnzbd', WhatADownloadClientSaid::Stopped, WhatADownloadClientSaid::Stopped),
        ADownloadClientAsked::unreached('nzbget', WhatADownloadClientSaid::Unsaid, ''),
    );
}

/** A stack that rehearses that pause, and says `$became` of the yes. */
function aStackRehearsingAPause(HowPausingIsGoing $became, ?WhatPausingComesTo $offer = null): AStackThatPausesDownloads
{
    return AStackThatPausesDownloads::rehearsing(HowPausingIsGoing::answered($offer ?? aRehearsedPause()), $became);
}

/**
 * Each client as drawn, one comparable row each.
 *
 * @param  list<ADownloadClientAsShown> $clients
 * @return list<list<bool|string>>
 */
function everyClientAsShown(array $clients): array
{
    return array_map(static fn(ADownloadClientAsShown $client): array => [$client->name, $client->wasSaid, $client->nowSaid, $client->reached, $client->why], $clients);
}

it('rehearses first, and draws what every client is doing and the one it could not reach, before the yes', function (): void {
    $pausing = aStackRehearsingAPause(HowPausingIsGoing::stillRunning());
    $screen = thePausingScreen($pausing);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect(everyClientAsShown($screen->answer()->clients))->toBe([
        ['qbittorrent', 'stacks.pausing.said.fetching', '', true, ''],
        ['sabnzbd', 'stacks.pausing.said.stopped', '', true, ''],
        ['nzbget', 'stacks.pausing.said.unsaid', '', false, 'connection refused'],
    ])
        ->and($pausing->rehearsed())->toBe([WhatToDoWithTheDownloads::Pause])
        ->and($pausing->agreed())->toBe([])
        ->and($drawn->said())->toContain(__('stacks.pausing.what_it_does.pause'), __('stacks.pausing.explained.pause'), 'qbittorrent', __('stacks.pausing.said.fetching'), __('stacks.pausing.said.stopped'), __('stacks.pausing.unreached'), 'connection refused')
        ->and($drawn->said())->not->toContain(__('stacks.pausing.done.pause'))
        ->and($drawn->offers())->toBe([__('stacks.pausing.agree.pause'), __('health.ask_again')]);
});

it('rehearses a resume as a resume, with the caution a spent cap carries', function (): void {
    $pausing = aStackRehearsingAPause(HowPausingIsGoing::stillRunning(), aRehearsedPause(WhatToDoWithTheDownloads::Resume, 'The monthly cap is spent, so they stop again'));
    $screen = thePausingScreen($pausing, asked: 'resume');
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($pausing->rehearsed())->toBe([WhatToDoWithTheDownloads::Resume])
        ->and($drawn->said())->toContain(__('stacks.pausing.what_it_does.resume'), 'The monthly cap is spent, so they stop again')
        ->and($drawn->offers())->toContain(__('stacks.pausing.agree.resume'));
});

it('offers nothing to agree to where the stack runs no download client', function (): void {
    $none = WhatPausingComesTo::said(WhatToDoWithTheDownloads::Pause, WhetherItWasRehearsed::Rehearsed, 'pause-nothing', '');
    $pausing = aStackRehearsingAPause(HowPausingIsGoing::stillRunning(), $none);
    $screen = thePausingScreen($pausing);
    $drawn = WhatTheDeviceWouldDraw::by($screen);
    $screen->agree();

    expect($drawn->said())->toContain(__('stacks.pausing.no_clients'))
        ->and($drawn->offers())->toBe([__('health.ask_again')])
        ->and($pausing->agreed())->toBe([]);
});

it('carries out exactly the rehearsal it showed, and says it is doing so on its cadence', function (): void {
    $pausing = aStackRehearsingAPause(HowPausingIsGoing::stillRunning());
    $screen = thePausingScreen($pausing);
    $screen->answer();
    $screen->agree();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect(array_map(static fn(WhatPausingComesTo $offer): string => $offer->agreement(), $pausing->agreed()))->toBe(['pause-qbittorrent-fetching'])
        ->and($screen->isWorking())->toBeTrue()
        ->and($drawn)->toContain(__('stacks.pausing.working.pause'))
        ->and($drawn)->not->toContain(__('stacks.pausing.what_it_does.pause'));

    $screen->whileItRuns();
    $screen->done();

    expect(array_map(static fn(Job $job): string => $job->shown(), $pausing->followed()))->toBe([AStackThatPausesDownloads::THE_REHEARSAL, AStackThatPausesDownloads::THE_JOB])
        ->and($pausing->agreed())->toHaveCount(1);
});

it('reports each client before and after, and names the one it could not reach', function (): void {
    $pausing = aStackRehearsingAPause(HowPausingIsGoing::answered(aPauseCarriedOut()));
    $screen = thePausingScreen($pausing);
    $screen->answer();
    $screen->agree();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect(everyClientAsShown($screen->done()->clients))->toBe([
        ['qbittorrent', 'stacks.pausing.said.fetching', 'stacks.pausing.said.stopped', true, ''],
        ['sabnzbd', 'stacks.pausing.said.stopped', 'stacks.pausing.said.stopped', true, ''],
        ['nzbget', 'stacks.pausing.said.unsaid', '', false, ''],
    ])
        ->and($screen->done()->wasRehearsed)->toBeFalse()
        ->and($drawn)->toContain(__('stacks.pausing.done.pause'))
        ->and($drawn)->toContain(aLineOfPausing('stacks.pausing.before_and_after', ['was' => aLineOfPausing('stacks.pausing.said.fetching'), 'now' => aLineOfPausing('stacks.pausing.said.stopped')]))
        ->and($drawn)->toContain(__('stacks.pausing.unreached'), __('stacks.pausing.unreached_no_reason'));
});

it('labels a report the stack says was rehearsed as a rehearsal, never as paused', function (): void {
    $pausing = aStackRehearsingAPause(HowPausingIsGoing::answered(aRehearsedPause()));
    $screen = thePausingScreen($pausing);
    $screen->answer();
    $screen->agree();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain(__('stacks.pausing.a_rehearsal'))
        ->and($drawn)->not->toContain(__('stacks.pausing.done.pause'));
});

it('says a yes the stack has no outcome for is not known, rather than failed', function (): void {
    $screen = thePausingScreen(aStackRehearsingAPause(HowPausingIsGoing::ended()));
    $screen->answer();
    $screen->agree();
    $screen->whileItRuns();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.pausing.no_outcome'), __('stacks.pausing.no_outcome_action'));
});

it('sends no yes before a rehearsal has been read, and never on its cadence', function (): void {
    $pausing = aStackRehearsingAPause(HowPausingIsGoing::stillRunning());
    $screen = thePausingScreen($pausing);
    $screen->agree();
    $screen->whileItRuns();

    expect($pausing->agreed())->toBe([]);
});

it('says the stack is still rehearsing, and reads the same rehearsal again on its cadence', function (): void {
    $pausing = AStackThatPausesDownloads::rehearsing(HowPausingIsGoing::stillRunning(), HowPausingIsGoing::stillRunning());
    $screen = thePausingScreen($pausing);

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.pausing.working_it_out'))
        ->and($screen->awaitsAnOutcome())->toBeTrue();

    $screen->whileItRuns();
    $screen->answer();

    expect($pausing->rehearsed())->toHaveCount(1)
        ->and($pausing->followed())->toHaveCount(2);
});

it('says a rehearsal the stack no longer has is gone, and rehearses afresh when asked again', function (): void {
    $pausing = AStackThatPausesDownloads::rehearsing(HowPausingIsGoing::ended(), HowPausingIsGoing::stillRunning());
    $screen = thePausingScreen($pausing);

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.pausing.offer_ended'));

    $screen->again();
    $screen->answer();

    expect($pausing->rehearsed())->toHaveCount(2);
});

it('offers nothing to agree to where the stack would not rehearse, and lets go of a refused session', function (): void {
    $keychain = AKeychainInMemory::working();
    $pausing = AStackThatPausesDownloads::met(Obstacle::of(KindOfObstacle::CredentialWasRefused));
    $screen = thePausingScreen($pausing, $keychain);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackWhoseDownloadsPause()->id()))->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->not->toContain(__('stacks.pausing.agree.pause'));
});

it('says what stood in the way of a yes the stack refused', function (): void {
    $pausing = AStackThatPausesDownloads::rehearsingButRefusing(aRehearsedPause(), Obstacle::of(KindOfObstacle::StackDidNotAnswer));
    $screen = thePausingScreen($pausing);
    $screen->answer();
    $screen->agree();

    expect($screen->done()->went->cameBack())->toBeFalse()
        ->and($pausing->agreed())->toHaveCount(1);
});

it('asks for a session rather than a rehearsal where this device holds none', function (): void {
    $pausing = aStackRehearsingAPause(HowPausingIsGoing::stillRunning());
    $screen = thePausingScreen($pausing, signedIn: false);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($pausing->rehearsed())->toBe([])
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('connection.sign_in'));
});

it('names nothing where it was opened on neither pausing nor resuming, and asks the stack nothing', function (mixed $asked): void {
    $pausing = aStackRehearsingAPause(HowPausingIsGoing::stillRunning());
    $screen = thePausingScreen($pausing, asked: $asked);

    expect($screen->answer()->namesWhat)->toBeFalse()
        ->and($pausing->rehearsed())->toBe([])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.pausing.names_nothing'));
})->with([
    'a word it does not know' => ['throttle'],
    'not text' => [7],
]);

it('asks after the same handle when asked again after a yes the stack took on', function (): void {
    $pausing = aStackRehearsingAPause(HowPausingIsGoing::stillRunning());
    $screen = thePausingScreen($pausing);
    $screen->answer();
    $screen->agree();
    $screen->again();
    $screen->done();
    $followed = array_map(static fn(Job $job): string => $job->shown(), $pausing->followed());

    expect($pausing->agreed())->toHaveCount(1)
        ->and(end($followed))->toBe(AStackThatPausesDownloads::THE_JOB);
});

it('asks for a session where it is gone by the time the yes is sent, or asked after', function (bool $afterTheYes): void {
    $keychain = AKeychainInMemory::working();
    $screen = thePausingScreen(aStackRehearsingAPause(HowPausingIsGoing::stillRunning()), $keychain);
    $screen->answer();

    if ($afterTheYes) {
        $screen->agree();
    }

    $keychain->forget(theStackWhoseDownloadsPause()->id());

    if (! $afterTheYes) {
        $screen->agree();
    }

    $screen->whileItRuns();

    expect($screen->done()->went->isSignedIn)->toBeFalse()
        ->and($screen->done()->clients)->toBe([]);
})->with(['before the yes' => [false], 'after it' => [true]]);

it('has nothing to report for a yes nobody gave', function (): void {
    $pausing = aStackRehearsingAPause(HowPausingIsGoing::stillRunning());

    expect(thePausingScreen($pausing)->done()->hasEnded)->toBeTrue()
        ->and($pausing->followed())->toBe([]);
});

it('renders its own view, and is reached from Services each way', function (): void {
    $screen = thePausingScreen(aStackRehearsingAPause(HowPausingIsGoing::stillRunning()));

    expect($screen->render()->name())->toBe('operator::pausing-the-downloads')
        ->and(NativeRouter::resolve($screen->goes()->downloads(WhatToDoWithTheDownloads::Pause)))->not->toBeNull()
        ->and(NativeRouter::resolve($screen->goes()->downloads(WhatToDoWithTheDownloads::Resume)))->not->toBeNull();
});
