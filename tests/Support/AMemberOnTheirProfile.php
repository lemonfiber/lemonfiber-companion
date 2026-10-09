<?php

declare(strict_types=1);

namespace Tests\Support;

use Bootstrap\Composition\EveryKeeperOfAStack;
use Modules\Connection\Api\RemovingAStack;
use Modules\Household\Internal\Screens\YourCornerOfTheHouse;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Whose;
use Modules\Vault\Api\PlatformKeychain;
use Modules\Vault\Api\PlatformStacks;
use Modules\Watching\Api\KeepingTheirLanguages;
use Tests\Support\Fakes\APlatformStore;
use Tests\Support\Fakes\RemovalsUnderWayInMemory;

/** A phone holding a member's session for each of these houses, its pairings and sessions in one platform store, and the languages each chose. */
final readonly class AMemberOnTheirProfile
{
    public APlatformStore $store;

    public PlatformStacks $stacks;

    public PlatformKeychain $keychain;

    public KeepingTheirLanguages $languages;

    public function __construct(Stack ...$paired)
    {
        $this->store = APlatformStore::working();
        $this->stacks = new PlatformStacks($this->store);
        $this->keychain = new PlatformKeychain($this->store);
        $this->languages = WhatThePhoneKeeps::noLanguagesYet($this->keychain);

        foreach ($paired as $stack) {
            $this->stacks->remember($stack);
            $this->keychain->keep($stack->id(), Session::of('a-session'), Whose::member('robin'));
        }
    }

    /** The Profile tab of this house, removing through a journal that writes, or one that will not. */
    public function profileOf(Stack $stack, bool $journalWrites = true): YourCornerOfTheHouse
    {
        $journal = $journalWrites ? new PlatformStacks($this->store) : RemovalsUnderWayInMemory::refusing();
        $screen = new YourCornerOfTheHouse(
            AroundThePhone::holding($this->stacks, storage: $this->keychain),
            $this->stacks,
            new RemovingAStack($journal, new EveryKeeperOfAStack($this->stacks, $this->keychain, $this->languages)),
            $this->languages,
        );
        $screen->setParams(['stack' => $stack->id()->stored()]);

        return $screen;
    }
}
