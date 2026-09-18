<?php

declare(strict_types=1);

namespace Modules\Vault\Internal;

/**
 * Whether the store holds a record, carried out of an `either()` arm.
 *
 * {@see \Lemonfiber\Native\WasRead::either()} answers with an object, so that a
 * caller cannot read a value out without saying what happens when there is none
 * and what happens when the store could not be asked. A lock still wants a yes
 * or a no, and this is the smallest honest way across — it is what the arms are
 * allowed to build, and it carries nothing that was read.
 *
 * **Two named constructors rather than a boolean parameter**, which is `D5`: a
 * bare `true` at a call site says nothing about what is true, and here the call
 * sites are the arms of the outcome, so each says which arm it is.
 *
 * `Internal` because it is a detail of how this module reads an outcome, and
 * `E2`'s promise is that anything here can be renamed without reading another
 * module. {@see \Modules\Operator\Internal\WhetherItIsHeld} is the same move at
 * a different outcome; they are deliberately not shared, because a type in
 * `Kernel\Api` is a thing every module may name and neither of these is.
 */
final readonly class WhetherAnythingIsHeld
{
    private function __construct(public bool $held) {}

    /** The store holds a record, whatever is in it. */
    public static function itIs(): self
    {
        return new self(held: true);
    }

    /** It holds none, or holds one that has been emptied. */
    public static function itIsNot(): self
    {
        return new self(held: false);
    }
}
