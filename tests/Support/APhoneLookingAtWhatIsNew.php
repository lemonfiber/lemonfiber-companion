<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Whose;
use Modules\News\Api\MarkingAsNew;
use Modules\News\Api\Noticing;
use Modules\News\Internal\NewsOfAStack;
use Modules\News\Internal\WhatEachStackLastNamed;
use Modules\Operator\Internal\Screens\WhatIsNewOnEveryStack;
use Modules\Vault\Api\PlatformKeychain;
use Modules\Vault\Api\PlatformStacks;
use Tests\Support\Fakes\APlatformStore;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\AStackThatListsWhatIsNew;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\NewsKeptInMemory;
use Tests\Support\Fakes\StandingsInMemory;

/**
 * A phone paired with these stacks, signed in to each, opening What's new.
 *
 * One news store for the phone, so what one screen saw is what the next one
 * reads, and one set of stacks a test tells what to list.
 */
final readonly class APhoneLookingAtWhatIsNew
{
    /** When every frame here is drawn. */
    public const int NOW = 1_759_400_000;

    public PlatformStacks $stacks;

    public PlatformKeychain $keychain;

    public AStackThatListsWhatIsNew $listing;

    public StandingsInMemory $standings;

    public Noticing $noticing;

    public MarkingAsNew $marking;

    public function __construct(Stack ...$paired)
    {
        $store = APlatformStore::working();
        $this->stacks = new PlatformStacks($store);
        $this->keychain = new PlatformKeychain($store);
        $this->listing = AStackThatListsWhatIsNew::listingNothing();
        $this->standings = StandingsInMemory::working();
        $news = new NewsOfAStack(ASealInMemory::working(), NewsKeptInMemory::empty(), FrozenClock::at(Instant::atEpochSeconds(self::NOW)), new WhatEachStackLastNamed());
        $this->noticing = new Noticing($news);
        $this->marking = new MarkingAsNew($news);

        foreach ($paired as $stack) {
            $this->stacks->remember($stack);
            $this->keychain->keep($stack->id(), Session::of('a-session'), Whose::theOperator());
        }
    }

    /** What's new, opened afresh. */
    public function opens(): WhatIsNewOnEveryStack
    {
        $clock = FrozenClock::at(Instant::atEpochSeconds(self::NOW));

        return new WhatIsNewOnEveryStack(
            $this->stacks,
            $this->keychain,
            $this->listing,
            $this->noticing,
            $this->standings,
            $clock,
        );
    }
}
