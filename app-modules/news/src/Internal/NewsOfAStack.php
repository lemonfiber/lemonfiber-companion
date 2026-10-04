<?php

declare(strict_types=1);

namespace Modules\News\Internal;

use Closure;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\Sealed;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealStanding;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\TheNewestNamed;
use Modules\Kernel\Api\Unsealed;
use Modules\News\Api\HowMuchIsNew;

/**
 * What is kept about what is new on a stack, read, written and forgotten, sealed on the way in and opened on the way out.
 *
 * Beside it, what each stack last named as newest and how much of it was new,
 * held in memory by {@see WhatEachStackLastNamed} and let go of whenever what
 * is kept changes or is forgotten.
 *
 * What does not read is read as nothing kept, which marks every kind and has
 * every kind start again from what is current.
 */
final readonly class NewsOfAStack
{
    public function __construct(
        private Sealed $seal,
        private NewsKept $kept,
        private Clock $clock,
        private WhatEachStackLastNamed $lastNamed,
    ) {}

    /** What is kept for the stack, or nothing where none is or it does not read. */
    public function of(StackId $stack): WhatIsKeptOfNews
    {
        return $this->kept->found($this->seal->stack($stack))->either(
            found: fn(SealedPayload $payload, Shape $shape): WhatIsKeptOfNews => $this->seal->open($payload)->either(
                opened: static fn(Unsealed $value): WhatIsKeptOfNews => TheNewsAsKept::read($shape, $value),
                unreadable: static fn(): WhatIsKeptOfNews => WhatIsKeptOfNews::nothing(),
            ),
            none: static fn(): WhatIsKeptOfNews => WhatIsKeptOfNews::nothing(),
            unreadable: static fn(): WhatIsKeptOfNews => WhatIsKeptOfNews::nothing(),
        );
    }

    /**
     * Keep this for the stack; whether it was kept.
     *
     * What was counted new on the stack is let go of either way, since what is
     * kept may have changed under it.
     */
    public function keep(StackId $stack, WhatIsKeptOfNews $news): bool
    {
        $this->lastNamed->changed($stack);

        return $this->seal->seal(TheNewsAsKept::written($news))->either(
            sealed: fn(SealedPayload $payload): WhetherItWasKept => $this->kept->keep($this->seal->stack($stack), $payload, Shape::current(), $this->clock->now())->either(
                down: static fn(): WhetherItWasKept => new WhetherItWasKept(was: true),
                notKept: static fn(): WhetherItWasKept => new WhetherItWasKept(was: false),
            ),
            refused: static fn(): WhetherItWasKept => new WhetherItWasKept(was: false),
        )->was;
    }

    /** The stack named this as newest, and this much of it is new, held for the screens that open after. */
    public function heard(StackId $stack, TheNewestNamed $newest, HowMuchIsNew $counted): void
    {
        $this->lastNamed->named($stack, $newest, $counted);
    }

    /**
     * How much was new when the stack last named the newest, counted again where what is kept changed since.
     *
     * @param Closure(TheNewestNamed): HowMuchIsNew $counting
     */
    public function lastCounted(StackId $stack, Closure $counting): HowMuchIsNew
    {
        return $this->lastNamed->lastCounted($stack, $counting);
    }

    /** Forget what is kept for the stack, and what it last named. */
    public function forget(StackId $stack): Forgotten
    {
        $this->lastNamed->forget($stack);

        return $this->kept->forget($this->seal->stack($stack));
    }

    /** Forget what is kept for every stack, and what each last named. */
    public function forgetEverything(): Forgotten
    {
        $this->lastNamed->forgetEverything();

        return $this->kept->forgetEverything();
    }

    /**
     * Whether anything is kept for the stack.
     *
     * Where nothing can be sealed, nothing could be read to say, so the answer
     * is yes: a removal that asks is told there may be something to forget.
     */
    public function keepsAnythingOf(StackId $stack): bool
    {
        if ($this->seal->standing() === SealStanding::Unavailable) {
            return true;
        }

        return $this->kept->found($this->seal->stack($stack))->either(
            found: static fn(): WhetherItWasKept => new WhetherItWasKept(was: true),
            none: static fn(): WhetherItWasKept => new WhetherItWasKept(was: false),
            unreadable: static fn(): WhetherItWasKept => new WhetherItWasKept(was: true),
        )->was;
    }
}
