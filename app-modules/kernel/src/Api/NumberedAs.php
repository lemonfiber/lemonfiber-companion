<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/** A season's or an episode's number, where it has one: specials often have none. */
final readonly class NumberedAs
{
    private function __construct(private ?int $number) {}

    public static function number(int $number): self
    {
        return new self($number);
    }

    public static function none(): self
    {
        return new self(null);
    }

    /**
     * @template TNumbered of object
     * @template TNone of object
     *
     * @param Closure(int): TNumbered $number
     * @param Closure(): TNone        $none
     *
     * @return TNumbered|TNone
     */
    public function either(Closure $number, Closure $none): object
    {
        return $this->number === null ? $none() : $number($this->number);
    }
}
