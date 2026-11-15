<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function is_int;
use function trim;

/**
 * What upgrading one kind of media takes in total, or why the stack could not work that out.
 *
 * The bytes re-fetching what sits below the cutoff would take, counted by the
 * stack; where it could not count them, its reason in the operator's words. No
 * total is worked out here.
 */
final readonly class WhatUpgradingItTakes
{
    /** @param int<0, max>|non-empty-string $total the bytes it would take, or why that could not be worked out */
    private function __construct(private int|string $total) {}

    /** The bytes it would take; a count below nothing is refused. */
    public static function bytes(int $bytes): self
    {
        if ($bytes < 0) {
            throw QualitySaysNothing::about('total');
        }

        return new self($bytes);
    }

    /** Why the total could not be worked out; a blank reason is refused. */
    public static function unknown(string $why): self
    {
        if (trim($why) === '') {
            throw QualitySaysNothing::about('total');
        }

        return new self($why);
    }

    /**
     * @template TBytes of object
     * @template TUnknown of object
     *
     * @param  Closure(int<0, max>): TBytes $bytes
     * @param  Closure(string): TUnknown    $unknown
     * @return TBytes|TUnknown
     */
    public function either(Closure $bytes, Closure $unknown): object
    {
        return is_int($this->total) ? $bytes($this->total) : $unknown($this->total);
    }
}
