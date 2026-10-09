<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/** The grant a device holds on a stack, or that it holds none it can read. */
final readonly class TheGrantHeld
{
    private function __construct(private ?AGrant $grant) {}

    public static function held(AGrant $grant): self
    {
        return new self($grant);
    }

    /** Nothing kept, or nothing this build can read, which is the same to whoever plays. */
    public static function none(): self
    {
        return new self(null);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * @template THeld of object
     * @template TNone of object
     *
     * @param Closure(AGrant): THeld $held
     * @param Closure(): TNone       $none
     *
     * @return THeld|TNone
     */
    public function either(Closure $held, Closure $none): object
    {
        return $this->grant instanceof AGrant ? $held($this->grant) : $none();
    }
}
