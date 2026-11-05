<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * One thing a change newly asks the operator for, why, and where its answer is kept.
 */
final readonly class WhatAChangeAsksFor
{
    private function __construct(
        public string $what,
        public string $because,
        private ?string $setting,
    ) {}

    /** Something whose answer is kept in a setting. */
    public static function keptIn(string $what, string $because, string $setting): self
    {
        $where = trim($setting);

        if ($where === '') {
            throw AFindingSaysNothing::about('setting');
        }

        return new self(self::said($what, 'what'), self::said($because, 'because'), $where);
    }

    /** An account the operator obtains themselves, which no setting holds. */
    public static function toObtain(string $what, string $because): self
    {
        return new self(self::said($what, 'what'), self::said($because, 'because'), null);
    }

    /**
     * Say where its answer is kept, or that the operator obtains it.
     *
     * @template TKept of object
     * @template TObtained of object
     *
     * @param  Closure(string): TKept  $kept
     * @param  Closure(): TObtained  $obtained
     * @return TKept|TObtained
     */
    public function where(Closure $kept, Closure $obtained): object
    {
        return $this->setting === null ? $obtained() : $kept($this->setting);
    }

    private static function said(string $text, string $part): string
    {
        $said = trim($text);

        if ($said === '') {
            throw AFindingSaysNothing::about($part);
        }

        return $said;
    }
}
