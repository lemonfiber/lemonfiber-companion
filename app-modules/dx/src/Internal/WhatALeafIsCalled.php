<?php

declare(strict_types=1);

namespace Modules\Dx\Internal;

/**
 * The words the notation writes a value with no parts inside it as.
 *
 * `D4`'s subject exactly: four words, fixed by somebody else's syntax, that
 * were four string literals beside `===` in a `match`. As an enum they are a
 * vocabulary with a name, and a reader meeting `Bool` does not have to work out
 * whether the string was the word `bool` or a value that happened to spell it.
 *
 * Only the words that decide a *value* are here. `string`, `int` and `float`
 * name a type whose value a stand-in makes up, and what it makes up is
 * {@see WhatAStackWouldSay}'s business rather than a property of the word.
 */
enum WhatALeafIsCalled: string
{
    /** Either way round, so a stand-in picks. */
    case Bool = 'bool';

    /** Fixed: the contract allows nothing else there. */
    case True = 'true';

    /** Fixed the other way. */
    case False = 'false';

    /** Absent, which is a value a reader has to be given rather than denied. */
    case Null = 'null';

    /**
     * The value a field of this type carries.
     *
     * Not spelled `value()`, which a backed enum already answers with the word
     * itself — two members a line apart meaning opposite things.
     *
     * `Bool` answers true rather than false for the reason
     * {@see WhatAStackWouldSay} gives about optional fields: what a stand-in is
     * for is looking at screens, and the branch behind `false` is usually the
     * one that renders nothing.
     */
    public function carries(): ?bool
    {
        return match ($this) {
            self::Bool, self::True => true,
            self::False => false,
            self::Null => null,
        };
    }
}
