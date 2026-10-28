<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_map;
use function implode;
use function sprintf;

/** The words this app reads, as a refusal names them: each in backticks, joined with commas. */
final readonly class TheWordsItReads
{
    public static function quoted(string ...$words): string
    {
        return implode(', ', array_map(static fn(string $word): string => sprintf('`%s`', $word), $words));
    }
}
