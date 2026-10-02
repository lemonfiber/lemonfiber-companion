<?php

declare(strict_types=1);

namespace Modules\News\Internal;

use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\Sealed;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealStanding;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Unsealed;

/**
 * What is kept about what is new on a stack, read, written and forgotten, sealed on the way in and opened on the way out.
 *
 * What does not read is read as nothing kept, which marks every kind and has
 * every kind start again from what is current.
 */
final readonly class NewsOfAStack
{
    public function __construct(private Sealed $seal, private NewsKept $kept, private Clock $clock) {}

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

    /** Keep this for the stack; whether it was kept. */
    public function keep(StackId $stack, WhatIsKeptOfNews $news): bool
    {
        return $this->seal->seal(TheNewsAsKept::written($news))->either(
            sealed: fn(SealedPayload $payload): WhetherItWasKept => $this->kept->keep($this->seal->stack($stack), $payload, Shape::current(), $this->clock->now())->either(
                down: static fn(): WhetherItWasKept => new WhetherItWasKept(was: true),
                notKept: static fn(): WhetherItWasKept => new WhetherItWasKept(was: false),
            ),
            refused: static fn(): WhetherItWasKept => new WhetherItWasKept(was: false),
        )->was;
    }

    /** Forget what is kept for the stack. */
    public function forget(StackId $stack): Forgotten
    {
        return $this->kept->forget($this->seal->stack($stack));
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
