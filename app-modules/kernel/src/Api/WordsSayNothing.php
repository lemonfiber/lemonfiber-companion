<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/** A word of the glossary that arrived with something it owes left blank. */
final class WordsSayNothing extends InvalidArgumentException
{
    public static function about(string $field): self
    {
        return new self(sprintf(
            'A word of the glossary arrived with its `%s` blank, and a word that does not say what it means explains nothing.',
            $field,
        ));
    }
}
