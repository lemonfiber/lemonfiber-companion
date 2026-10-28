<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What one Play plays: a title or an episode, with where it plays, or nothing at all.
 */
final readonly class WhatPlayPlays
{
    /** @param array{HoldingId, string, WhereItPlays}|null $one the one it plays, under its name, and where it plays; or none */
    private function __construct(private ?array $one) {}

    /** This one, under this name, and where it plays. */
    public static function one(HoldingId $id, string $titled, WhereItPlays $plays): self
    {
        return new self([$id, $titled, $plays]);
    }

    /** Nothing: a series with no episode listed, or an episode the title does not hold. */
    public static function nothing(): self
    {
        return new self(null);
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
        return $this->one === null ? $nothing() : $one(...$this->one);
    }
}
