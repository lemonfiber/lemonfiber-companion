<?php

declare(strict_types=1);

namespace Modules\Vault\Tests\Api;

use function expect;
use function it;
use function json_encode;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfWork;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhatAReturnFinds;
use Modules\Vault\Api\PlatformStacks;
use Modules\Vault\Api\PlatformWorkLeftRunning;

use function sprintf;
use function str_repeat;

use Tests\Support\Fakes\APlatformStore;

/**
 * What the contract deliberately leaves out.
 *
 * The contract asserts what this adapter and the fake must both promise, which
 * cannot include a stored value — the fake stores no encoding. Writing one and
 * reading it back is this adapter's whole job, and the rules about a key per
 * stack, a shape number and an unrecognised shape are about exactly that, so
 * they are driven here.
 *
 * Every value this build did not write reads as nothing to follow, rather than
 * raising: the screen opens offering to start the work, and the work itself
 * goes on on the stack whatever this device holds.
 */
function aStackAWalkWasLeftOn(string $seed = 'a'): StackId
{
    return StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST)));
}

/** The key a stack's walkthrough handle is kept under. */
function whereAWalkIsKeptFor(StackId $stack): string
{
    return sprintf('lemonfiber.left-running.walkthrough.%s', $stack->stored());
}

/** What a return would find, as a word. Named for this file: the vault's module suites share one namespace (`G10`). */
function whatTheStoreGivesBack(WhatAReturnFinds $finds): string
{
    return $finds->either(
        job: static fn(Job $job): Code => Code::of(sprintf('follows:%s', $job->shown())),
        nothing: static fn(): Code => Code::of('nothing-to-follow'),
    )->shown();
}

/** A working store already holding a value for the stack's walkthrough, written as the argument says. */
function aStoreWithAWalkLeft(mixed $value): APlatformStore
{
    return APlatformStore::working()->alreadyHolding(whereAWalkIsKeptFor(aStackAWalkWasLeftOn()), (string) json_encode($value));
}

/** What the adapter over that store finds for the stack's walkthrough. */
function whatIsFoundIn(APlatformStore $store): string
{
    return whatTheStoreGivesBack(new PlatformWorkLeftRunning($store, new PlatformStacks($store))->whatWasLeft(aStackAWalkWasLeftOn(), KindOfWork::Walkthrough));
}

it('writes the handle with the shape it reads, under a key for the stack and the kind of work', function (): void {
    $store = APlatformStore::working();

    new PlatformWorkLeftRunning($store, new PlatformStacks($store))->remember(aStackAWalkWasLeftOn(), KindOfWork::Walkthrough, Job::named('a-walk-left-running'));

    expect($store->keysHeld())->toBe([whereAWalkIsKeptFor(aStackAWalkWasLeftOn())])
        ->and($store->whatIsUnder(whereAWalkIsKeptFor(aStackAWalkWasLeftOn())))->toBe('{"shape":1,"job":"a-walk-left-running"}');
});

it('keeps each stack under a key of its own', function (): void {
    $store = APlatformStore::working();
    $left = new PlatformWorkLeftRunning($store, new PlatformStacks($store));

    $left->remember(aStackAWalkWasLeftOn('a'), KindOfWork::Walkthrough, Job::named('the-walk-on-one'));
    $left->remember(aStackAWalkWasLeftOn('b'), KindOfWork::Walkthrough, Job::named('the-walk-on-the-other'));

    expect($store->keysHeld())->toBe([whereAWalkIsKeptFor(aStackAWalkWasLeftOn('a')), whereAWalkIsKeptFor(aStackAWalkWasLeftOn('b'))]);
});

it('reads back what an earlier session wrote, which is the point of the store', function (): void {
    // The clause the contract cannot assert, because the fake does not survive
    // a launch. A second adapter over the same store is the nearest thing to
    // opening the screen again that a test can arrange.
    $store = APlatformStore::working();

    new PlatformWorkLeftRunning($store, new PlatformStacks($store))->remember(aStackAWalkWasLeftOn(), KindOfWork::Walkthrough, Job::named('a-walk-left-running'));

    expect(whatIsFoundIn($store))->toBe('follows:a-walk-left-running');
});

