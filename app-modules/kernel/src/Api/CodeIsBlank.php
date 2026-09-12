<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A problem arrived without the identifier an operator would search for.
 *
 * Thrown rather than returned, which is the exception to C1 rather than a
 * breach of it: C1 is about a refusal crossing a module boundary, and this is
 * not a refusal — it is a value that cannot be constructed, raised at the one
 * place a string becomes a `Code`. There is nothing for a caller to handle,
 * because there is no code to show either way.
 *
 * Module-owned rather than a bare exception so that a catch block can name
 * this and nothing else (C3). Built through a named constructor rather than by
 * overriding `__construct`, because `parent::__construct()` is the base
 * class's constructor called from outside it as far as the analyser is
 * concerned; `new self(...)` inside the class is the cure `phpstan.neon`
 * describes.
 */
final class CodeIsBlank extends InvalidArgumentException
{
    public static function inAProblem(): self
    {
        return new self('A problem arrived with no code, and the code is what an operator searches for a year later.');
    }
}
