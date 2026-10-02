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
 * A replacement's yes carries the name of the offer it was shown.
 */
final readonly class AMoveAgreed
{
    private function __construct(private MovingInBy $by, private string $offer) {}

    /** The move the stack described, now agreed to; one that is not pending is refused. */
    public static function after(AMove $shown): self
    {
        if ($shown->stance() !== Stance::Pending) {
            throw ThereIsNothingToAgreeTo::staged($shown->stance());
        }

        return $shown->either(
            adopting: static fn(): self => new self(MovingInBy::Adopting, ''),
            importing: static fn(): self => new self(MovingInBy::Importing, ''),
            standingBeside: static fn(): self => new self(MovingInBy::StandingBeside, ''),
            replacing: static fn(TheReplacement $replacing): self => new self(MovingInBy::Replacing, $replacing->agreement()),
        );
    }

    /**
     * The name of the offer this agrees to, or `''` where the yes is a confirmation.
     *
     * A replacement stops somebody's running services, so its yes is the name of
     * what it said it would stop; every other way of moving in is agreed to by
     * confirming it.
     */
    public function offer(): string
    {
        return $this->offer;
    }

    /** Which way of moving in was agreed to. */
    public function by(): MovingInBy
    {
        return $this->by;
    }
}
