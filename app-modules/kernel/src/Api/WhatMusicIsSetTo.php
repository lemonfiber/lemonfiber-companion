<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * The format chosen for music, or none where the stack has none set.
 *
 * Two arms rather than a nullable format, so a reader says what happens when
 * nothing is set rather than finding out on a screen.
 */
final readonly class WhatMusicIsSetTo
{
    private function __construct(private ?AFormatInForce $format) {}

    /** Music is chosen, and this is the format. */
    public static function set(AFormatInForce $format): self
    {
        return new self($format);
    }

    /** No format is set for music. */
    public static function unset(): self
    {
        return new self(null);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TSet of object
     * @template TUnset of object
     *
     * @param Closure(AFormatInForce): TSet $set
     * @param Closure(): TUnset             $unset
     *
     * @return TSet|TUnset
     */
    public function either(Closure $set, Closure $unset): object
    {
        return $this->format instanceof AFormatInForce ? $set($this->format) : $unset();
    }
}
