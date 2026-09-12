<?php

declare(strict_types=1);

namespace Modules\Health\Api;

use InvalidArgumentException;

/**
 * A finding arrived without the identifier its check is recognised by.
 *
 * Raised where a string becomes a `Check`. Without it a finding cannot be
 * matched against the same finding from yesterday, so "this is still broken"
 * and "this broke again" become the same screen (C3).
 */
final class CheckIsUnnamed extends InvalidArgumentException
{
    public static function inAReport(): self
    {
        return new self('A finding arrived with no check identifier, so nothing can recognise it between runs.');
    }
}
