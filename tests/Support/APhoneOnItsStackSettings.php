<?php

declare(strict_types=1);

namespace Tests\Support;

use Bootstrap\Composition\EveryKeeperOfAStack;
use Modules\Connection\Api\RemovingAStack;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Whose;
use Modules\News\Api\MarkingAsNew;
use Modules\News\Internal\NewsOfAStack;
use Modules\Operator\Internal\Screens\ThisStackOnThisPhone;
use Modules\Vault\Api\PlatformKeychain;
use Modules\Vault\Api\PlatformStacks;
use Tests\Support\Fakes\APlatformStore;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\NewsKeptInMemory;
use Tests\Support\Fakes\RemovalsUnderWayInMemory;

/** A phone paired with these, its pairings and sessions in one platform store. */
final readonly class APhoneOnItsStackSettings
{
    public APlatformStore $store;

    public PlatformStacks $stacks;

    public PlatformKeychain $keychain;

    /** Which kinds each stack marks as new, kept as the phone keeps them. */
    public MarkingAsNew $marking;

    public function __construct(Stack ...$paired)
    {
        $this->store = APlatformStore::working();
        $this->stacks = new PlatformStacks($this->store);
        $this->keychain = new PlatformKeychain($this->store);
        $this->marking = new MarkingAsNew(new NewsOfAStack(ASealInMemory::working(), NewsKeptInMemory::empty(), FrozenClock::at(Instant::atEpochSeconds(0))));

        foreach ($paired as $stack) {
            $this->stacks->remember($stack);
            $this->keychain->keep($stack->id(), Session::of('a-session'), Whose::theOperator());
        }
    }

    /** The Stack settings page of this stack, removing through a journal that writes, or one that will not. */
    public function pageOf(Stack $stack, bool $journalWrites = true): ThisStackOnThisPhone
    {
        $journal = $journalWrites ? new PlatformStacks($this->store) : RemovalsUnderWayInMemory::refusing();
        $screen = new ThisStackOnThisPhone(
            AroundThePhone::holding($this->stacks, storage: $this->keychain),
            new RemovingAStack($journal, new EveryKeeperOfAStack($this->stacks, $this->keychain)),
            $this->marking,
        );
        $screen->setParams(['stack' => $stack->id()->stored()]);

        return $screen;
    }
}
