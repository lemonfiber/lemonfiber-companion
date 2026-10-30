<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Lemonfiber\Native\Wakes;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnAlert;
use Modules\Kernel\Api\AnAlertsName;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheAlerts;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\WhatStoppedMoving;
use Modules\Kernel\Api\WhatTheCoreDecided;
use Modules\Kernel\Api\WhatWasHeard;
use Modules\Kernel\Api\WhichWayItWent;
use Modules\Kernel\Api\Whose;
use Modules\Wayfinding\Api\HearingWhileAway;
use Modules\Wayfinding\Api\HowTheWakeWent;
use Tests\Support\AWakeOnThePhone;
use Tests\Support\Fakes\AStackThatSpeaksUp;

pest()->group('holds:bootstrap/Composition');

// The platform wakes the app while it is away, with nothing to say, and the
// app hears each stack the operator is signed in to over its own connection:
// what the stack raised meanwhile is told, its summary is kept, and the wake
// lets go within a bounded number of reads, well inside the time iOS allows one.

/** The moment a wake comes, in every case below. */
const WOKEN_AT = 1_790_000_000;

/** Named for this file: the root suites share one namespace (`G10`). */
function aStackTheWakeHears(string $called, string $seed): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST))),
        StackName::of($called),
        Address::of('https://192.168.1.90'),
        Fingerprint::of(str_repeat($seed, Fingerprint::CHARACTERS)),
    );
}

function theBoathouse(): Stack
{
    return aStackTheWakeHears('The boathouse', 'b');
}

function theGarage(): Stack
{
    return aStackTheWakeHears('The garage', 'd');
}

/** The first summary a stack says, which comes after everything it raised meanwhile. */
function itIsHealthy(): WhatWasHeard
{
    return WhatWasHeard::said(TheHealthSummary::of(HowItStands::Healthy, 0, '', WhatStoppedMoving::nothing()));
}

/** The VPN going down, raised while the phone was away. */
function theVpnWentDown(): TheAlerts
{
    return TheAlerts::of(AnAlert::raised(WhatTheCoreDecided::toSay('vpn.down'), WhichWayItWent::Onset, AnAlertsName::of('vpn.down#1')));
}

it('tells each alert a stack raised meanwhile, keeps its summary, and lets go once it has said it', function (): void {
    $phone = AWakeOnThePhone::holding(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::nothing(), itIsHealthy()->alerting(theVpnWentDown())), Instant::atEpochSeconds(WOKEN_AT), theBoathouse())
        ->signedInTo(theBoathouse(), Whose::theOperator());

    expect($phone->hearing()->hear())->toBe(HowTheWakeWent::HeardEveryStack)
        ->and($phone->notifier->told())->toBe([sprintf('plain vpn.down %s onset as %s opening lemonfiber://stacks/%s', theBoathouse()->name()->shown(), AnAlertsName::of('vpn.down#1')->filedFor(theBoathouse()->id()), theBoathouse()->id()->stored())])
        ->and($phone->hearing->asked())->toBe(2)
        ->and($phone->standings->keepsAnythingOf(theBoathouse()->id()))->toBeTrue()
        ->and($phone->hearing->lettingsGo())->toBe(1);
});

it('reaches no stack while notifications are off', function (): void {
    $phone = AWakeOnThePhone::withNotificationsOff(AStackThatSpeaksUp::holdingOpen(itIsHealthy()->alerting(theVpnWentDown())), Instant::atEpochSeconds(WOKEN_AT), theBoathouse())
        ->signedInTo(theBoathouse(), Whose::theOperator());

    expect($phone->hearing()->hear())->toBe(HowTheWakeWent::NothingToTell)
        ->and($phone->hearing->asked())->toBe(0)
        ->and($phone->notifier->told())->toBe([])
        ->and($phone->hearing->lettingsGo())->toBe(0);
});

it('reaches no stack while the app is in front of somebody, who hears them already', function (): void {
    $phone = AWakeOnThePhone::holding(AStackThatSpeaksUp::holdingOpen(itIsHealthy()->alerting(theVpnWentDown())), Instant::atEpochSeconds(WOKEN_AT), theBoathouse())
        ->signedInTo(theBoathouse(), Whose::theOperator())
        ->withTheAppOpen();

    expect($phone->hearing()->hear())->toBe(HowTheWakeWent::TheAppIsOpen)
        ->and($phone->hearing->asked())->toBe(0)
        ->and($phone->notifier->told())->toBe([])
        ->and($phone->hearing->lettingsGo())->toBe(0);
});

it('listens only to stacks the phone holds an operator\'s session for', function (): void {
    $phone = AWakeOnThePhone::holding(AStackThatSpeaksUp::holdingOpen(itIsHealthy()), Instant::atEpochSeconds(WOKEN_AT), theBoathouse(), theGarage())
        ->signedInTo(theGarage(), Whose::member('robin'));

    expect($phone->hearing()->hear())->toBe(HowTheWakeWent::HeardEveryStack)
        ->and($phone->hearing->asked())->toBe(0)
        ->and($phone->hearing->lettingsGo())->toBe(1);
});

