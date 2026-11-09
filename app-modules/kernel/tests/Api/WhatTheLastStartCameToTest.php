<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ACheckAfterAStart;
use Modules\Kernel\Api\ARestartSaysTooLittle;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\HowLongItHasFailed;
use Modules\Kernel\Api\HowTheCheckAfterAStartEnded;
use Modules\Kernel\Api\HowTheTunnelCameBack;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\TheCheckKept;
use Modules\Kernel\Api\ThePortAfterAStart;
use Modules\Kernel\Api\TheServicesNamed;
use Modules\Kernel\Api\TheTunnelAfterAStart;
use Modules\Kernel\Api\WhatTheLastStartCameTo;
use Modules\Kernel\Api\WhenItSays;

use function sprintf;

/** A check that passed, at this moment. */
function aCheckThatPassedAt(int $seconds): ACheckAfterAStart
{
    return ACheckAfterAStart::ran(Instant::atEpochSeconds($seconds), HowTheCheckAfterAStartEnded::Passed, TheServicesNamed::of(), TheTunnelAfterAStart::held(), ThePortAfterAStart::notForwarded(), '');
}

/** What a port came to, as one word. */
function whatThePortCameTo(ThePortAfterAStart $port): string
{
    return $port->either(
        same: static fn(int $is): Code => Code::of(sprintf('same %d', $is)),
        moved: static fn(int $was, int $is): Code => Code::of(sprintf('moved %d %d', $was, $is)),
        now: static fn(int $is): Code => Code::of(sprintf('now %d', $is)),
        unsaid: static fn(): Code => Code::of('unsaid'),
        notForwarded: static fn(): Code => Code::of('none'),
    )->shown();
}

it('refuses a check against the latest start from a machine that will not say when it started', function (): void {
    expect(static fn(): WhatTheLastStartCameTo => WhatTheLastStartCameTo::said(WhenItSays::unsaid(), TheCheckKept::this(aCheckThatPassedAt(1)), TheCheckKept::none(), '', HowLongItHasFailed::notFailing()))
        ->toThrow(ARestartSaysTooLittle::class, 'no time for that start');
});

it('holds the newest check kept against an earlier start where the machine will not say when it started', function (): void {
    $last = WhatTheLastStartCameTo::said(WhenItSays::unsaid(), TheCheckKept::none(), TheCheckKept::this(aCheckThatPassedAt(5)), 'Autostart is off', HowLongItHasFailed::after(3, WhenItSays::at(Instant::atEpochSeconds(2))));

    expect($last->started()->isSaid())->toBeFalse()
        ->and($last->sinceIt()->isKept())->toBeFalse()
        ->and($last->newest()->either(kept: static fn(ACheckAfterAStart $check): Instant => $check->at(), none: static fn(): Instant => Instant::atEpochSeconds(0))->epochSeconds())->toBe(5)
        ->and($last->declined())->toBe('Autostart is off')
        ->and($last->failing()->either(failing: static fn(int $starts): Code => Code::of((string) $starts), notFailing: static fn(): Code => Code::of('none'))->shown())->toBe('3');
});

it('refuses failing after no start at all, holds failing after one, and says a stack that came back is not failing', function (): void {
    expect(static fn(): HowLongItHasFailed => HowLongItHasFailed::after(0, WhenItSays::unsaid()))->toThrow(ARestartSaysTooLittle::class, '`failed_boots`')
        ->and(HowLongItHasFailed::after(1, WhenItSays::unsaid())->either(failing: static fn(int $starts): Code => Code::of((string) $starts), notFailing: static fn(): Code => Code::of('none'))->shown())->toBe('1')
        ->and(HowLongItHasFailed::notFailing()->either(failing: static fn(): Code => Code::of('failing'), notFailing: static fn(): Code => Code::of('none'))->shown())->toBe('none');
});

it('carries everything a check said', function (): void {
    $check = ACheckAfterAStart::ran(Instant::atEpochSeconds(9), HowTheCheckAfterAStartEnded::Failed, TheServicesNamed::of('sonarr'), TheTunnelAfterAStart::notEstablished('No handshake'), ThePortAfterAStart::moved(1, 2, everyClientTakesIt: false), 'It did not come back');

    expect($check->at()->epochSeconds())->toBe(9)
        ->and($check->ended())->toBe(HowTheCheckAfterAStartEnded::Failed)
        ->and([...$check->notBack()])->toBe(['sonarr'])
        ->and($check->tunnel()->came())->toBe(HowTheTunnelCameBack::NotEstablished)
        ->and($check->tunnel()->said())->toBe('No handshake')
        ->and($check->port()->everyClientTakesIt())->toBeFalse()
        ->and($check->said())->toBe('It did not come back');
});

it('says a tunnel that held or that nothing checked with no words, and refuses one that failed with none', function (): void {
    expect(TheTunnelAfterAStart::held()->came())->toBe(HowTheTunnelCameBack::Held)
        ->and(TheTunnelAfterAStart::held()->said())->toBe('')
        ->and(TheTunnelAfterAStart::notChecked()->came())->toBe(HowTheTunnelCameBack::NotChecked)
        ->and(TheTunnelAfterAStart::notChecked()->said())->toBe('')
        ->and(static fn(): TheTunnelAfterAStart => TheTunnelAfterAStart::notEstablished(' '))->toThrow(ARestartSaysTooLittle::class, '`said`');
});

it('says what a port came to, each answer apart', function (ThePortAfterAStart $port, string $said, bool $taken): void {
    expect(whatThePortCameTo($port))->toBe($said)
        ->and($port->everyClientTakesIt())->toBe($taken);
})->with([
    'the same' => [fn(): ThePortAfterAStart => ThePortAfterAStart::same(51413, everyClientTakesIt: true), 'same 51413', true],
    'moved' => [fn(): ThePortAfterAStart => ThePortAfterAStart::moved(51413, 51999, everyClientTakesIt: false), 'moved 51413 51999', false],
    'moved to itself' => [fn(): ThePortAfterAStart => ThePortAfterAStart::moved(51413, 51413, everyClientTakesIt: true), 'same 51413', true],
    'named only after' => [fn(): ThePortAfterAStart => ThePortAfterAStart::cameUpOn(51999, everyClientTakesIt: true), 'now 51999', true],
    'not named' => [fn(): ThePortAfterAStart => ThePortAfterAStart::unsaid(everyClientTakesIt: false), 'unsaid', false],
    'none forwarded' => [ThePortAfterAStart::notForwarded(), 'none', true],
]);

it('names a moment or says it named none', function (): void {
    expect(WhenItSays::at(Instant::atEpochSeconds(7))->either(at: static fn(Instant $at): Code => Code::of((string) $at->epochSeconds()), unsaid: static fn(): Code => Code::of('unsaid'))->shown())->toBe('7')
        ->and(WhenItSays::unsaid()->either(at: static fn(): Code => Code::of('at'), unsaid: static fn(): Code => Code::of('unsaid'))->shown())->toBe('unsaid');
});

it('names what a check came to and how the tunnel came back by their wire words', function (): void {
    foreach (HowTheCheckAfterAStartEnded::cases() as $ended) {
        expect($ended->saidOnTheScreen())->toBe(sprintf('restarts.ended.%s', $ended->value));
    }

    foreach (HowTheTunnelCameBack::cases() as $came) {
        expect($came->saidOnTheScreen())->toBe(sprintf('restarts.tunnel.%s', $came->value));
    }
});
