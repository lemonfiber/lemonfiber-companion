<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/** One check after a start the stack keeps, or the fact that it keeps none. */
final readonly class TheCheckKept
{
    private function __construct(private ?ACheckAfterAStart $check) {}

    /** The stack keeps this check. */
    public static function this(ACheckAfterAStart $check): self
    {
        return new self($check);
    }

    /** The stack keeps none. */
    public static function none(): self
    {
        return new self(null);
    }

    /** Whether the stack keeps one. */
    public function isKept(): bool
    {
        return $this->check instanceof ACheckAfterAStart;
    }

    /**
     * Say what happens for a check kept and for none.
     *
     * @template TKept of object
     * @template TNone of object
     *
     * @param  Closure(ACheckAfterAStart): TKept $kept
     * @param  Closure(): TNone                  $none
     * @return TKept|TNone
     */
    public function either(Closure $kept, Closure $none): object
    {
        return $this->check instanceof ACheckAfterAStart ? $kept($this->check) : $none();
    }
}
