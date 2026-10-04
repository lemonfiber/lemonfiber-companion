<?php

declare(strict_types=1);

use Bootstrap\Composition\EveryKeeperOfAStack;
use Modules\Health\Api\KeepingTheLastReading;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\ForgetsAStack;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfWork;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\WhatStoppedMoving;
use Modules\Kernel\Api\WhichTab;
use Modules\Kernel\Api\Whose;
use Modules\News\Api\AnItem;
use Modules\News\Api\KindOfNews;
use Modules\News\Api\Noticing;
use Modules\News\Api\TheItems;
use Modules\News\Internal\NewsOfAStack;
use Modules\Services\Api\KeepingWhatItRuns;
use Modules\Updates\Api\KeepingTheLastUpkeep;
use Modules\Vault\Api\PlatformKeychain;
use Modules\Vault\Api\PlatformStacks;
use Modules\Vault\Api\PlatformStandings;
use Modules\Vault\Api\PlatformWhereTheOperatorWas;
use Modules\Vault\Api\PlatformWorkLeftRunning;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\APlatformStore;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\NewsKeptInMemory;
use Tests\Support\Fakes\ReadingsInMemory;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\Fakes\StandingsInMemory;
use Tests\Support\Fakes\WhereTheOperatorWasInMemory;
use Tests\Support\Fakes\WorkLeftRunningInMemory;
use Tests\Support\WhatIsKeptOfServices;
use Tests\Support\WhatIsKeptOfUpdates;
use Tests\Support\WhatThePhoneKeeps;

// The ForgetsAStack contract, run against every keeper of a stack and against
// all of them together: what it keeps for one stack goes, what it keeps for
// any other stays, and it says afterwards that it keeps nothing of the one.

/** A stack a keeper holds something for. Named for this file. */
function aStackSomethingIsKeptFor(string $seed): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST))),
        StackName::of(sprintf('Kept %s', $seed)),
        Address::of('https://192.168.1.46'),
        Fingerprint::of(str_repeat($seed, Fingerprint::CHARACTERS)),
    );
}

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
        'the readings' => new KeepingTheLastReading(ASealInMemory::working(), ReadingsInMemory::empty()),
        'the upkeep' => new KeepingTheLastUpkeep(ASealInMemory::working(), ReadingsInMemory::empty()),
        'what it runs' => WhatThePhoneKeeps::noListingYet(),
        'what is new' => whatNoticesNewsOver(ASealInMemory::working()),
        'the platform words' => new PlatformStandings(APlatformStore::working()),
        'the fake words' => StandingsInMemory::working(),
        'the platform work' => new PlatformWorkLeftRunning($store, new PlatformStacks($store)),
        'the fake work' => WorkLeftRunningInMemory::working(),
        'the platform place' => new PlatformWhereTheOperatorWas(APlatformStore::working()),
        'the fake place' => WhereTheOperatorWasInMemory::nowhere(),
    ];

    foreach ([...$keepers, $pairings, $words] as $keeper) {
        foreach (['a', 'b'] as $seed) {
            keepSomethingFor($keeper, aStackSomethingIsKeptFor($seed));
        }
    }

    return [...$keepers, 'every keeper together' => new EveryKeeperOfAStack($pairings, $words)];
}

/** What notices what is new on a stack, over this seal and a store of its own. */
function whatNoticesNewsOver(ASealInMemory $seal): Noticing
{
    return new Noticing(new NewsOfAStack($seal, NewsKeptInMemory::empty(), FrozenClock::at(Instant::atEpochSeconds(0))));
}

/** Have a keeper of readings hold the newest reading of its kind for this stack. */
function keepAReadingFor(KeepingTheLastReading|KeepingTheLastUpkeep|KeepingWhatItRuns $keeper, Stack $stack): Noted
{
    return match (true) {
        $keeper instanceof KeepingTheLastReading => $keeper->keep($stack->id(), TheHealthSummary::of(HowItStands::Healthy, 0, '', WhatStoppedMoving::nothing()), Instant::atEpochSeconds(1)),
        $keeper instanceof KeepingTheLastUpkeep => $keeper->keep($stack->id(), WhatIsKeptOfUpdates::aReadingOfAStackThatIsCurrent(), Instant::atEpochSeconds(1)),
        $keeper instanceof KeepingWhatItRuns => $keeper->keep($stack->id(), WhatIsKeptOfServices::aListingOfNothing()),
    };
}

