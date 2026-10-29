<?php

declare(strict_types=1);

use Bootstrap\Composition\ArtworkGoesWithTheSession;
use Bootstrap\Composition\EveryKeeperOfAStack;
use Bootstrap\Composition\NothingToAsk;
use Modules\Device\Api\PlatformPhoneKeys;
use Modules\Health\Api\KeepingTheLastReading;
use Modules\Kernel\Api\ForgetsAStack;
use Modules\Kernel\Api\HoldingAPhoneKey;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KeepingTheGrant;
use Modules\Kernel\Api\KeepingThePhoneKeysName;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Stacks;
use Modules\News\Api\Noticing;
use Modules\News\Internal\NewsOfAStack;
use Modules\News\Internal\WhatEachStackLastNamed;
use Modules\Requests\Api\KeepingWhatWasAsked;
use Modules\Sdk\Api\Assessors;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Internal\WhatEachStackOffers;
use Modules\Services\Api\KeepingWhatItRuns;
use Modules\Updates\Api\KeepingTheLastUpkeep;
use Modules\Vault\Api\PlatformGrants;
use Modules\Vault\Api\PlatformKeychain;
use Modules\Vault\Api\PlatformPhoneKeysNames;
use Modules\Vault\Api\PlatformStacks;
use Modules\Vault\Api\PlatformStandings;
use Modules\Vault\Api\PlatformWhereHearingLeftOff;
use Modules\Vault\Api\PlatformWhereTheOperatorWas;
use Modules\Vault\Api\PlatformWorkLeftRunning;
use Modules\Watching\Api\KeepingTheirLanguages;
use Saloon\Http\Faking\MockClient;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\APhoneKeyInMemory;
use Tests\Support\Fakes\APhoneKeyOnAHandset;
use Tests\Support\Fakes\APlatformStore;
use Tests\Support\Fakes\ArtworkHeldInMemory;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\GrantsKeptInMemory;
use Tests\Support\Fakes\NewsKeptInMemory;
use Tests\Support\Fakes\PhoneKeysNamesKeptInMemory;
use Tests\Support\Fakes\ReadingsInMemory;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\Fakes\StandingsInMemory;
use Tests\Support\Fakes\WhereHearingLeftOffInMemory;
use Tests\Support\Fakes\WhereTheOperatorWasInMemory;
use Tests\Support\Fakes\WorkLeftRunningInMemory;
use Tests\Support\WhatEveryKeeperKeeps;
use Tests\Support\WhatTheContractAccepts;
use Tests\Support\WhatTheModulesDeclare;
use Tests\Support\WhatThePhoneKeeps;

pest()->group('holds:bootstrap/Composition');

// The ForgetsAStack contract, run against every keeper of a stack and against
// all of them together: what it keeps for one stack goes, what it keeps for
// any other stays, and it says afterwards that it keeps nothing of the one.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/**
 * Every keeper, each holding something for two stacks.
 *
 * @return array<string, ForgetsAStack>
 */
function everyKeeperHoldingTwoStacks(): array
{
    $store = APlatformStore::working();
    $pairings = StacksInMemory::working();
    $words = StandingsInMemory::working();
    $keepers = [
        'the platform pairings' => new PlatformStacks(APlatformStore::working()),
        'the fake pairings' => StacksInMemory::working(),
        'the platform keychain' => new PlatformKeychain(APlatformStore::working()),
        'the fake keychain' => AKeychainInMemory::working(),
        'the keychain letting go of artwork' => new ArtworkGoesWithTheSession(new PlatformKeychain(APlatformStore::working()), ArtworkHeldInMemory::working()),
        'the readings' => new KeepingTheLastReading(ASealInMemory::working(), ReadingsInMemory::empty()),
        'the upkeep' => new KeepingTheLastUpkeep(ASealInMemory::working(), ReadingsInMemory::empty()),
        'what it runs' => WhatThePhoneKeeps::noListingYet(),
        'what the household asked for' => WhatThePhoneKeeps::noRequestsYet(),
        'the languages members chose' => WhatThePhoneKeeps::noLanguagesYet(),
        'what is new' => whatNoticesNewsOver(ASealInMemory::working()),
        'the platform words' => new PlatformStandings(APlatformStore::working()),
        'the fake words' => StandingsInMemory::working(),
        'the platform work' => new PlatformWorkLeftRunning($store, new PlatformStacks($store)),
        'the fake work' => WorkLeftRunningInMemory::working(),
        'the platform place' => new PlatformWhereTheOperatorWas(APlatformStore::working()),
        'the fake place' => WhereTheOperatorWasInMemory::nowhere(),
        'the platform place heard to' => new PlatformWhereHearingLeftOff(APlatformStore::working(), new PlatformStacks(APlatformStore::working())),
        'the fake place heard to' => WhereHearingLeftOffInMemory::nowhere(),
        'the platform grants' => new PlatformGrants(APlatformStore::working()),
        'the fake grants' => GrantsKeptInMemory::working(),
        'the platform phone key names' => new PlatformPhoneKeysNames(APlatformStore::working()),
        'the fake phone key names' => PhoneKeysNamesKeptInMemory::working(),
        'the phone\'s keys' => thePhonesKeysOnAHandset(),
        'the fake phone\'s keys' => APhoneKeyInMemory::onAnAndroidPhone(),
        'what each stack offers' => new Assessors(new PinnedClients(), FrozenClock::at(Instant::atEpochSeconds(0)), new WhatEachStackOffers()),
    ];

    foreach ([...$keepers, $pairings, $words] as $keeper) {
        foreach (['a', 'b'] as $seed) {
            WhatEveryKeeperKeeps::somethingFor($keeper, WhatEveryKeeperKeeps::aStack($seed));
        }
    }

    return [...$keepers, 'every keeper together' => new EveryKeeperOfAStack($pairings, $words)];
}

