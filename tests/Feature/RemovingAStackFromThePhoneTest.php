<?php

declare(strict_types=1);

use Bootstrap\Composition\EveryKeeperOfAStack;
use Modules\Connection\Api\RemovingAStack;
use Modules\Connection\Api\WhatBecameOfRemoving;
use Modules\Health\Api\KeepingTheLastReading;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\ForgetsAStack;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfWork;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\WhatStoppedMoving;
use Modules\Kernel\Api\Whose;
use Modules\Vault\Api\PlatformKeychain;
use Modules\Vault\Api\PlatformStacks;
use Modules\Vault\Api\PlatformStandings;
use Modules\Vault\Api\PlatformWorkLeftRunning;
use Tests\Support\Fakes\APlatformStore;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\HealthReadingsInMemory;
use Tests\Support\Fakes\ReadingsKeptForInMemory;
use Tests\Support\Fakes\RemovalsUnderWayInMemory;
use Tests\Support\Fakes\StacksInMemory;

// Remove from phone, over the stores the phone keeps a stack in: the secure
// store behind the pairing, the session and the markers, and the store of
// readings. A removal is one act however many steps it takes, so it is cut
// after every step, finished as the app next opens, and never leaves the
// stack in a list in between.

/** A stack this phone is paired with. Named for this file. */
function aStackOnThePhone(string $seed): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST))),
        StackName::of(sprintf('Stack %s', $seed)),
        Address::of('https://192.168.1.45'),
        Fingerprint::of(str_repeat($seed, Fingerprint::CHARACTERS)),
    );
}

/** A phone holding everything it keeps for two stacks, the one to remove and one to keep. */
final readonly class APhoneHoldingTwoStacks
{
    public APlatformStore $store;

    public HealthReadingsInMemory $readings;

    public PlatformStacks $stacks;

    /** @var list<ForgetsAStack> */
    public array $keepers;

    public function __construct()
    {
        $this->store = APlatformStore::working();
        $this->readings = HealthReadingsInMemory::empty();
        $this->stacks = new PlatformStacks($this->store);

        $keychain = new PlatformKeychain($this->store);
        $keeping = new KeepingTheLastReading(ASealInMemory::working(), $this->readings, ReadingsKeptForInMemory::standard(), FrozenClock::at(Instant::atEpochSeconds(0)));
        $standings = new PlatformStandings($this->store);
        $left = new PlatformWorkLeftRunning($this->store, $this->stacks);

        foreach (['a', 'b'] as $seed) {
            $stack = aStackOnThePhone($seed);
            $this->stacks->remember($stack);
            $keychain->keep($stack->id(), Session::of(sprintf('a-session-for-%s', $seed)), Whose::theOperator());
            $keeping->keep($stack->id(), TheHealthSummary::of(HowItStands::Healthy, 0, '', WhatStoppedMoving::nothing()), Instant::atEpochSeconds(1));
            $standings->remember($stack->id(), HowItStands::Healthy, Instant::atEpochSeconds(1));
            $left->remember($stack->id(), KindOfWork::Walkthrough, Job::named(sprintf('a-walk-on-%s', $seed)));
        }

        // In the order the composition root registers them: the pairing first.
        $this->keepers = [$this->stacks, $keychain, $keeping, $standings, $left];
    }

    public function removing(): RemovingAStack
    {
        return new RemovingAStack(new PlatformStacks($this->store), new EveryKeeperOfAStack(...$this->keepers));
    }

    /**
     * Which keepers still keep anything of this stack, by position.
     *
     * @return list<int>
     */
    public function stillKeeping(StackId $stack): array
    {
        return array_keys(array_filter($this->keepers, static fn(ForgetsAStack $keeper): bool => $keeper->keepsAnythingOf($stack)));
    }
}

