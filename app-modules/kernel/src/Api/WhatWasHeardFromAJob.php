<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use Closure;

use function trim;

/** The lines one job said since a screen last asked, or that it said none, or what stood in the way of hearing it. */
final readonly class WhatWasHeardFromAJob
{
    /** @param list<string> $lines */
    private function __construct(
        private array $lines,
        private ?Obstacle $why,
    ) {}

    public static function said(string ...$lines): self
    {
        foreach ($lines as $line) {
            if (trim($line) === '') {
                throw AJobSaidNothing::inALine();
            }
        }

        return new self(array_values($lines), null);
    }

    public static function nothingNew(): self
    {
        return new self([], null);
    }

    public static function met(Obstacle $why): self
    {
        return new self([], $why);
    }

    /**
     * @template T of object
     * @param Closure(string ...): T $said
     * @param Closure(): T $nothingNew
     * @param Closure(Obstacle): T $met
     * @return T
     */
    public function either(Closure $said, Closure $nothingNew, Closure $met): object
    {
        return match (true) {
            $this->why instanceof Obstacle => $met($this->why),
            $this->lines !== [] => $said(...$this->lines),
            default => $nothingNew(),
        };
    }
}
