<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_filter;
use function array_keys;

use Bootstrap\Composition\EveryKeeperOfAStack;
use Modules\Connection\Api\RemovingAStack;
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

use function sprintf;
use function str_repeat;

use Tests\Support\Fakes\APlatformStore;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\HealthReadingsInMemory;
use Tests\Support\Fakes\ReadingsKeptForInMemory;

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
            $stack = self::aStack($seed);
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

    /** A stack this phone is paired with, one of the two told apart by its seed. */
    public static function aStack(string $seed): Stack
    {
        return Stack::of(
            StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST))),
            StackName::of(sprintf('Stack %s', $seed)),
            Address::of('https://192.168.1.45'),
            Fingerprint::of(str_repeat($seed, Fingerprint::CHARACTERS)),
        );
    }
}
