<?php

declare(strict_types=1);

namespace Modules\Dx\Internal;

/**
 * The punctuation a declared type is written with.
 *
 * `D4` asks that a closed set be an enum rather than a literal beside `===`,
 * and this is as closed as a set gets: the notation is somebody else's and
 * these five characters are the whole of what this module reads structurally.
 * A sixth would be a change to PHPStan's syntax, which is exactly the kind of
 * event a named case makes visible and a bare `'{'` does not.
 *
 * Named for what each one does to a reading rather than for its shape. `'<'` is
 * an angle bracket in every language and *opens a parameter* in this one, and
 * the second reading is the one a reader of {@see WhatTheContractDeclares}
 * needs.
 */
enum TheNotation: string
{
    /** Opens a shape: `array{a: string}`. */
    case OpensAShape = '{';

    /** Closes one. */
    case ClosesAShape = '}';

    /** Opens what a collection holds: `list<string>`, `array<string, int>`. */
    case OpensAParameter = '<';

    /** Closes one. */
    case ClosesAParameter = '>';

    /** Marks a field the contract allows to be absent: `at?: string`. */
    case MarksOptional = '?';

    /**
     * How far into a type one character moves the reading.
     *
     * Depth rather than a kind, because every caller asks the same question:
     * whether a separator sits at the top of the type or inside something. A
     * comma at depth one belongs to whatever opened, and splitting on it is how
     * `array<string, list<int>>` comes apart in the wrong place.
     */
    public function deeper(): int
    {
        return match ($this) {
            self::OpensAShape, self::OpensAParameter => 1,
            self::ClosesAShape, self::ClosesAParameter => -1,
            self::MarksOptional => 0,
        };
    }
}
