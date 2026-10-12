<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What one run of the line's action did: rehearse a change, apply it, or only read the line.
 */
enum WhatTheLineRunDid: string
{
    case Rehearsed = 'rehearsed';

    case Applied = 'applied';

    case OnlyRead = 'only-read';

    /** Which of the three a report's two flags say, a rehearsal outranking anything it claims to have written. */
    public static function said(bool $rehearsed, bool $applied): self
    {
        return match (true) {
            $rehearsed => self::Rehearsed,
            $applied => self::Applied,
            default => self::OnlyRead,
        };
    }

    /** The catalogue key a screen leads with. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.line_change.did.%s', $this->value);
    }
}