it('takes the handle out of the store when it lets go of it', function (): void {
    $store = aStoreWithAWalkLeft(['shape' => 1, 'job' => 'a-walk-that-ended']);

    new PlatformWorkLeftRunning($store, new PlatformStacks($store))->forget(aStackAWalkWasLeftOn(), KindOfWork::Walkthrough);

    expect($store->keysHeld())->toBe([]);
});

it('discards a value written in a shape this build does not know', function (mixed $shape): void {
    // Never interpreted as though it were current. A handle read out of a value
    // from another build would ask the stack about work nobody here started.
    expect(whatIsFoundIn(aStoreWithAWalkLeft(['shape' => $shape, 'job' => 'a-walk-from-elsewhere'])))->toBe('nothing-to-follow');
})->with(['a newer shape' => [2], 'the right number as text' => ['1'], 'no number at all' => [null]]);

it('discards a value that names no shape', function (): void {
    expect(whatIsFoundIn(aStoreWithAWalkLeft(['job' => 'a-walk-from-elsewhere'])))->toBe('nothing-to-follow');
});

it('discards a value that is not a value this build writes at all', function (mixed $value): void {
    expect(whatIsFoundIn(aStoreWithAWalkLeft($value)))->toBe('nothing-to-follow');
})->with(['text' => ['not a value'], 'a number' => [1], 'a list' => [[1, 'a-walk']]]);

it('discards a value that is not JSON', function (): void {
    $store = APlatformStore::working()->alreadyHolding(whereAWalkIsKeptFor(aStackAWalkWasLeftOn()), 'a-walk-written-bare');

    expect(whatIsFoundIn($store))->toBe('nothing-to-follow');
});

it('finds nothing in a value of the right shape that holds no handle', function (): void {
    expect(whatIsFoundIn(aStoreWithAWalkLeft(['shape' => 1])))->toBe('nothing-to-follow');
});

it('finds nothing where the handle is not text', function (mixed $job): void {
    expect(whatIsFoundIn(aStoreWithAWalkLeft(['shape' => 1, 'job' => $job])))->toBe('nothing-to-follow');
})->with(['a number' => [42], 'nothing' => [null], 'a list' => [['a-walk']]]);

it('finds nothing where the handle is blank, which no stack answers', function (string $job): void {
    // `Job` refuses a blank name, and a stored one is read the same way rather
    // than raising on the screen that opened.
    expect(whatIsFoundIn(aStoreWithAWalkLeft(['shape' => 1, 'job' => $job])))->toBe('nothing-to-follow');
})->with(['empty' => [''], 'only spaces' => ['   ']]);

it('finds nothing in a store that will not open, whatever it holds', function (): void {
    // Seeded as though an earlier launch had kept a handle, and still read as
    // nothing: a store that cannot be asked is not a store to follow work from.
    $store = APlatformStore::refusing()->alreadyHolding(whereAWalkIsKeptFor(aStackAWalkWasLeftOn()), '{"shape":1,"job":"a-walk-behind-a-shut-store"}');

    expect(whatIsFoundIn($store))->toBe('nothing-to-follow');
});

it('keeps no handle whose value cannot be written down, and says a return will find nothing', function (): void {
    // A handle is the stack's text and `Job` asks only that it not be blank, so
    // a byte sequence that is not text reaches `json_encode`, which answers
    // false. Nothing is written, and that is what a return will find.
    $store = APlatformStore::working();

    $went = new PlatformWorkLeftRunning($store, new PlatformStacks($store))->remember(aStackAWalkWasLeftOn(), KindOfWork::Walkthrough, Job::named("a handle with a broken byte \xB1\x31 in it"));

    expect(whatTheStoreGivesBack($went))->toBe('nothing-to-follow')
        ->and($store->keysHeld())->toBe([]);
});
