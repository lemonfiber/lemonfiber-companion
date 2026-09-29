<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What came of asking the platform's secure storage for one seal key.
 *
 * Three answers, and the second is the point of the type. A key that was there
 * and a key made just now are both a key the seal can use, and they are
 * opposite news to whoever kept something: under the first, what was sealed
 * still opens, and under the second nothing sealed before ever will.
 */
final readonly class KeyHeld
{
    private function __construct(
        private KeyMaterial|WhyNothingIsSealed $answer,
        private bool $madeNow = false,
    ) {}

    /** The key was there, and this is it. */
    public static function held(KeyMaterial $key): self
    {
        return new self($key);
    }

    /** There was no key that could be read, and this one was made and kept in its place. */
    public static function madeAfresh(KeyMaterial $key): self
    {
        return new self($key, madeNow: true);
    }

    /** No key can be had, and this is why. */
    public static function refused(WhyNothingIsSealed $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in all three cases, and get back what you built.
     *
     * @template THeld of object
     * @template TMadeAfresh of object
     * @template TRefused of object
     *
     * @param Closure(KeyMaterial): THeld           $held
     * @param Closure(KeyMaterial): TMadeAfresh     $madeAfresh
     * @param Closure(WhyNothingIsSealed): TRefused $refused
     *
     * @return THeld|TMadeAfresh|TRefused
     */
    public function either(Closure $held, Closure $madeAfresh, Closure $refused): object
    {
        if ($this->answer instanceof WhyNothingIsSealed) {
            return $refused($this->answer);
        }

        return $this->madeNow ? $madeAfresh($this->answer) : $held($this->answer);
    }
}
