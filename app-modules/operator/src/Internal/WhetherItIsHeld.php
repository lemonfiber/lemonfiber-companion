<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/**
 * Whether a stack has a session on this device, carried out of an `either()` arm.
 *
 * {@see \Modules\Kernel\Api\Resumed::either()} answers with an object, so that
 * a caller cannot pull a session out without saying what happens when there is
 * none. A screen still wants a yes or a no, and this is the smallest honest way
 * across — it is what the arms are allowed to build, and it carries no session.
 *
 * **Two named constructors rather than a boolean parameter**, which is `D5`: a
 * bare `true` at a call site says nothing about what is true, and here the two
 * call sites are the two arms of the outcome — so each says which arm it is.
 *
 * `Internal` because it is a detail of how this surface reads an outcome, and
 * `E2`'s promise is that anything here can be renamed without reading another
 * module.
 */
final readonly class WhetherItIsHeld
{
    private function __construct(public bool $held) {}

    /** The store had a session for that stack. */
    public static function itIs(): self
    {
        return new self(held: true);
    }

    /** It did not, for whatever reason — which this deliberately does not carry. */
    public static function itIsNot(): self
    {
        return new self(held: false);
    }
}
