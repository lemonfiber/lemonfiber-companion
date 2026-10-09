<?php

declare(strict_types=1);

namespace Modules\Connection\Tests\Api;

use function expect;
use function it;

use Modules\Connection\Api\TheGrantForThisDevice;
use Modules\Kernel\Api\AGrant;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\TheGrantIsFor;
use Modules\Kernel\Api\ThisDevice;
use Modules\Kernel\Api\WhatTheGrantCameTo;
use Modules\Kernel\Api\Whose;

use function str_repeat;

use Tests\Support\APhoneHoldingTwoStacks;
use Tests\Support\Fakes\ADeviceWithAnId;
use Tests\Support\Fakes\AStackThatGrants;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\GrantsKeptInMemory;
use Tests\Support\TheGrantAsSeen;

/** A grant the core would answer, lasting until a moment. Named for this file. */
function aGrantForThisDevice(string $digit, int $until): AGrant
{
    return AGrant::of(str_repeat($digit, 32), Instant::atEpochSeconds($until));
}

/** What a grant came to, as the grant or the obstacle's kind. Named for this file. */
function whatTheGrantWas(WhatTheGrantCameTo $came): string
{
    return $came->either(
        granted: static fn(AGrant $grant): Code => Code::of($grant->forTheDoor()),
        refused: static fn(Obstacle $why): Code => Code::of($why->kind()->value),
    )->shown();
}

/** Ada, on the device these tests play on. */
function adaHere(): TheGrantIsFor
{
    return TheGrantIsFor::of(Whose::member('ada'), ThisDevice::named('this-device'));
}

/** The grant for this device over these, with a clock. Named for this file. */
function theGrantOver(AStackThatGrants $core, GrantsKeptInMemory $kept, FrozenClock $clock): TheGrantForThisDevice
{
    return new TheGrantForThisDevice($core, $kept, ADeviceWithAnId::named('this-device'), $clock);
}

it('asks the core once under this device\'s id, and keeps what it was granted', function (): void {
    $stack = APhoneHoldingTwoStacks::aStack('a');
    $core = AStackThatGrants::granting(aGrantForThisDevice('1', 1_000));
    $kept = GrantsKeptInMemory::working();
    $grants = theGrantOver($core, $kept, FrozenClock::at(Instant::atEpochSeconds(10)));

    expect(whatTheGrantWas($grants->on($stack, Session::of('a-session'), Whose::member('ada'))))->toBe(str_repeat('1', 32))
        ->and(whatTheGrantWas($grants->on($stack, Session::of('a-session'), Whose::member('ada'))))->toBe(str_repeat('1', 32))
        ->and($core->askedBy())->toBe(['this-device'])
        ->and(TheGrantAsSeen::of($kept->theGrantOn($stack->id(), adaHere())))->toStartWith(str_repeat('1', 32));
});

it('asks again under the same id once the grant has lapsed', function (): void {
    $stack = APhoneHoldingTwoStacks::aStack('a');
    $core = AStackThatGrants::granting(aGrantForThisDevice('1', 1_000), aGrantForThisDevice('2', 5_000));
    $clock = FrozenClock::at(Instant::atEpochSeconds(10));
    $grants = theGrantOver($core, GrantsKeptInMemory::working(), $clock);

    $grants->on($stack, Session::of('a-session'), Whose::member('ada'));
    $clock->moveTo(Instant::atEpochSeconds(1_000));

    expect(whatTheGrantWas($grants->on($stack, Session::of('a-session'), Whose::member('ada'))))->toBe(str_repeat('2', 32))
        ->and($core->askedBy())->toBe(['this-device', 'this-device']);
});