it('lets go of the stack\'s pairing, session, readings and markers in one act, and of nothing of any other', function (): void {
    $phone = new APhoneHoldingTwoStacks();

    expect($phone->removing()->remove(aStackOnThePhone('a')->id()))->toBe(WhatBecameOfRemoving::Removed)
        ->and($phone->stillKeeping(aStackOnThePhone('a')->id()))->toBe([])
        ->and($phone->stillKeeping(aStackOnThePhone('b')->id()))->toBe([0, 1, 2, 3, 4])
        ->and(new PlatformStacks($phone->store)->underWay()->isEmpty())->toBeTrue()
        ->and($phone->stacks->configured()->knows(aStackOnThePhone('b')->id()))->toBeTrue();
});

it('finishes a removal cut short after any step when the app next opens, and never lists the stack in between', function (int $stepsTaken): void {
    $phone = new APhoneHoldingTwoStacks();
    $removed = aStackOnThePhone('a')->id();

    // The removal as far as it got before the app was stopped: written down,
    // and this many keepers asked.
    new PlatformStacks($phone->store)->begin($removed);

    foreach (array_slice($phone->keepers, 0, $stepsTaken) as $keeper) {
        $keeper->forgetTheStack($removed);
    }

    $listedWhileCut = $phone->stacks->configured()->knows($removed);
    $left = $phone->removing()->finishWhatWasLeft();

    expect($listedWhileCut)->toBeFalse()
        ->and($left->isEmpty())->toBeTrue()
        ->and($phone->stillKeeping($removed))->toBe([])
        ->and(new PlatformStacks($phone->store)->underWay()->isEmpty())->toBeTrue()
        ->and($phone->stillKeeping(aStackOnThePhone('b')->id()))->toBe([0, 1, 2, 3, 4]);
})->with([
    'before anything was let go of' => [0],
    'after the pairing' => [1],
    'after the session' => [2],
    'after the readings' => [3],
    'after the words' => [4],
    'after the work left running, before it was struck off' => [5],
]);

it('touches nothing where the removal cannot be written down', function (): void {
    $phone = new APhoneHoldingTwoStacks();
    $removing = new RemovingAStack(RemovalsUnderWayInMemory::refusing(), new EveryKeeperOfAStack(...$phone->keepers));

    expect($removing->remove(aStackOnThePhone('a')->id()))->toBe(WhatBecameOfRemoving::Refused)
        ->and($phone->stillKeeping(aStackOnThePhone('a')->id()))->toBe([0, 1, 2, 3, 4])
        ->and($phone->stacks->configured()->knows(aStackOnThePhone('a')->id()))->toBeTrue();
});

it('keeps a removal a keeper would not finish, as it happens and at the next opening, and finishes it once the keeper lets go', function (): void {
    $removed = aStackOnThePhone('a');
    $holdingOn = StacksInMemory::holding($removed)->thenHoldingOn();
    $journal = RemovalsUnderWayInMemory::working();

    $went = new RemovingAStack($journal, new EveryKeeperOfAStack($holdingOn))->remove($removed->id());
    $still = new RemovingAStack($journal, new EveryKeeperOfAStack($holdingOn))->finishWhatWasLeft();
    $letsGo = StacksInMemory::holding($removed);
    $left = new RemovingAStack($journal, new EveryKeeperOfAStack($letsGo))->finishWhatWasLeft();

    expect($went)->toBe(WhatBecameOfRemoving::Finishing)
        ->and($still->holds($removed->id()))->toBeTrue()
        ->and($holdingOn->keepsAnythingOf($removed->id()))->toBeTrue()
        ->and($left->isEmpty())->toBeTrue()
        ->and($letsGo->keepsAnythingOf($removed->id()))->toBeFalse()
        ->and($journal->underWay()->isEmpty())->toBeTrue();
});

it('takes a stack back where it is paired again before its removal finished', function (): void {
    $phone = new APhoneHoldingTwoStacks();
    $removed = aStackOnThePhone('a');

    new PlatformStacks($phone->store)->begin($removed->id());
    $phone->stacks->remember($removed);

    expect($phone->stacks->configured()->knows($removed->id()))->toBeTrue()
        ->and(new PlatformStacks($phone->store)->underWay()->isEmpty())->toBeTrue();
});