/** What notices what is new on a stack, over this seal and a store of its own. */
function whatNoticesNewsOver(ASealInMemory $seal): Noticing
{
    return new Noticing(new NewsOfAStack($seal, NewsKeptInMemory::empty(), FrozenClock::at(Instant::atEpochSeconds(0)), new WhatEachStackLastNamed()));
}

/** The phone's own keys, over a handset that keeps them. */
function thePhonesKeysOnAHandset(): PlatformPhoneKeys
{
    return APhoneKeyOnAHandset::onAnAndroidPhone()->theAdapterOverIt();
}

it('keeps something for each stack it was given', function (): void {
    foreach (everyKeeperHoldingTwoStacks() as $which => $keeper) {
        expect($keeper->keepsAnythingOf(WhatEveryKeeperKeeps::aStack('a')->id()))->toBeTrue($which)
            ->and($keeper->keepsAnythingOf(WhatEveryKeeperKeeps::aStack('b')->id()))->toBeTrue($which);
    }
});

it('lets go of everything it keeps for one stack, and of nothing it keeps for another', function (): void {
    foreach (everyKeeperHoldingTwoStacks() as $which => $keeper) {
        expect($keeper->forgetTheStack(WhatEveryKeeperKeeps::aStack('a')->id())->howMany())->toBeGreaterThan(0, $which)
            ->and($keeper->keepsAnythingOf(WhatEveryKeeperKeeps::aStack('a')->id()))->toBeFalse($which)
            ->and($keeper->keepsAnythingOf(WhatEveryKeeperKeeps::aStack('b')->id()))->toBeTrue($which)
            ->and($keeper->forgetTheStack(WhatEveryKeeperKeeps::aStack('a')->id())->howMany())->toBe(0, $which);
    }
});

it('keeps something of a stack, every keeper together, where only one of them does', function (): void {
    $pairings = StacksInMemory::working();
    WhatEveryKeeperKeeps::somethingFor($pairings, WhatEveryKeeperKeeps::aStack('a'));

    expect(new EveryKeeperOfAStack($pairings, StandingsInMemory::working())->keepsAnythingOf(WhatEveryKeeperKeeps::aStack('a')->id()))->toBeTrue()
        ->and(new EveryKeeperOfAStack(StacksInMemory::working(), StandingsInMemory::working())->keepsAnythingOf(WhatEveryKeeperKeeps::aStack('a')->id()))->toBeFalse();
});

it('refuses to be built, every keeper together, with no keeper to ask', function (): void {
    expect(static fn(): EveryKeeperOfAStack => new EveryKeeperOfAStack())->toThrow(NothingToAsk::class);
});

it('asks every keeper of a stack the modules declare, when the app forgets a stack', function (): void {
    WhatEveryKeeperKeeps::inTheAppOverStoresOfItsOwn();
    $composed = WhatEveryKeeperKeeps::askedAsOne(app(ForgetsAStack::class));

    foreach (WhatTheModulesDeclare::theirOwn(ForgetsAStack::class) as $declared) {
        expect(array_any($composed, static fn(ForgetsAStack $keeper): bool => $keeper instanceof $declared))->toBeTrue($declared);
    }
});

