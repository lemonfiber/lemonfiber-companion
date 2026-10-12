<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * A temporary lift of the line's limits a change asks for, in minutes, or none.
 */
final readonly class ALift
{
    /** @param int<0, max>|null $minutes */
    private function __construct(private ?int $minutes) {}

    /** No lift is asked for. */
    public static function none(): self
    {
        return new self(null);
    }

    /** A lift of this many minutes, refused below nothing; how long one may be is the core's to judge. */
    public static function forMinutes(int $minutes): self
    {
        if ($minutes < 0) {
            throw LineChangeAsksNothing::forALiftOf($minutes);
        }

        return new self($minutes);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TFor of object
     * @template TNone of object
     *
     * @param Closure(int<0, max>): TFor $for
     * @param Closure(): TNone           $none
     *
     * @return TFor|TNone
     */
    public function either(Closure $for, Closure $none): object
    {
        return $this->minutes === null ? $none() : $for($this->minutes);
    }

    /** Whether no lift is asked for. */
    public function isNone(): bool
    {
        return $this->minutes === null;
    }
}
