<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/** What a stack answered when asked to keep this phone's endpoint: kept, refused in its words, or not reached. */
final readonly class WhatRegisteringAWakeCameTo
{
    private function __construct(private AnEndpoint|ARefusalInItsWords|Obstacle $answer) {}

    /** The stack keeps the endpoint, as it wrote it back. */
    public static function registered(AnEndpoint $endpoint): self
    {
        return new self($endpoint);
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
     * @param  Closure(AnEndpoint): TRegistered      $registered
     * @param  Closure(ARefusalInItsWords): TRefused $refused
     * @param  Closure(Obstacle): TMet               $met
     * @return TRegistered|TRefused|TMet
     */
    public function either(Closure $registered, Closure $refused, Closure $met): object
    {
        return match (true) {
            $this->answer instanceof AnEndpoint => $registered($this->answer),
            $this->answer instanceof ARefusalInItsWords => $refused($this->answer),
            default => $met($this->answer),
        };
    }
}
