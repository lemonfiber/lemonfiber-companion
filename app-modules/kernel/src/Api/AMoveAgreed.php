<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A way of moving in the operator agreed to, having been shown what it would come to.
 *
 * {@see MovingIn::moveIn()} takes one of these and nothing else, and the only
 * way to make one is from an answer the stack gave without the yes that
 * stands *pending*: staged, nothing done, waiting on somebody to agree. So
 * the act sent is the one that was shown, and a move the stack turned away,
 * found nothing to do about, or already carried out has nothing to agree to.
 */
final readonly class AMoveAgreed
{
    private function __construct(private MovingInBy $by) {}

    /** The move the stack described, now agreed to; one that is not pending is refused. */
    public static function after(AMove $shown): self
    {
        if ($shown->stance() !== Stance::Pending) {
            throw ThereIsNothingToAgreeTo::staged($shown->stance());
        }

        return new self($shown->by());
    }

    /** Which way of moving in was agreed to. */
    public function by(): MovingInBy
    {
        return $this->by;
    }
}
