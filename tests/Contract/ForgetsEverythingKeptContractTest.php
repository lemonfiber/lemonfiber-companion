<?php

declare(strict_types=1);

use Bootstrap\Composition\EveryStoreThePhoneKeeps;
use Modules\Connection\Internal\Store\SettingsInTheDatabase;
use Modules\Health\Internal\HealthReadingsKept;
use Modules\Health\Internal\Store\HealthReadingsInTheDatabase;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\ForgetsEverythingKept;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfWork;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\Showing;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Vault\Api\PlatformStacks;
use Modules\Vault\Api\PlatformStandings;
use Modules\Vault\Api\PlatformWorkLeftRunning;
use Tests\Support\AKeptDatabase;
use Tests\Support\Fakes\APlatformStore;
use Tests\Support\Fakes\ConnectionSettingsInMemory;
use Tests\Support\Fakes\HealthReadingsInMemory;
use Tests\Support\Fakes\StandingsInMemory;
use Tests\Support\Fakes\WorkLeftRunningInMemory;

// The ForgetsEverythingKept contract, run against every store and against all
// of them together.
//
// A phone whose key has gone clears what it kept by asking this once, so the
// promise is the whole of what makes the clearing true: after it, a store
// holds nothing for any stack, and it says how much it let go of. A store that
// forgot one stack and not another would leave a reading behind that the new
// key can never open.

/**
 * Each store holding one reading for each of two stacks, and every store together.
 *
 * The stores that hold readings are asked through their own port to keep
 * them, so what is forgotten is what an owner put there. Every store together
 * is two in memory, because the app has one database and a second adapter
 * over it would be the first one again.
 *
 * @return array<string, array{ForgetsEverythingKept, list<HealthReadingsKept>}>
 */
function everyStoreHoldingTwoReadings(): array
{
    $adapter = new HealthReadingsInTheDatabase(AKeptDatabase::migrated());
    $fake = HealthReadingsInMemory::empty();
    $oneOfTwo = HealthReadingsInMemory::empty();
    $theOther = HealthReadingsInMemory::empty();

    foreach ([$adapter, $fake, $oneOfTwo, $theOther] as $store) {
        foreach (['the-loft', 'the-shed'] as $stack) {
            $store->keep(SealedStack::of($stack), SealedPayload::of('sealed'), Shape::One, Instant::atEpochSeconds(1_790_000_000));
        }
    }

    return [
        'the adapter' => [$adapter, [$adapter]],
        'the fake' => [$fake, [$fake]],
        'every store together' => [new EveryStoreThePhoneKeeps($oneOfTwo, $theOther), [$oneOfTwo, $theOther]],
    ];
}

it('forgets everything every store it answers for keeps, and says how much that was', function (): void {
    foreach (everyStoreHoldingTwoReadings() as $which => [$forgetting, $stores]) {
        expect($forgetting->forgetEverything()->howMany())->toBe(2 * count($stores), $which);

        foreach ($stores as $store) {
            expect($store->forgetEverything()->howMany())->toBe(0, $which);
        }
    }
});

it('forgets nothing where nothing is kept, and says so', function (): void {
    $stores = [
        'the adapter' => new HealthReadingsInTheDatabase(AKeptDatabase::migrated()),
        'the fake' => HealthReadingsInMemory::empty(),
        'the settings adapter' => new SettingsInTheDatabase(AKeptDatabase::migrated()),
        'the settings fake' => ConnectionSettingsInMemory::empty(),
        'every store together' => new EveryStoreThePhoneKeeps(HealthReadingsInMemory::empty(), HealthReadingsInMemory::empty()),
        'no store at all' => new EveryStoreThePhoneKeeps(),
    ];

    foreach ($stores as $which => $forgetting) {
        expect($forgetting->forgetEverything()->howMany())->toBe(0, $which);
    }
});

it('forgets the settings a settings store keeps, and says how much that was', function (): void {
    // Each store is made when its turn comes: the database the adapters stand
    // on is one, and migrating it for the next adapter would empty the last.
    foreach ([
        'the settings adapter' => static fn(): SettingsInTheDatabase => new SettingsInTheDatabase(AKeptDatabase::migrated()),
        'the settings fake' => static fn(): ConnectionSettingsInMemory => ConnectionSettingsInMemory::empty(),
    ] as $which => $made) {
        $store = $made();
        $store->keep(SealedPayload::of('sealed'), Shape::One, Instant::atEpochSeconds(1_790_000_000));

        expect($store->forgetEverything()->howMany())->toBe(1, $which)
            ->and($store->forgetEverything()->howMany())->toBe(0, $which);
    }
});

/** A stack this phone is paired with, for the markers below. Named for this file. */
function aStackWithMarkers(string $seed): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST))),
        StackName::of(sprintf('Stack %s', $seed)),
        Address::of('https://192.168.1.44'),
        Fingerprint::of(str_repeat($seed, Fingerprint::CHARACTERS)),
    );
}

it('forgets the word every stack last said, and says how many', function (): void {
    foreach ([
        'the platform store' => static fn(): PlatformStandings => new PlatformStandings(APlatformStore::working()),
        'the fake' => static fn(): StandingsInMemory => StandingsInMemory::working(),
    ] as $which => $made) {
        $standings = $made();

        foreach (['a', 'b'] as $seed) {
            $standings->remember(aStackWithMarkers($seed)->id(), HowItStands::Healthy, Instant::atEpochSeconds(1_790_000_000));
        }

        expect($standings->forgetEverything()->howMany())->toBe(2, $which)
            ->and($standings->lastKnownOf(aStackWithMarkers('a')->id()))->toEqual(Showing::waiting())
            ->and($standings->forgetEverything()->howMany())->toBe(0, $which);
    }
});

it('forgets the work left running on every stack, and says how much', function (): void {
    $store = APlatformStore::working();
    $stacks = new PlatformStacks($store);

    foreach (['a', 'b'] as $seed) {
        $stacks->remember(aStackWithMarkers($seed));
    }

    foreach ([
        'the platform store' => new PlatformWorkLeftRunning($store, $stacks),
        'the fake' => WorkLeftRunningInMemory::working(),
    ] as $which => $left) {
        foreach (['a', 'b'] as $seed) {
            $left->remember(aStackWithMarkers($seed)->id(), KindOfWork::Walkthrough, Job::named(sprintf('walk-%s', $seed)));
        }

        expect($left->forgetEverything()->howMany())->toBe(2, $which)
            ->and($left->forgetEverything()->howMany())->toBe(0, $which);
    }
});
