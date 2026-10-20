<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What one run of a setting's action did: rehearse the change, write it, or neither.
 */
enum WhatTheSettingRunDid: string
{
    case Rehearsed = 'rehearsed';

    case Applied = 'applied';

    case NotWritten = 'not-written';

    /** Which of the three a report's two flags say, a rehearsal outranking anything it claims to have written. */
    public static function said(bool $rehearsed, bool $applied): self
    {
        return match (true) {
            $rehearsed => self::Rehearsed,
            $applied => self::Applied,
            default => self::NotWritten,
        };
    }

    /** The catalogue key a screen leads with. */
    public function saidOnTheScreen(): string
    {
        return match ($this) {
            self::Rehearsed => 'plugins.settings.did.rehearsed',
            self::Applied => 'plugins.settings.did.applied',
            self::NotWritten => 'plugins.settings.did.not_written',
        };
    }
}
