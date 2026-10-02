<?php

declare(strict_types=1);

namespace Modules\News\Internal;

use Closure;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\WhyNoReadingIsFound;

/**
 * One kept row about what is new on a stack, as the store found it: sealed, none, or one this build cannot read.
 */
final readonly class KeptNews
{
    private function __construct(private SealedPayload|WhyNoReadingIsFound $answer, private Shape $shape) {}

    /** The row, sealed as it was kept, with the shape it was written in. */
    public static function found(SealedPayload $payload, Shape $shape): self
    {
        return new self($payload, $shape);
    }

    /** Nothing is kept. */
    public static function none(): self
    {
        return new self(WhyNoReadingIsFound::NoneIsKept, Shape::current());
    }

    /** A row is kept that this build cannot read. */
    public static function thatThisBuildCannotRead(): self
    {
        return new self(WhyNoReadingIsFound::ItCannotBeRead, Shape::current());
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TFound of object
     * @template TNone of object
     * @template TUnreadable of object
     *
     * @param Closure(SealedPayload, Shape): TFound $found
     * @param Closure(): TNone                     $none
     * @param Closure(): TUnreadable               $unreadable
     *
     * @return TFound|TNone|TUnreadable
     */
    public function either(Closure $found, Closure $none, Closure $unreadable): object
    {
        if ($this->answer instanceof SealedPayload) {
            return $found($this->answer, $this->shape);
        }

        return match ($this->answer) {
            WhyNoReadingIsFound::NoneIsKept => $none(),
            WhyNoReadingIsFound::ItCannotBeRead => $unreadable(),
        };
    }
}
