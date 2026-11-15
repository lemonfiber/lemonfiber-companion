<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

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
    /** @param int<0, max> $bytes */
    private function __construct(private int $bytes, private string $unknown) {}

    /** The bytes it would take; a count below nothing is refused. */
    public static function bytes(int $bytes): self
    {
        if ($bytes < 0) {
            throw QualitySaysNothing::about('total');
        }

        return new self($bytes, '');
    }

    /** Why the total could not be worked out; a blank reason is refused. */
    public static function unknown(string $why): self
    {
        if (trim($why) === '') {
            throw QualitySaysNothing::about('total');
        }

        return new self(0, $why);
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
        return $this->unknown === '' ? $bytes($this->bytes) : $unknown($this->unknown);
    }
}
