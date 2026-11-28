<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/** What a stack answered when asked to keep this phone's endpoint: kept, refused in its words, or not reached. */
final readonly class WhatRegisteringAWakeCameTo
{
    private function __construct(private AWakeFingerprint|ARefusalInItsWords|Obstacle $answer) {}

    /** The stack keeps the endpoint, named by the fingerprint it wrote back. */
    public static function registered(AWakeFingerprint $held): self
    {
        return new self($held);
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
     * @template TRegistered of object
     * @template TRefused of object
     * @template TMet of object
     *
     * @param  Closure(AWakeFingerprint): TRegistered $registered
     * @param  Closure(ARefusalInItsWords): TRefused $refused
     * @param  Closure(Obstacle): TMet               $met
     * @return TRegistered|TRefused|TMet
     */
    public function either(Closure $registered, Closure $refused, Closure $met): object
    {
        return match (true) {
            $this->answer instanceof AWakeFingerprint => $registered($this->answer),
            $this->answer instanceof ARefusalInItsWords => $refused($this->answer),
            default => $met($this->answer),
        };
    }
}
