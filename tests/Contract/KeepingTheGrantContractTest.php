<?php

declare(strict_types=1);

use Modules\Kernel\Api\AGrant;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KeepingTheGrant;
use Modules\Kernel\Api\Kept;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\TheGrantIsFor;
use Modules\Kernel\Api\ThisDevice;
use Modules\Kernel\Api\Whose;
use Modules\Vault\Api\PlatformGrants;
use Tests\Support\Fakes\APlatformStore;
use Tests\Support\Fakes\GrantsKeptInMemory;
use Tests\Support\TheGrantAsSeen;

// The KeepingTheGrant contract, run against the adapter and against the fake.
//
// What both must agree on: the grant this device holds on each stack, given
// back as kept until replaced or let go of, and nothing where the device would
// not keep it. The adapter runs over a hand-written
// stand-in for the platform's store, as every adapter here does.

/** Named for this file: the root suites share one namespace (G10). */
function aStackAGrantIsKeptFor(string $seed = 'a'): StackId
{
    return StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST)));
}

/** A grant as the core answers one, lasting until a moment. Named for this file. */
function aGrantLastingUntil(int $seconds, string $digit = '0'): AGrant
{
    return AGrant::of(str_repeat($digit, 32), Instant::atEpochSeconds($seconds));
}

/** Whom the grants in this file are kept for. */
function aGrantKeptForAda(string $member = 'ada', string $device = 'this-device'): TheGrantIsFor
{
    return TheGrantIsFor::of(Whose::member($member), ThisDevice::named($device));
}

/** Whether something was kept, as a word. Named for this file. */
function whetherTheGrantWasKept(Kept $kept): string
{
    return $kept->either(
        kept: static fn(): Code => Code::of('kept'),
        refused: static fn(): Code => Code::of('refused'),
    )->shown();
}

/** @return array<string, array{Closure(): KeepingTheGrant}> */
dataset('every implementation that keeps a grant', [
    'the platform store' => [fn(): KeepingTheGrant => new PlatformGrants(APlatformStore::working())],
    'the fake' => [fn(): KeepingTheGrant => GrantsKeptInMemory::working()],
]);

/** @return array<string, array{Closure(): KeepingTheGrant}> */
dataset('every implementation that will not keep one', [
    'a platform store that will not open' => [fn(): KeepingTheGrant => new PlatformGrants(APlatformStore::refusing())],
    'a device with no store' => [fn(): KeepingTheGrant => new PlatformGrants(APlatformStore::absent())],
    'the fake' => [fn(): KeepingTheGrant => GrantsKeptInMemory::refusing()],
]);

it('holds no grant for a stack it was never asked about', function (KeepingTheGrant $kept): void {
    expect(TheGrantAsSeen::of($kept->theGrantOn(aStackAGrantIsKeptFor(), aGrantKeptForAda())))->toBe(TheGrantAsSeen::NONE)
        ->and($kept->keepsAnythingOf(aStackAGrantIsKeptFor()))->toBeFalse();
})->with('every implementation that keeps a grant');

it('gives back the grant it kept', function (KeepingTheGrant $kept): void {
    $stack = aStackAGrantIsKeptFor();

    expect(whetherTheGrantWasKept($kept->keepTheGrant($stack, aGrantKeptForAda(), aGrantLastingUntil(2_000))))->toBe('kept')
        ->and($kept->keepsAnythingOf($stack))->toBeTrue()
        ->and(TheGrantAsSeen::of($kept->theGrantOn($stack, aGrantKeptForAda())))->toBe(sprintf('%s until 2000', str_repeat('0', 32)));
})->with('every implementation that keeps a grant');

it('keeps the latest grant in place of the one before it', function (KeepingTheGrant $kept): void {
    $stack = aStackAGrantIsKeptFor();

    $kept->keepTheGrant($stack, aGrantKeptForAda(), aGrantLastingUntil(1_000, '1'));
    $kept->keepTheGrant($stack, aGrantKeptForAda(), aGrantLastingUntil(2_000, '2'));

    expect(TheGrantAsSeen::of($kept->theGrantOn($stack, aGrantKeptForAda())))->toStartWith(str_repeat('2', 32));
})->with('every implementation that keeps a grant');

it('keeps one stack apart from another', function (KeepingTheGrant $kept): void {
    $one = aStackAGrantIsKeptFor('a');
    $other = aStackAGrantIsKeptFor('b');

    $kept->keepTheGrant($one, aGrantKeptForAda(), aGrantLastingUntil(1_000, '1'));
    $kept->keepTheGrant($other, aGrantKeptForAda(), aGrantLastingUntil(1_000, '2'));
    $kept->letTheGrantGo($other);

    expect(TheGrantAsSeen::of($kept->theGrantOn($one, aGrantKeptForAda())))->toStartWith(str_repeat('1', 32))
        ->and(TheGrantAsSeen::of($kept->theGrantOn($other, aGrantKeptForAda())))->toBe(TheGrantAsSeen::NONE);
})->with('every implementation that keeps a grant');

it('holds no grant once it is let go of', function (KeepingTheGrant $kept): void {
    $stack = aStackAGrantIsKeptFor();
    $kept->keepTheGrant($stack, aGrantKeptForAda(), aGrantLastingUntil(2_000));

    expect(whetherTheGrantWasKept($kept->letTheGrantGo($stack)))->toBe('kept')
        ->and(TheGrantAsSeen::of($kept->theGrantOn($stack, aGrantKeptForAda())))->toBe(TheGrantAsSeen::NONE)
        ->and($kept->keepsAnythingOf($stack))->toBeFalse();
})->with('every implementation that keeps a grant');

it('says nothing was kept, and reads nothing back, where the device would not keep it', function (KeepingTheGrant $kept): void {
    $stack = aStackAGrantIsKeptFor();

    expect(whetherTheGrantWasKept($kept->keepTheGrant($stack, aGrantKeptForAda(), aGrantLastingUntil(2_000))))->toBe('refused')
        ->and(whetherTheGrantWasKept($kept->letTheGrantGo($stack)))->toBe('refused')
        ->and(TheGrantAsSeen::of($kept->theGrantOn($stack, aGrantKeptForAda())))->toBe(TheGrantAsSeen::NONE);
})->with('every implementation that will not keep one');

it('hands a grant only to the member and device it was kept for', function (KeepingTheGrant $kept): void {
    $stack = aStackAGrantIsKeptFor();
    $kept->keepTheGrant($stack, aGrantKeptForAda(), aGrantLastingUntil(2_000));

    expect(TheGrantAsSeen::of($kept->theGrantOn($stack, aGrantKeptForAda('grace'))))->toBe(TheGrantAsSeen::NONE)
        ->and(TheGrantAsSeen::of($kept->theGrantOn($stack, aGrantKeptForAda(device: 'another-device'))))->toBe(TheGrantAsSeen::NONE)
        ->and(TheGrantAsSeen::of($kept->theGrantOn($stack, TheGrantIsFor::of(Whose::theOperator(), ThisDevice::named('this-device')))))->toBe(TheGrantAsSeen::NONE)
        ->and(TheGrantAsSeen::of($kept->theGrantOn($stack, aGrantKeptForAda())))->toStartWith(str_repeat('0', 32));
})->with('every implementation that keeps a grant');