/** Have a keeper hold something for this stack, through its own port. */
function keepSomethingFor(ForgetsAStack $keeper, Stack $stack): void
{
    if ($keeper instanceof PlatformStacks || $keeper instanceof StacksInMemory) {
        $keeper->remember($stack);

        return;
    }

    if ($keeper instanceof PlatformKeychain || $keeper instanceof AKeychainInMemory) {
        $keeper->keep($stack->id(), Session::of('a-session'), Whose::theOperator());

        return;
    }

    if ($keeper instanceof KeepingTheLastReading || $keeper instanceof KeepingTheLastUpkeep || $keeper instanceof KeepingWhatItRuns) {
        keepAReadingFor($keeper, $stack);

        return;
    }

    if ($keeper instanceof Noticing) {
        $keeper->whatIsNewIn($stack->id(), TheItems::of(KindOfNews::Request, AnItem::aRequest(1)));

        return;
    }

    if ($keeper instanceof PlatformStandings || $keeper instanceof StandingsInMemory) {
        $keeper->remember($stack->id(), HowItStands::Healthy, Instant::atEpochSeconds(1));

        return;
    }

    if ($keeper instanceof PlatformWhereTheOperatorWas || $keeper instanceof WhereTheOperatorWasInMemory) {
        $keeper->wasOn($stack->id(), WhichTab::Services);

        return;
    }

    if ($keeper instanceof PlatformWorkLeftRunning || $keeper instanceof WorkLeftRunningInMemory) {
        $keeper->remember($stack->id(), KindOfWork::Walkthrough, Job::named('a-walk'));

        return;
    }

    throw new LogicException(sprintf('No way to have %s keep something.', $keeper::class));
}

it('keeps something for each stack it was given', function (): void {
    foreach (everyKeeperHoldingTwoStacks() as $which => $keeper) {
        expect($keeper->keepsAnythingOf(aStackSomethingIsKeptFor('a')->id()))->toBeTrue($which)
            ->and($keeper->keepsAnythingOf(aStackSomethingIsKeptFor('b')->id()))->toBeTrue($which);
    }
});

it('lets go of everything it keeps for one stack, and of nothing it keeps for another', function (): void {
    foreach (everyKeeperHoldingTwoStacks() as $which => $keeper) {
        expect($keeper->forgetTheStack(aStackSomethingIsKeptFor('a')->id())->howMany())->toBeGreaterThan(0, $which)
            ->and($keeper->keepsAnythingOf(aStackSomethingIsKeptFor('a')->id()))->toBeFalse($which)
            ->and($keeper->keepsAnythingOf(aStackSomethingIsKeptFor('b')->id()))->toBeTrue($which)
            ->and($keeper->forgetTheStack(aStackSomethingIsKeptFor('a')->id())->howMany())->toBe(0, $which);
    }
});

it('says it may still keep something where its store cannot be read', function (): void {
    $stack = aStackSomethingIsKeptFor('a')->id();

    foreach ([
        'the platform pairings' => new PlatformStacks(APlatformStore::refusing()),
        'the platform keychain' => new PlatformKeychain(APlatformStore::refusing()),
        'the platform words' => new PlatformStandings(APlatformStore::refusing()),
        'the platform work' => new PlatformWorkLeftRunning(APlatformStore::refusing(), new PlatformStacks(APlatformStore::refusing())),
        'the platform place' => new PlatformWhereTheOperatorWas(APlatformStore::refusing()),
        'readings whose keys cannot be read' => new KeepingTheLastReading(ASealInMemory::thatWillNotOpen(), ReadingsInMemory::empty()),
        'the upkeep whose keys cannot be read' => new KeepingTheLastUpkeep(ASealInMemory::thatWillNotOpen(), ReadingsInMemory::empty()),
        'what it runs, whose keys cannot be read' => new KeepingWhatItRuns(ASealInMemory::thatWillNotOpen(), ReadingsInMemory::empty(), FrozenClock::at(Instant::atEpochSeconds(0))),
        'news whose keys cannot be read' => whatNoticesNewsOver(ASealInMemory::thatWillNotOpen()),
    ] as $which => $keeper) {
        expect($keeper->keepsAnythingOf($stack))->toBeTrue($which);
    }
});
