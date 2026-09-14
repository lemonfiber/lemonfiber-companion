<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Whether a report reached the operator's own hands.
 *
 * The answer {@see Sharing} gives, and a value rather than an exception for
 * `C1`'s reason: a device with nowhere to write a temporary file is an ordinary
 * state of the world, and the commonest cause is a disk somebody has filled
 * with the media this product exists to manage.
 *
 * **It does not say whether the report was sent**, and cannot. The share sheet
 * belongs to the platform; an operator may pick an app or change their mind,
 * and neither comes back. Claiming otherwise on a screen would be the app
 * telling somebody their support request is on its way when nothing was sent.
 *
 * The shape is {@see Kept}'s, and it stays its own class for the reason
 * {@see Reading} gives: what makes any of them readable at a call site is the
 * pair of names, and a shared `left`/`right` would trade that away.
 */
final readonly class Handed
{
    private function __construct(private ?WhyNothingWasShared $why) {}

    /** The share sheet was reached, and the rest is the operator's. */
    public static function over(): self
    {
        return new self(null);
    }

    /** It was not, for the reason named. */
    public static function refused(WhyNothingWasShared $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * @template TOver of object
     * @template TRefused of object
     *
     * @param Closure(): TOver                      $over
     * @param Closure(WhyNothingWasShared): TRefused $refused
     *
     * @return TOver|TRefused
     */
    public function either(Closure $over, Closure $refused): object
    {
        // Read off the refusal, the way `Kept` does: the arm with something to
        // explain is the one the type is written around, and a fall-through is
        // how a branch becomes the one nobody tested.
        return $this->why instanceof WhyNothingWasShared ? $refused($this->why) : $over();
    }
}
