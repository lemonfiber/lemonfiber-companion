<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Modules\Connection\Internal\NotifyingKept;
use Modules\Connection\Internal\TheChoicesAsKept;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\ForgetsAStack;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\KindOfNotification;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\Sealed;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealStanding;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Unsealed;
use Modules\Kernel\Api\WhatMayReachThePhone;

/**
 * What the operator lets reach the phone from each stack, as they chose on the notification page.
 *
 * Kept on the phone, sealed, one row a stack, and never sent to the stack.
 * Everything until the operator chooses, and a choice that does not read is
 * read as everything. Each change is kept as it is made and answers what is in
 * force after it, so a choice the phone could not keep is drawn as not made.
 */
final readonly class ChoosingWhatReachesThePhone implements ForgetsAStack
{
    public function __construct(private Sealed $seal, private NotifyingKept $kept, private Clock $clock) {}

    /** What reaches the phone from the stack. */
    public function forTheStack(StackId $stack): WhatMayReachThePhone
    {
        return $this->kept->found($this->seal->stack($stack))->either(
            found: fn(SealedPayload $payload, Shape $shape): WhatMayReachThePhone => $this->seal->open($payload)->either(
                opened: static fn(Unsealed $value): WhatMayReachThePhone => TheChoicesAsKept::read($shape, $value),
                unreadable: static fn(): WhatMayReachThePhone => WhatMayReachThePhone::everything(),
            ),
            none: static fn(): WhatMayReachThePhone => WhatMayReachThePhone::everything(),
        );
    }

    /** Switch this kind off for the stack, and answer what is in force after. */
    public function switchOff(StackId $stack, KindOfNotification $kind): WhatMayReachThePhone
    {
        return $this->keep($stack, $this->forTheStack($stack)->withOff($kind));
    }

    /** Switch this kind on for the stack, and answer what is in force after. */
    public function switchOn(StackId $stack, KindOfNotification $kind): WhatMayReachThePhone
    {
        return $this->keep($stack, $this->forTheStack($stack)->withOn($kind));
    }

    /** Tell problems from this severity up for the stack, and answer what is in force after. */
    public function tellProblemsFrom(StackId $stack, Severity $floor): WhatMayReachThePhone
    {
        return $this->keep($stack, $this->forTheStack($stack)->withFloor($floor));
    }

    public function forgetTheStack(StackId $stack): Forgotten
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

        return $this->kept->found($this->seal->stack($stack))->holdsSomething();
    }

    /** Keep this for the stack, and answer what is in force: this where it was kept, what was before where it was not. */
    private function keep(StackId $stack, WhatMayReachThePhone $chosen): WhatMayReachThePhone
    {
        return $this->seal->seal(TheChoicesAsKept::written($chosen))->either(
            sealed: fn(SealedPayload $payload): Noted => $this->kept->keep($this->seal->stack($stack), $payload, Shape::current(), $this->clock->now()),
            refused: static fn(): Noted => Noted::notKept(),
        )->either(
            down: static fn(): WhatMayReachThePhone => $chosen,
            notKept: fn(): WhatMayReachThePhone => $this->forTheStack($stack),
        );
    }
}
