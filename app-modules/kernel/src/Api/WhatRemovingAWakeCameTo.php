<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/** What a stack answered when asked to forget this phone's endpoint: forgotten, refused in its words, or not reached. */
final readonly class WhatRemovingAWakeCameTo
{
    private function __construct(private ARefusalInItsWords|Obstacle|null $answer) {}

    /** The stack holds the endpoint no more, whether it removed it now or held none by that name. */
    public static function removed(): self
    {
        return new self(null);
    }

    public static function refused(ARefusalInItsWords $why): self
    {
        return new self($why);
    }

    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * @template TRemoved of object
     * @template TRefused of object
     * @template TMet of object
     *
     * @param  Closure(): TRemoved                   $removed
     * @param  Closure(ARefusalInItsWords): TRefused $refused
     * @param  Closure(Obstacle): TMet               $met
     * @return TRemoved|TRefused|TMet
     */
    public function either(Closure $removed, Closure $refused, Closure $met): object
    {
        return match (true) {
            $this->answer instanceof ARefusalInItsWords => $refused($this->answer),
            $this->answer instanceof Obstacle => $met($this->answer),
            default => $removed(),
        };
    }
}
