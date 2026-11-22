<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/** Holds each part of what the stack leaves alone to sense as it is taken in. */
trait ReadsWhatIsLeftAlone
{
    private static function said(string $text, string $part): string
    {
        if (trim($text) === '') {
            throw WhatIsLeftAloneMakesNoSense::saysNothingOf($part);
        }

        return $text;
    }

    private static function counted(int $more, string $part): int
    {
        if ($more < 0) {
            throw WhatIsLeftAloneMakesNoSense::countsBelowNone($part);
        }

        return $more;
    }
}
