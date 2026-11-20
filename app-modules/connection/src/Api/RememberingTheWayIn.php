<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Modules\Connection\Internal\TheWayInAsKept;
use Modules\Connection\Internal\WaysInKept;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\ForgetsAStack;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\Sealed;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealStanding;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Unsealed;

/**
 * The way that last let a member in on each house, kept on the phone so its sign-in leads with it.
 *
 * Nothing names whose sign-in it was: before anybody signs in the phone knows
 * the house and nothing else, so the way is the house's, and the password
 * until either way has let somebody in.
 */
final readonly class RememberingTheWayIn implements ForgetsAStack
{
    public function __construct(private Sealed $seal, private WaysInKept $kept, private Clock $clock) {}

    /** The way to lead with on this house. */
    public function ledWithOn(StackId $stack): TheWayIn
    {
        return $this->kept->found($this->seal->stack($stack))->either(
            found: fn(SealedPayload $payload, Shape $shape): TheWayIn => $this->seal->open($payload)->either(
                opened: static fn(Unsealed $value): TheWayIn => TheWayInAsKept::read($shape, $value),
                unreadable: static fn(): TheWayIn => TheWayIn::Password,
            ),
            none: static fn(): TheWayIn => TheWayIn::Password,
        );
    }

    /** Note that this way let a member in on this house. */
    public function letIn(StackId $stack, TheWayIn $way): Noted
    {
        return $this->seal->seal(TheWayInAsKept::written($way))->either(
            sealed: fn(SealedPayload $payload): Noted => $this->kept->keep($this->seal->stack($stack), $payload, Shape::current(), $this->clock->now()),
            refused: static fn(): Noted => Noted::notKept(),
        );
    }

    public function forgetTheStack(StackId $stack): Forgotten
    {
        return $this->kept->forget($this->seal->stack($stack));
    }

    public function keepsAnythingOf(StackId $stack): bool
    {
        if ($this->seal->standing() === SealStanding::Unavailable) {
            return true;
        }

        return $this->kept->found($this->seal->stack($stack))->holdsSomething();
    }
}
