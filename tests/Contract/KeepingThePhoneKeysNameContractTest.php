<?php

declare(strict_types=1);

use Modules\Kernel\Api\APhoneKeysName;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\KeepingThePhoneKeysName;
use Modules\Kernel\Api\Kept;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\ThePhoneKeysNameHeld;
use Modules\Vault\Api\PlatformPhoneKeysNames;
use Tests\Support\Fakes\APlatformStore;
use Tests\Support\Fakes\PhoneKeysNamesKeptInMemory;

// The KeepingThePhoneKeysName contract, run against the adapter over a hand-written stand-in for
// the platform's store and against the fake.

/** Named for this file: the root suites share one namespace (G10). */
function aStackAPhoneKeysNameIsKeptFor(string $seed = 'a'): StackId
{
    return StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST)));
}

/** The name held, or a word for none. */
function thePhoneKeysNameHeld(ThePhoneKeysNameHeld $held): string
{
    return $held->either(
        named: static fn(APhoneKeysName $name): Code => Code::of($name->shown()),
        none: static fn(): Code => Code::of('none'),
    )->shown();
}

/** Whether the name was kept, as a word. */
function whetherThePhoneKeysNameWasKept(Kept $kept): string
{
    return $kept->either(
        kept: static fn(): Code => Code::of('kept'),
        refused: static fn(): Code => Code::of('refused'),
    )->shown();
}

/** @return array<string, array{Closure(): KeepingThePhoneKeysName}> */
dataset('every implementation that keeps a phone key\'s name', [
    'the platform store' => [fn(): KeepingThePhoneKeysName => new PlatformPhoneKeysNames(APlatformStore::working())],
    'the fake' => [fn(): KeepingThePhoneKeysName => PhoneKeysNamesKeptInMemory::working()],
]);

/** @return array<string, array{Closure(): KeepingThePhoneKeysName}> */
dataset('every implementation that will not keep one', [
    'a platform store that will not open' => [fn(): KeepingThePhoneKeysName => new PlatformPhoneKeysNames(APlatformStore::refusing())],
    'a device with no store' => [fn(): KeepingThePhoneKeysName => new PlatformPhoneKeysNames(APlatformStore::absent())],
    'the fake' => [fn(): KeepingThePhoneKeysName => PhoneKeysNamesKeptInMemory::refusing()],
]);

it('holds no name for a stack no key was registered on', function (KeepingThePhoneKeysName $kept): void {
    expect(thePhoneKeysNameHeld($kept->nameOn(aStackAPhoneKeysNameIsKeptFor())))->toBe('none')
        ->and($kept->keepsAnythingOf(aStackAPhoneKeysNameIsKeptFor()))->toBeFalse();
})->with('every implementation that keeps a phone key\'s name');

it('gives back the name it kept, for that stack alone', function (KeepingThePhoneKeysName $kept): void {
    expect(whetherThePhoneKeysNameWasKept($kept->keepTheName(aStackAPhoneKeysNameIsKeptFor(), APhoneKeysName::of('Galaxy A51 · 7f3a'))))->toBe('kept')
        ->and(thePhoneKeysNameHeld($kept->nameOn(aStackAPhoneKeysNameIsKeptFor())))->toBe('Galaxy A51 · 7f3a')
        ->and(thePhoneKeysNameHeld($kept->nameOn(aStackAPhoneKeysNameIsKeptFor('b'))))->toBe('none')
        ->and($kept->keepsAnythingOf(aStackAPhoneKeysNameIsKeptFor()))->toBeTrue();
})->with('every implementation that keeps a phone key\'s name');

it('keeps the latest name in place of the one before it', function (KeepingThePhoneKeysName $kept): void {
    $kept->keepTheName(aStackAPhoneKeysNameIsKeptFor(), APhoneKeysName::of('Galaxy A51 · 7f3a'));
    $kept->keepTheName(aStackAPhoneKeysNameIsKeptFor(), APhoneKeysName::of('Galaxy A51 · 01bc'));

    expect(thePhoneKeysNameHeld($kept->nameOn(aStackAPhoneKeysNameIsKeptFor())))->toBe('Galaxy A51 · 01bc');
})->with('every implementation that keeps a phone key\'s name');

