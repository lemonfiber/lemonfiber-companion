<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ReadingNews;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\TheNewsOfAStack;
use Modules\Kernel\Api\WhatTheStackListed;
use Modules\Kernel\Api\WhatWasFoundOfTheNews;

/**
 * Stacks where a test says what each lists that could be new, and which count being asked.
 *
 * {@see AStackWithAFrontDoor}'s sibling, for every stack at once: What's new reads
 * each stack it shows, so a test says what each answers and changes it between
 * frames, which is how something becomes new.
 *
 * Not `readonly`: what is listed changes, and what was asked is written when the
 * asking happens.
 */
final class AStackThatListsWhatIsNew implements ReadingNews
{
    /** @var array<string, WhatWasFoundOfTheNews> what each stack answers, by its stored identifier */
    private array $answers = [];

    /** @var array<string, int> how many times each stack was asked */
    private array $askings = [];

    /** Stacks that each list nothing until a test says otherwise. */
    public static function listingNothing(): self
    {
        return new self();
    }

    /** The stack lists this from now on. */
    public function lists(StackId $stack, TheNewsOfAStack $news): self
    {
        $this->answers[$stack->stored()] = WhatWasFoundOfTheNews::found($news);

        return $this;
    }

    /** The stack cannot be reached from now on, for the reason given. */
    public function cannotBeReached(StackId $stack, Obstacle $why): self
    {
        $this->answers[$stack->stored()] = WhatWasFoundOfTheNews::met($why);

        return $this;
    }

    /** How many times the stack was asked, which catches a frame asking twice. */
    public function askings(StackId $stack): int
    {
        return $this->askings[$stack->stored()] ?? 0;
    }

    public function newsOn(Stack $stack, Session $session): WhatWasFoundOfTheNews
    {
        $which = $stack->id()->stored();
        $this->askings[$which] = $this->askings($stack->id()) + 1;

        return array_key_exists($which, $this->answers)
            ? $this->answers[$which]
            : WhatWasFoundOfTheNews::found(new TheNewsOfAStack(WhatTheStackListed::these(), WhatTheStackListed::these(), WhatTheStackListed::these()));
    }
}
