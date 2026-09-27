<?php

declare(strict_types=1);

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfWork;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhatAReturnFinds;
use Modules\Kernel\Api\WorkLeftRunning;
use Modules\Vault\Api\PlatformWorkLeftRunning;
use Tests\Support\Fakes\APlatformStore;
use Tests\Support\Fakes\WorkLeftRunningInMemory;

// The WorkLeftRunning contract, run against the adapter and against the fake.
//
// G2's shape. What both must agree on is what a return finds: the handle that
// was kept, for the stack and kind it was kept for, until it is replaced or let
// go of — and nothing where the device would not keep it. That is the whole of
// what lets a screen left and opened again show where the work got to.
//
// The adapter is driven against a hand-written stand-in for the platform's own
// store, as every adapter here is: there is no Keychain behind a PHP process on
// a laptop, and without one the adapter is a file nothing executes.
//
// What is not asserted is survival across a launch, or what a stored value
// looks like. The platform's store survives one and the fake does not, and the
// fake stores no encoding, so both belong to the adapter's own tests — a
// contract asserting either would fail on the fake or be weakened to pass, and
// a weakened contract is how a fake drifts.

/** Named for this file: the root suites share one namespace (G10). */
function aStackSomethingWasLeftRunningOn(string $seed = 'a'): StackId
{
    return StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST)));
}

/** What a return finds, as a word. Named for this file, for the same reason (G10). */
function whatAReturnWouldFind(WhatAReturnFinds $finds): string
{
    return $finds->either(
        job: static fn(Job $job): Code => Code::of(sprintf('follows:%s', $job->shown())),
        nothing: static fn(): Code => Code::of('nothing-to-follow'),
    )->shown();
}

/** @return array<string, array{Closure(): WorkLeftRunning}> */
dataset('every implementation that keeps work left running', [
    'the platform store' => [fn(): WorkLeftRunning => new PlatformWorkLeftRunning(APlatformStore::working())],
    'the fake' => [fn(): WorkLeftRunning => WorkLeftRunningInMemory::working()],
]);

/** @return array<string, array{Closure(): WorkLeftRunning}> */
dataset('every implementation that will not keep one', [
    'a platform store that will not open' => [fn(): WorkLeftRunning => new PlatformWorkLeftRunning(APlatformStore::refusing())],
    'a device with no store' => [fn(): WorkLeftRunning => new PlatformWorkLeftRunning(APlatformStore::absent())],
    'the fake' => [fn(): WorkLeftRunning => WorkLeftRunningInMemory::refusing()],
]);

it('finds nothing on a stack nothing was left running on', function (WorkLeftRunning $left): void {
    expect(whatAReturnWouldFind($left->whatWasLeft(aStackSomethingWasLeftRunningOn(), KindOfWork::Walkthrough)))->toBe('nothing-to-follow');
})->with('every implementation that keeps work left running');

it('gives back the handle it kept, and says a return will find it', function (WorkLeftRunning $left): void {
    $stack = aStackSomethingWasLeftRunningOn();

    expect(whatAReturnWouldFind($left->remember($stack, KindOfWork::Walkthrough, Job::named('a-walk-left-running'))))->toBe('follows:a-walk-left-running')
        ->and(whatAReturnWouldFind($left->whatWasLeft($stack, KindOfWork::Walkthrough)))->toBe('follows:a-walk-left-running');
})->with('every implementation that keeps work left running');

it('keeps the latest handle in place of the one before it', function (WorkLeftRunning $left): void {
    // Starting new work lets go of the old. Keeping both would be this app
    // deciding which to follow, and the one it got wrong would be the walk the
    // operator had just started.
    $stack = aStackSomethingWasLeftRunningOn();

    $left->remember($stack, KindOfWork::Walkthrough, Job::named('the-first-walk'));
    $left->remember($stack, KindOfWork::Walkthrough, Job::named('the-second-walk'));

    expect(whatAReturnWouldFind($left->whatWasLeft($stack, KindOfWork::Walkthrough)))->toBe('follows:the-second-walk');
})->with('every implementation that keeps work left running');

it('keeps one stack apart from another', function (WorkLeftRunning $left): void {
    $one = aStackSomethingWasLeftRunningOn('a');
    $other = aStackSomethingWasLeftRunningOn('b');

    $left->remember($one, KindOfWork::Walkthrough, Job::named('the-walk-on-one'));
    $left->remember($other, KindOfWork::Walkthrough, Job::named('the-walk-on-the-other'));
    $left->forget($other, KindOfWork::Walkthrough);

    expect(whatAReturnWouldFind($left->whatWasLeft($one, KindOfWork::Walkthrough)))->toBe('follows:the-walk-on-one')
        ->and(whatAReturnWouldFind($left->whatWasLeft($other, KindOfWork::Walkthrough)))->toBe('nothing-to-follow');
})->with('every implementation that keeps work left running');

it('finds nothing once the handle is let go of, and says so', function (WorkLeftRunning $left): void {
    $stack = aStackSomethingWasLeftRunningOn();
    $left->remember($stack, KindOfWork::Walkthrough, Job::named('a-walk-that-ended'));

    expect(whatAReturnWouldFind($left->forget($stack, KindOfWork::Walkthrough)))->toBe('nothing-to-follow')
        ->and(whatAReturnWouldFind($left->whatWasLeft($stack, KindOfWork::Walkthrough)))->toBe('nothing-to-follow');
})->with('every implementation that keeps work left running');

it('lets go of a handle it never kept without complaint', function (WorkLeftRunning $left): void {
    expect(whatAReturnWouldFind($left->forget(aStackSomethingWasLeftRunningOn(), KindOfWork::Walkthrough)))->toBe('nothing-to-follow');
})->with('every implementation that keeps work left running');

it('says a return will find nothing where the device would not keep the handle', function (WorkLeftRunning $left): void {
    // The one answer a screen has to be told while it is still open: the walk
    // goes on, and the way back to it does not.
    $stack = aStackSomethingWasLeftRunningOn();

    expect(whatAReturnWouldFind($left->remember($stack, KindOfWork::Walkthrough, Job::named('a-walk-nowhere-noted'))))->toBe('nothing-to-follow')
        ->and(whatAReturnWouldFind($left->whatWasLeft($stack, KindOfWork::Walkthrough)))->toBe('nothing-to-follow')
        ->and(whatAReturnWouldFind($left->forget($stack, KindOfWork::Walkthrough)))->toBe('nothing-to-follow');
})->with('every implementation that will not keep one');