it('lets go of a stack in every keeper the app is composed with, the pairing first, and of nothing of another stack', function (): void {
    WhatEveryKeeperKeeps::inTheAppOverStoresOfItsOwn();
    $forgetting = app(ForgetsAStack::class);
    $composed = WhatEveryKeeperKeeps::askedAsOne($forgetting);

    foreach ($composed as $keeper) {
        WhatEveryKeeperKeeps::somethingFor($keeper, WhatEveryKeeperKeeps::aStack('a'));
        WhatEveryKeeperKeeps::somethingFor($keeper, WhatEveryKeeperKeeps::aStack('b'));
        expect($keeper->keepsAnythingOf(WhatEveryKeeperKeeps::aStack('a')->id()))->toBeTrue($keeper::class);
    }

    $forgetting->forgetTheStack(WhatEveryKeeperKeeps::aStack('a')->id());

    expect($composed[0])->toBeInstanceOf(Stacks::class);

    foreach ($composed as $keeper) {
        expect($keeper->keepsAnythingOf(WhatEveryKeeperKeeps::aStack('a')->id()))->toBeFalse($keeper::class)
            ->and($keeper->keepsAnythingOf(WhatEveryKeeperKeeps::aStack('b')->id()))->toBeTrue($keeper::class);
    }
});

it('leaves no session, grant, phone key or phone key\'s name of a forgotten stack in what the app is composed with', function (string $secret): void {
    WhatEveryKeeperKeeps::inTheAppOverStoresOfItsOwn();
    $forgetting = app(ForgetsAStack::class);
    $holding = array_find(WhatEveryKeeperKeeps::askedAsOne($forgetting), static fn(ForgetsAStack $keeper): bool => $keeper instanceof $secret);

    expect($holding)->toBeInstanceOf(ForgetsAStack::class);
    assert($holding instanceof ForgetsAStack);
    WhatEveryKeeperKeeps::somethingFor($holding, WhatEveryKeeperKeeps::aStack('a'));
    expect($holding->keepsAnythingOf(WhatEveryKeeperKeeps::aStack('a')->id()))->toBeTrue();

    $forgetting->forgetTheStack(WhatEveryKeeperKeeps::aStack('a')->id());

    expect($holding->keepsAnythingOf(WhatEveryKeeperKeeps::aStack('a')->id()))->toBeFalse();
})->with([
    'the session' => SecureStorage::class,
    'the grant' => KeepingTheGrant::class,
    'the phone key' => HoldingAPhoneKey::class,
    'the phone key\'s name' => KeepingThePhoneKeysName::class,
]);

it('says it may still keep something where its store cannot be read', function (): void {
    $stack = WhatEveryKeeperKeeps::aStack('a')->id();

    foreach ([
        'the platform pairings' => new PlatformStacks(APlatformStore::refusing()),
        'the platform keychain' => new PlatformKeychain(APlatformStore::refusing()),
        'the keychain letting go of artwork' => new ArtworkGoesWithTheSession(new PlatformKeychain(APlatformStore::refusing()), ArtworkHeldInMemory::working()),
        'the platform words' => new PlatformStandings(APlatformStore::refusing()),
        'the platform work' => new PlatformWorkLeftRunning(APlatformStore::refusing(), new PlatformStacks(APlatformStore::refusing())),
        'the platform place' => new PlatformWhereTheOperatorWas(APlatformStore::refusing()),
        'the platform place heard to' => new PlatformWhereHearingLeftOff(APlatformStore::refusing(), new PlatformStacks(APlatformStore::refusing())),
        'the platform grants' => new PlatformGrants(APlatformStore::refusing()),
        'the platform phone key names' => new PlatformPhoneKeysNames(APlatformStore::refusing()),
        'readings whose keys cannot be read' => new KeepingTheLastReading(ASealInMemory::thatWillNotOpen(), ReadingsInMemory::empty()),
        'the upkeep whose keys cannot be read' => new KeepingTheLastUpkeep(ASealInMemory::thatWillNotOpen(), ReadingsInMemory::empty()),
        'what it runs, whose keys cannot be read' => new KeepingWhatItRuns(ASealInMemory::thatWillNotOpen(), ReadingsInMemory::empty(), FrozenClock::at(Instant::atEpochSeconds(0))),
        'what the household asked for, whose keys cannot be read' => new KeepingWhatWasAsked(ASealInMemory::thatWillNotOpen(), ReadingsInMemory::empty(), FrozenClock::at(Instant::atEpochSeconds(0))),
        'the languages members chose, whose keys cannot be read' => new KeepingTheirLanguages(ASealInMemory::thatWillNotOpen(), ReadingsInMemory::empty(), FrozenClock::at(Instant::atEpochSeconds(0)), AKeychainInMemory::working()),
        'news whose keys cannot be read' => whatNoticesNewsOver(ASealInMemory::thatWillNotOpen()),
    ] as $which => $keeper) {
        expect($keeper->keepsAnythingOf($stack))->toBeTrue($which);
    }
});

it('stands in for a stack with a declaration the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('CapabilitiesEnvelope', WhatEveryKeeperKeeps::aDeclarationOfNothing()))->toBe([]);
});
