<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What came of sealing a value: the sealed payload, or why there is none.
 *
 * A refusal is a value here for {@see Kept}'s reason: a phone with no secure
 * storage is an ordinary state of the world, and a seal that could only report
 * one by throwing would make it the case nothing checks — and the case that
 * gets a value written to disk in the clear by whoever caught it.
 *
 * One field holding either answer, so no state holds both and none holds
 * neither.
 */
final readonly class Sealing
{
    private function __construct(private SealedPayload|WhyNothingIsSealed $answer) {}

    /** The value is sealed, and this is what a store may keep. */
    public static function sealed(SealedPayload $payload): self
    {
        return new self($payload);
    }

    /** Nothing was sealed, and this is why. */
    public static function refused(WhyNothingIsSealed $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * @template TSealed of object
     * @template TRefused of object
     *
     * @param Closure(SealedPayload): TSealed       $sealed
     * @param Closure(WhyNothingIsSealed): TRefused $refused
     *
     * @return TSealed|TRefused
     */
    public function either(Closure $sealed, Closure $refused): object
    {
        return $this->answer instanceof WhyNothingIsSealed
            ? $refused($this->answer)
            : $sealed($this->answer);
    }
}
