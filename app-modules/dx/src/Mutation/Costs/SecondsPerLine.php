<?php

declare(strict_types=1);

namespace Dx\Mutation\Costs;

use function count;

use Dx\Mutation\TheRepository;

use function in_array;
use function is_array;
use function token_get_all;

/**
 * A file's cost estimated from its lines of code and what a line costs where
 * it is ({@see WhatALineCosts}).
 */
final readonly class SecondsPerLine implements WhatAFileCosts
{
    public function __construct(private TheRepository $repository, private WhatALineCosts $lines) {}

    public function secondsFor(string $file): float
    {
        return self::linesOfCode($this->repository->contentsOf($file)) * $this->lines->secondsPerLineOf($file);
    }

    /**
     * The lines of PHP that hold code: a line with at least one token that is
     * not whitespace, a comment or the opening tag. A line holding only a brace
     * or a semicolon counts for nothing: `token_get_all()` gives those tokens as
     * bare strings, with no line number.
     */
    public static function linesOfCode(string $source): int
    {
        $lines = [];

        foreach (token_get_all($source) as $token) {
            if (is_array($token) && ! in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG], strict: true)) {
                $lines[$token[2]] = true;
            }
        }

        return count($lines);
    }
}
