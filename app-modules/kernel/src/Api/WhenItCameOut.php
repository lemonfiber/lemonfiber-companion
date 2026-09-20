<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * The year a holding came out, where the core knew one.
 *
 * {@see Size}'s shape for a smaller question, and here for `C2`'s reason: a
 * plain `?int` cannot say which of *nobody dated this* and *this is the year*
 * it means, so whoever receives one guesses — and the guess that gets made is
 * a zero printed beside a title.
 *
 * **Undated is ordinary rather than missing.** A household holds things whose
 * year nobody ever recorded, and a screen leaving the year off is the honest
 * rendering of that. What it must not do is invent one, which is what a
 * default in this type would be.
 */
final readonly class WhenItCameOut
{
    private function __construct(private ?int $year) {}

    /** The core dated it. */
    public static function in(int $year): self
    {
        return new self($year);
    }

    /** The core did not, and nothing here will. */
    public static function unstated(): self
    {
        return new self(null);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * @template TDated of object
     * @template TUnstated of object
     *
     * @param  Closure(int): TDated  $dated
     * @param  Closure(): TUnstated  $unstated
     * @return TDated|TUnstated
     */
    public function either(Closure $dated, Closure $unstated): object
    {
        return $this->year === null ? $unstated() : $dated($this->year);
    }
}
