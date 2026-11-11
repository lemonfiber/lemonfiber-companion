<?php

declare(strict_types=1);

use Modules\Kernel\Api\ACheckAfterAStart;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowItIsHosted;
use Modules\Kernel\Api\HowLongItHasFailed;
use Modules\Kernel\Api\HowTheCheckAfterAStartEnded;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheCheckKept;
use Modules\Kernel\Api\ThePortAfterAStart;
use Modules\Kernel\Api\TheServicesNamed;
use Modules\Kernel\Api\TheTunnelAfterAStart;
use Modules\Kernel\Api\Unattended;
use Modules\Kernel\Api\WhatKeepsItRunning;
use Modules\Kernel\Api\WhatRunsUnattended;
use Modules\Kernel\Api\WhatTheLastStartCameTo;
use Modules\Kernel\Api\WhenItSays;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhileNobodyWasLooking;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\Catalogue;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatHosts;
use Tests\Support\Fakes\AStackThatSaysHowItCameBack;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// What the machine came back to after it last started, and what keeps it running.

/** When the screen is drawn, in these tests. */
const WHEN_THE_RESTARTS_ARE_READ = 1_790_000_000;

/** The machine this screen is about. */
function theStackThatRestarted(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** A launch agent keeping one thing, which is installed against nothing. */
function aMachineKeepingOneThingThatDidNotComeBack(): WhatRunsUnattended
{
    return WhatRunsUnattended::keptBy(
        WhatKeepsItRunning::Launchd,
        Unattended::orphaned('Seeding what you share', 'lemonfiber seed', 'Torrents keep seeding', '/usr/local/bin/lemonfiber'),
        Unattended::called('Watching the library', 'lemonfiber watch --all', 'New files are noticed', HowItIsHosted::Hosted),
    );
}

/** A check after a start that ended as said, an hour before the screen is drawn. */
function aCheckThatEnded(HowTheCheckAfterAStartEnded $ended, ?TheTunnelAfterAStart $tunnel = null, ?ThePortAfterAStart $port = null, string ...$notBack): TheCheckKept
{
    return TheCheckKept::this(ACheckAfterAStart::ran(Instant::atEpochSeconds(WHEN_THE_RESTARTS_ARE_READ - 3_600), $ended, TheServicesNamed::of(...$notBack), $tunnel ?? TheTunnelAfterAStart::held(), $port ?? ThePortAfterAStart::notForwarded(), ''));
}

/** Nothing kept of any start. */
function noRecordOfARestart(): WhatTheLastStartCameTo
{
    return WhatTheLastStartCameTo::said(WhenItSays::unsaid(), TheCheckKept::none(), TheCheckKept::none(), '', HowLongItHasFailed::notFailing());
}

/** The screen, with both readings as the fakes say, signed in unless said. */
function theRestartScreen(AStackThatSaysHowItCameBack $asking, ?AStackThatHosts $hosting = null, bool $signedIn = true): WhileNobodyWasLooking
{
    $stack = theStackThatRestarted();
    $keychain = AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhileNobodyWasLooking(
        $asking,
        $hosting ?? AStackThatHosts::with(aMachineKeepingOneThingThatDidNotComeBack()),
        $keychain,
        FrozenClock::at(Instant::atEpochSeconds(WHEN_THE_RESTARTS_ARE_READ)),
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/**
 * Every line two frames draw: the last start on the first, what keeps it running on the second.
 *
 * @return list<string>
 */
function whatTwoFramesSay(WhileNobodyWasLooking $screen): array
{
    WhatTheDeviceWouldDraw::by($screen);

    return WhatTheDeviceWouldDraw::by($screen)->said();
}

it('is served at its own path and is in the menu', function (): void {
    expect(data_get(NativeRouter::resolve(str_replace('{stack}', theStackThatRestarted()->id()->stored(), AStacksScreen::WhileNobodyWasLooking->value)), 'class'))->toBe(WhileNobodyWasLooking::class);
});

it('says the check after the latest restart ran, what it came to, what did not come back, the tunnel and the port', function (): void {
    $started = WhenItSays::at(Instant::atEpochSeconds(WHEN_THE_RESTARTS_ARE_READ - 7_200));
    $check = aCheckThatEnded(HowTheCheckAfterAStartEnded::Failed, TheTunnelAfterAStart::notEstablished('No handshake'), ThePortAfterAStart::moved(51413, 51999, everyClientTakesIt: false), 'sonarr', 'radarr');
    $said = whatTwoFramesSay(theRestartScreen(AStackThatSaysHowItCameBack::with(WhatTheLastStartCameTo::said($started, $check, $check, '', HowLongItHasFailed::after(2, WhenItSays::at(Instant::atEpochSeconds(WHEN_THE_RESTARTS_ARE_READ - 86_400)))))));

    expect($said)->toContain(Catalogue::words()->for('restarts.started', ['ago' => trans_choice('health.ago.hours', 2)]))
        ->and($said)->toContain(Catalogue::words()->for('restarts.checked', ['ago' => trans_choice('health.ago.hours', 1)]))
        ->and($said)->toContain(Catalogue::words()->for('restarts.ended.failed'))
        ->and($said)->toContain(Catalogue::words()->for('restarts.not_back', ['services' => 'sonarr, radarr']))
        ->and($said)->toContain(Catalogue::words()->for('restarts.tunnel.not-established', ['said' => 'No handshake']))
        ->and($said)->toContain(Catalogue::words()->for('restarts.port.moved', ['was' => '51413', 'now' => '51999']))
        ->and($said)->toContain(Catalogue::words()->for('restarts.port.not_every_client'))
        ->and($said)->toContain(trans_choice('restarts.failing', 2, ['since' => trans_choice('health.ago.days', 1)]));
});

it('says a check that has not run since the last restart has not, and never draws an earlier one as passing for it', function (): void {
    $started = WhenItSays::at(Instant::atEpochSeconds(WHEN_THE_RESTARTS_ARE_READ - 600));
    $said = whatTwoFramesSay(theRestartScreen(AStackThatSaysHowItCameBack::with(WhatTheLastStartCameTo::said($started, TheCheckKept::none(), aCheckThatEnded(HowTheCheckAfterAStartEnded::Passed), 'Autostart is off', HowLongItHasFailed::notFailing()))));

    expect($said)->toContain(Catalogue::words()->for('restarts.not_run', ['ago' => trans_choice('health.ago.minutes', 10)]))
        ->and($said)->toContain(Catalogue::words()->for('restarts.declined', ['reason' => 'Autostart is off']))
        ->and($said)->toContain(Catalogue::words()->for('restarts.earlier', ['ago' => trans_choice('health.ago.hours', 1)]))
        ->and($said)->not->toContain(Catalogue::words()->for('restarts.checked', ['ago' => trans_choice('health.ago.hours', 1)]));
});

it('says the machine will not say when it started, and draws the newest check only as an earlier one', function (): void {
    $said = whatTwoFramesSay(theRestartScreen(AStackThatSaysHowItCameBack::with(WhatTheLastStartCameTo::said(WhenItSays::unsaid(), TheCheckKept::none(), aCheckThatEnded(HowTheCheckAfterAStartEnded::Passed, TheTunnelAfterAStart::notChecked(), ThePortAfterAStart::same(51413, everyClientTakesIt: true)), '', HowLongItHasFailed::after(1, WhenItSays::unsaid())))));

    expect($said)->toContain(Catalogue::words()->for('restarts.start_unknown'))
        ->and($said)->toContain(Catalogue::words()->for('restarts.earlier', ['ago' => trans_choice('health.ago.hours', 1)]))
        ->and($said)->toContain(Catalogue::words()->for('restarts.tunnel.not-checked'))
        ->and($said)->toContain(Catalogue::words()->for('restarts.port.same', ['was' => '51413', 'now' => '51413']))
        ->and($said)->not->toContain(Catalogue::words()->for('restarts.port.not_every_client'))
        ->and($said)->toContain(trans_choice('restarts.failing_undated', 1));
});

it('says the stack keeps no record of a restart yet', function (): void {
    expect(whatTwoFramesSay(theRestartScreen(AStackThatSaysHowItCameBack::with(noRecordOfARestart()))))
        ->toContain(Catalogue::words()->for('restarts.nothing_kept'));
});

it('reads the last start on one frame and what keeps it running on the next, asking for it at once', function (): void {
    $asking = AStackThatSaysHowItCameBack::with(noRecordOfARestart());
    $hosting = AStackThatHosts::with(aMachineKeepingOneThingThatDidNotComeBack());
    $screen = theRestartScreen($asking, $hosting);
    $first = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($first)->toContain(Catalogue::words()->for('restarts.reading_keeps_running'))
        ->and($screen->running)->toBeNull();

    $second = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($second)->toContain(Catalogue::words()->for(WhatKeepsItRunning::Launchd->saidOnTheScreen()))
        ->and($second)->toContain(trans_choice('stacks.did_not_come_back', 1))
        ->and($second)->toContain(Catalogue::words()->for('restarts.keeps_running_open'))
        ->and($asking->askings())->toBe(1);
});

it('says what keeps it running could not be read, and keeps the last start it read', function (): void {
    $said = whatTwoFramesSay(theRestartScreen(AStackThatSaysHowItCameBack::with(noRecordOfARestart()), AStackThatHosts::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))));

    expect($said)->toContain(Catalogue::words()->for('restarts.keeps_running_unread'))
        ->and($said)->toContain(Catalogue::words()->for('restarts.nothing_kept'));
});

it('says what stood in the way where the stack could not be asked, and the sign-in where the session ended', function (): void {
    $met = theRestartScreen(AStackThatSaysHowItCameBack::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));
    $signedOut = theRestartScreen(AStackThatSaysHowItCameBack::with(noRecordOfARestart()), signedIn: false);

    expect($met->answer()->went->cameBack())->toBeFalse()
        ->and($met->answer()->went->met)->toBe(KindOfObstacle::StackDidNotAnswer->said())
        ->and($signedOut->answer()->went->isSignedIn)->toBeFalse();
});

it('asks both again when the operator asks again', function (): void {
    $asking = AStackThatSaysHowItCameBack::with(noRecordOfARestart());
    $screen = theRestartScreen($asking);
    whatTwoFramesSay($screen);

    $screen->askAgain();
    whatTwoFramesSay($screen);

    expect($asking->askings())->toBe(2);
});
