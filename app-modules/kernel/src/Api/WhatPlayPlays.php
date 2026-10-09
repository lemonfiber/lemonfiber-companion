<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What one Play plays: a title or an episode, with where it plays, or nothing at all.
 */
final readonly class WhatPlayPlays
{
    private function __construct(private ?HoldingId $id, private string $titled, private ?WhereItPlays $plays) {}

    /** This one, under this name, and where it plays. */
    public static function one(HoldingId $id, string $titled, WhereItPlays $plays): self
    {
        return new self($id, $titled, $plays);
    }

    /** Nothing: a series with no episode listed, or an episode the title does not hold. */
    public static function nothing(): self
    {
        return new self(null, '', null);
    }

    /**
     * Say what happens in both cases, and get back what you built.
     *
     * @template TOne of object
     * @template TNothing of object
     *
     * @param  Closure(HoldingId, string, WhereItPlays): TOne  $one
     * @param  Closure(): TNothing  $nothing
     * @return TOne|TNothing
     */
    public function either(Closure $one, Closure $nothing): object
    {
        return $this->id instanceof HoldingId && $this->plays instanceof WhereItPlays
            ? $one($this->id, $this->titled, $this->plays)
            : $nothing();
    }
}