it('lets the name go with the stack, and says how much it let go of', function (KeepingThePhoneKeysName $kept): void {
    $kept->keepTheName(aStackAPhoneKeysNameIsKeptFor(), APhoneKeysName::of('Galaxy A51 · 7f3a'));

    expect($kept->forgetTheStack(aStackAPhoneKeysNameIsKeptFor())->howMany())->toBe(1)
        ->and(thePhoneKeysNameHeld($kept->nameOn(aStackAPhoneKeysNameIsKeptFor())))->toBe('none')
        ->and($kept->forgetTheStack(aStackAPhoneKeysNameIsKeptFor())->howMany())->toBe(0);
})->with('every implementation that keeps a phone key\'s name');

it('lets the name go when asked, and holds none after', function (KeepingThePhoneKeysName $kept): void {
    $kept->keepTheName(aStackAPhoneKeysNameIsKeptFor(), APhoneKeysName::of('Galaxy A51 · 7f3a'));
    $kept->keepTheName(aStackAPhoneKeysNameIsKeptFor('b'), APhoneKeysName::of('Galaxy A51 · 01bc'));

    expect(whetherThePhoneKeysNameWasKept($kept->letTheNameGo(aStackAPhoneKeysNameIsKeptFor())))->toBe('kept')
        ->and(thePhoneKeysNameHeld($kept->nameOn(aStackAPhoneKeysNameIsKeptFor())))->toBe('none')
        ->and(thePhoneKeysNameHeld($kept->nameOn(aStackAPhoneKeysNameIsKeptFor('b'))))->toBe('Galaxy A51 · 01bc');
})->with('every implementation that keeps a phone key\'s name');

it('says when it would not let a name go', function (KeepingThePhoneKeysName $kept): void {
    expect(whetherThePhoneKeysNameWasKept($kept->letTheNameGo(aStackAPhoneKeysNameIsKeptFor())))->toBe('refused');
})->with([
    'a platform store that will not open' => [fn(): KeepingThePhoneKeysName => new PlatformPhoneKeysNames(APlatformStore::refusing())],
    'the fake' => [fn(): KeepingThePhoneKeysName => PhoneKeysNamesKeptInMemory::refusing()],
]);

it('says when it would not keep a name, and holds none', function (KeepingThePhoneKeysName $kept): void {
    expect(whetherThePhoneKeysNameWasKept($kept->keepTheName(aStackAPhoneKeysNameIsKeptFor(), APhoneKeysName::of('Galaxy A51 · 7f3a'))))->toBe('refused')
        ->and(thePhoneKeysNameHeld($kept->nameOn(aStackAPhoneKeysNameIsKeptFor())))->toBe('none');
})->with('every implementation that will not keep one');

it('reads a blank name in the store as none, so a new key is registered under a new name', function (): void {
    $store = APlatformStore::working()->alreadyHolding(sprintf('lemonfiber.phone-key-name.%s', aStackAPhoneKeysNameIsKeptFor()->stored()), '  ');

    expect(thePhoneKeysNameHeld(new PlatformPhoneKeysNames($store)->nameOn(aStackAPhoneKeysNameIsKeptFor())))->toBe('none');
});

it('keeps the name under the stack it names, and nowhere else', function (): void {
    $store = APlatformStore::working();

    new PlatformPhoneKeysNames($store)->keepTheName(aStackAPhoneKeysNameIsKeptFor(), APhoneKeysName::of('Galaxy A51 · 7f3a'));

    expect($store->keysHeld())->toBe([sprintf('lemonfiber.phone-key-name.%s', aStackAPhoneKeysNameIsKeptFor()->stored())]);
});