it('lets the kept grant go and asks again where the door refused it', function (): void {
    $stack = APhoneHoldingTwoStacks::aStack('a');
    $core = AStackThatGrants::granting(aGrantForThisDevice('1', 9_000), aGrantForThisDevice('2', 9_000));
    $kept = GrantsKeptInMemory::working();
    $grants = theGrantOver($core, $kept, FrozenClock::at(Instant::atEpochSeconds(10)));

    $grants->on($stack, Session::of('a-session'), Whose::member('ada'));

    expect(whatTheGrantWas($grants->afresh($stack, Session::of('a-session'), Whose::member('ada'))))->toBe(str_repeat('2', 32))
        ->and(TheGrantAsSeen::of($kept->theGrantOn($stack->id(), adaHere())))->toStartWith(str_repeat('2', 32));
});

it('keeps a grant per stack, each asked for under the one id', function (): void {
    $core = AStackThatGrants::granting(aGrantForThisDevice('1', 9_000), aGrantForThisDevice('2', 9_000));
    $kept = GrantsKeptInMemory::working();
    $grants = theGrantOver($core, $kept, FrozenClock::at(Instant::atEpochSeconds(10)));

    $grants->on(APhoneHoldingTwoStacks::aStack('a'), Session::of('a-session'), Whose::member('ada'));
    $grants->on(APhoneHoldingTwoStacks::aStack('b'), Session::of('a-session'), Whose::member('ada'));

    expect($core->askedBy())->toBe(['this-device', 'this-device'])
        ->and(TheGrantAsSeen::of($kept->theGrantOn(APhoneHoldingTwoStacks::aStack('a')->id(), adaHere())))->toStartWith(str_repeat('1', 32))
        ->and(TheGrantAsSeen::of($kept->theGrantOn(APhoneHoldingTwoStacks::aStack('b')->id(), adaHere())))->toStartWith(str_repeat('2', 32));
});

it('says why where the core granted nothing, and keeps nothing', function (): void {
    $stack = APhoneHoldingTwoStacks::aStack('a');
    $kept = GrantsKeptInMemory::working();
    $grants = theGrantOver(AStackThatGrants::refusing(Obstacle::of(KindOfObstacle::CredentialWasRefused)), $kept, FrozenClock::at(Instant::atEpochSeconds(10)));

    expect(whatTheGrantWas($grants->on($stack, Session::of('a-session'), Whose::member('ada'))))->toBe(KindOfObstacle::CredentialWasRefused->value)
        ->and(TheGrantAsSeen::of($kept->theGrantOn($stack->id(), adaHere())))->toBe(TheGrantAsSeen::NONE);
});

it('plays with a grant the store would not keep, and asks again next time', function (): void {
    $stack = APhoneHoldingTwoStacks::aStack('a');
    $core = AStackThatGrants::granting(aGrantForThisDevice('1', 9_000));
    $grants = theGrantOver($core, GrantsKeptInMemory::refusing(), FrozenClock::at(Instant::atEpochSeconds(10)));

    expect(whatTheGrantWas($grants->on($stack, Session::of('a-session'), Whose::member('ada'))))->toBe(str_repeat('1', 32))
        ->and(whatTheGrantWas($grants->on($stack, Session::of('a-session'), Whose::member('ada'))))->toBe(str_repeat('1', 32))
        ->and($core->askedBy())->toHaveCount(2);
});

it('asks afresh for a member who signs in where another played, and hands neither the other\'s grant', function (): void {
    $stack = APhoneHoldingTwoStacks::aStack('a');
    $core = AStackThatGrants::granting(aGrantForThisDevice('1', 9_000), aGrantForThisDevice('2', 9_000));
    $kept = GrantsKeptInMemory::working();
    $grants = theGrantOver($core, $kept, FrozenClock::at(Instant::atEpochSeconds(10)));

    $grants->on($stack, Session::of('ada-session'), Whose::member('ada'));

    expect(whatTheGrantWas($grants->on($stack, Session::of('grace-session'), Whose::member('grace'))))->toBe(str_repeat('2', 32))
        ->and($core->askedBy())->toHaveCount(2)
        ->and(TheGrantAsSeen::of($kept->theGrantOn($stack->id(), adaHere())))->toBe(TheGrantAsSeen::NONE);
});
