<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

use Closure;

/**
 * What came back from asking the store for one value.
 *
 * **Three arms, and the third is the point of the type.** *There is no such
 * key* and *the store could not be asked* look identical to anything that only
 * knows the read came back empty, and they are opposite answers: the first says
 * this device is not paired, the second says this device cannot be asked
 * whether it is paired. A launch reading the second as the first offers to pair
 * a machine that is already paired, and the operator pairs it twice.
 *
 * So there is no nullable string here and no `?? ''`. A caller has to say what
 * happens in all three cases, which is exactly the check that would otherwise
 * be forgotten.
 */
final readonly class WasRead
{
    /** What the value is on the two arms that have none. */
    private const string NOTHING_WAS_HELD = '';

    private function __construct(
        private string $value,
        private bool $held,
        private ?WhyNothingWasKept $why,
    ) {}

    /** The store holds a value under that key, and this is it. */
    public static function found(string $value): self
    {
        return new self(value: $value, held: true, why: null);
    }

    /** The store holds nothing under that key. The ordinary case on a first launch. */
    public static function nothing(): self
    {
        return new self(value: self::NOTHING_WAS_HELD, held: false, why: null);
    }

    /** The store could not be asked, and this is which of the two ways. */
    public static function refused(WhyNothingWasKept $why): self
    {
        return new self(value: self::NOTHING_WAS_HELD, held: false, why: $why);
    }

    /**
     * Say what happens in all three cases, and get back what you built.
     *
     * @template TFound of object
     * @template TNothing of object
     * @template TRefused of object
     *
     * @param  Closure(string): TFound  $found
     * @param  Closure(): TNothing  $nothing
     * @param  Closure(WhyNothingWasKept): TRefused  $refused
     * @return TFound|TNothing|TRefused
     */
    public function either(Closure $found, Closure $nothing, Closure $refused): object
    {
        if ($this->why instanceof WhyNothingWasKept) {
            return $refused($this->why);
        }

        return $this->held ? $found($this->value) : $nothing();
    }
}
