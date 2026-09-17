<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What a stack calls one thing somebody in the house asked for.
 *
 * A number rather than a name, because that is what the wire carries and what
 * an action is asked with. A value object over it for {@see ServiceId}'s
 * reason: an integer travelling through three signatures is an integer any of
 * them could have swapped for another integer, and the two on this screen are a
 * request and a size.
 *
 * **Zero and below are refused here rather than at each caller.** A stack
 * numbers what it holds from one, so a request numbered zero is a reading that
 * went wrong or a template that sent nothing — and `N2-R11`'s decision is one
 * where acting on the wrong subject is the whole harm.
 */
final readonly class RequestId
{
    /** The lowest number a stack gives anything it holds. */
    private const int THE_FIRST = 1;

    private function __construct(private int $number) {}

    /** One request, numbered as the stack numbers it. */
    public static function numbered(int $number): self
    {
        if ($number < self::THE_FIRST) {
            throw RequestIsUnnumbered::atNumber($number);
        }

        return new self($number);
    }

    /** The number, for asking with — which is the only thing it is for. */
    public function number(): int
    {
        return $this->number;
    }

    /** Whether this is the same request as that one. */
    public function is(self $other): bool
    {
        return $this->number === $other->number;
    }
}
