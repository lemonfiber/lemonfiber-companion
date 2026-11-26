<?php

declare(strict_types=1);

namespace Modules\Health\Api;

use Modules\Health\Internal\MissingAlertsKept;
use Modules\Health\Internal\WhetherAMarkIsKept;
use Modules\Kernel\Api\ForgetsAStack;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\Sealed;
use Modules\Kernel\Api\SealStanding;
use Modules\Kernel\Api\StackId;

/**
 * The stacks a resumed stream heard from that may not have handed back every alert they raised, until the operator opens one.
 *
 * Marked where a stack said its replay was `partial`, or said nothing of it,
 * on a wake or on a stream resumed while the app is open, and kept on the
 * phone, named by the stack's keyed hash, so a mark made while the app was
 * away is there when it opens. Opening the stack's health screen
 * sees the mark, and seeing it clears it.
 */
final readonly class AlertsThatMayBeMissing implements ForgetsAStack
{
    public function __construct(private Sealed $seal, private MissingAlertsKept $kept) {}

    /** Mark this stack as maybe missing alerts, from that moment. */
    public function mayBeMissingFrom(StackId $stack, Instant $at): Noted
    {
        return $this->kept->mark($this->seal->stack($stack), $at);
    }

    /** Whether this stack was marked, clearing the mark: what opening its health screen does. */
    public function seenOn(StackId $stack): bool
    {
        return $this->kept->forget($this->seal->stack($stack))->howMany() > 0;
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

        return $this->kept->whenMarked($this->seal->stack($stack))->either(
            down: static fn(): WhetherAMarkIsKept => new WhetherAMarkIsKept(is: true),
            notKept: static fn(): WhetherAMarkIsKept => new WhetherAMarkIsKept(is: false),
        )->is;
    }
}