it('asks every stack in turn, and stops asking each once it has said its first summary', function (): void {
    $phone = AWakeOnThePhone::holding(AStackThatSpeaksUp::holdingOpen(itIsHealthy(), WhatWasHeard::aSignOfLife(), itIsHealthy()), Instant::atEpochSeconds(WOKEN_AT), theBoathouse(), theGarage())
        ->signedInTo(theBoathouse(), Whose::theOperator())
        ->signedInTo(theGarage(), Whose::theOperator());

    expect($phone->hearing()->hear())->toBe(HowTheWakeWent::HeardEveryStack)
        ->and($phone->hearing->asked())->toBe(3)
        ->and($phone->standings->keepsAnythingOf(theBoathouse()->id()))->toBeTrue()
        ->and($phone->standings->keepsAnythingOf(theGarage()->id()))->toBeTrue();
});

it('stops asking a stack whose stream ended', function (): void {
    $phone = AWakeOnThePhone::holding(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::closed(), itIsHealthy()), Instant::atEpochSeconds(WOKEN_AT), theBoathouse())
        ->signedInTo(theBoathouse(), Whose::theOperator());

    expect($phone->hearing()->hear())->toBe(HowTheWakeWent::HeardEveryStack)
        ->and($phone->hearing->asked())->toBe(1);
});

it('stops asking a stack that could not be heard, and lets go of a session it refused', function (): void {
    $phone = AWakeOnThePhone::holding(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::met(Obstacle::of(KindOfObstacle::CredentialWasRefused))), Instant::atEpochSeconds(WOKEN_AT), theBoathouse())
        ->signedInTo(theBoathouse(), Whose::theOperator());

    expect($phone->hearing()->hear())->toBe(HowTheWakeWent::HeardEveryStack)
        ->and($phone->hearing->asked())->toBe(1)
        ->and($phone->keychain->isHolding(theBoathouse()->id()))->toBeFalse();
});

it('keeps the session of a stack that did not answer', function (): void {
    $phone = AWakeOnThePhone::holding(AStackThatSpeaksUp::holdingOpen(WhatWasHeard::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))), Instant::atEpochSeconds(WOKEN_AT), theBoathouse())
        ->signedInTo(theBoathouse(), Whose::theOperator());

    $phone->hearing()->hear();

    expect($phone->keychain->isHolding(theBoathouse()->id()))->toBeTrue();
});

it('gives up once it has made as many reads as a wake may, each waiting as long as a wake allows, and lets go', function (): void {
    $phone = AWakeOnThePhone::holding(AStackThatSpeaksUp::holdingOpen(), Instant::atEpochSeconds(WOKEN_AT), theBoathouse())
        ->signedInTo(theBoathouse(), Whose::theOperator());

    expect($phone->hearing()->hear())->toBe(HowTheWakeWent::GaveUpOnSome)
        ->and($phone->hearing->asked())->toBe(HearingWhileAway::AT_MOST_READS)
        ->and($phone->hearing->eachReadWaits())->toBe(HearingWhileAway::EACH_READ_WAITS_MS)
        ->and($phone->hearing->lettingsGo())->toBe(1);
});

it('shares those reads among the stacks it is still waiting on, and asks none beyond them', function (): void {
    $stacks = array_map(static fn(int $seed): Stack => aStackTheWakeHears(sprintf('Stack %d', $seed), dechex($seed)), range(1, 3));
    $phone = AWakeOnThePhone::holding(AStackThatSpeaksUp::holdingOpen(), Instant::atEpochSeconds(WOKEN_AT), ...$stacks);

    foreach ($stacks as $stack) {
        $phone->signedInTo($stack, Whose::theOperator());
    }

    expect($phone->hearing()->hear())->toBe(HowTheWakeWent::GaveUpOnSome)
        ->and($phone->hearing->asked())->toBe(HearingWhileAway::AT_MOST_READS);
});

it('listens for at most ten seconds of reads', function (): void {
    expect(HearingWhileAway::AT_MOST_READS * HearingWhileAway::EACH_READ_WAITS_MS)->toBe(10_000);
});

/** Run the command a wake runs, and answer its exit code. */
function theCommandAWakeRuns(): int
{
    return Artisan::call(Wakes::COMMAND);
}

it('is what the command a wake runs does', function (): void {
    $phone = AWakeOnThePhone::holding(AStackThatSpeaksUp::holdingOpen(itIsHealthy()), Instant::atEpochSeconds(WOKEN_AT), theBoathouse())
        ->signedInTo(theBoathouse(), Whose::theOperator());

    app()->instance(HearingWhileAway::class, $phone->hearing());

    expect(theCommandAWakeRuns())->toBe(0)
        ->and($phone->hearing->asked())->toBe(1)
        ->and($phone->hearing->lettingsGo())->toBe(1);
});

/** What the composition root makes for a wake. */
function whatTheContainerMakesForAWake(): HearingWhileAway
{
    return app()->make(HearingWhileAway::class);
}

it('is made by the composition root, afresh each time', function (): void {
    expect(whatTheContainerMakesForAWake())->toBeInstanceOf(HearingWhileAway::class)
        ->and(whatTheContainerMakesForAWake())->not->toBe(whatTheContainerMakesForAWake());
});
