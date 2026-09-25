<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Whether putting a copy back puts its data where it came from, or elsewhere.
 *
 * Two answers, and the stack gives one of them on every restore. The second
 * is the one an operator has to be told, so there is no way to read this
 * without saying what happens for it.
 */
final readonly class WhereTheDataGoes
{
    private function __construct(private ?ARelocation $elsewhere) {}

    /** Back where it came from. */
    public static function whereItWas(): self
    {
        return new self(null);
    }

    /** Somewhere other than where it came from. */
    public static function elsewhere(ARelocation $relocation): self
    {
        return new self($relocation);
    }

    /** Whether it goes somewhere other than where it came from. */
    public function isElsewhere(): bool
    {
        return $this->elsewhere instanceof ARelocation;
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TInPlace of object
     * @template TElsewhere of object
     *
     * @param Closure(): TInPlace              $whereItWas
     * @param Closure(ARelocation): TElsewhere $elsewhere
     *
     * @return TInPlace|TElsewhere
     */
    public function either(Closure $whereItWas, Closure $elsewhere): object
    {
        return $this->elsewhere instanceof ARelocation ? $elsewhere($this->elsewhere) : $whereItWas();
    }
}
