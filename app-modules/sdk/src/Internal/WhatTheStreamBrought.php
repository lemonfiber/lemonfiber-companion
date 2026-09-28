<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

/**
 * What one drain of a held stream took, and whether the stream ended while it did.
 *
 * The two travel together because each reading of the stream needs both: the
 * bytes to parse, and whether to let the connection go afterwards.
 */
final readonly class WhatTheStreamBrought
{
    public function __construct(
        public string $said,
        public bool $ended,
    ) {}
}
