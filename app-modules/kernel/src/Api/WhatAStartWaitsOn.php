<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * What the stack last said a start is waiting for, when the screen asked.
 *
 * A bare sentence of the stack's own, which the newest one replaces. Three
 * answers, because a screen does something different with each: a new line to
 * draw in place of the last, nothing new since the last wake, or a stream that
 * could not be heard, with the obstacle saying why.
 *
 * The line is drawn as the stack wrote it and nothing of this app's is put in
 * its place: what a start is waiting for is the stack's to say, and a
 * progress figure worked out here would be a guess at something it knows.
 */
final readonly class WhatAStartWaitsOn
{
    private function __construct(
        private string $line,
        private ?Obstacle $why,
    ) {}

    /** The stack said this, and it replaces whatever it said before. */
    public static function saying(string $line): self
    {
        $said = trim($line);

        if ($said === '') {
            throw StartSaysNothing::inItsLine();
        }

        return new self($said, null);
    }

    /** Nothing about the start has arrived since the screen last asked. */
    public static function nothingNew(): self
    {
        return new self('', null);
    }

    /** The stream could not be heard, and this is what stood in the way. */
    public static function met(Obstacle $why): self
    {
        return new self('', $why);
    }

    /**
     * Every arm required, so a screen says what each answer means to it.
     *
     * @template T of object
     *
     * @param Closure(string): T   $saying
     * @param Closure(): T         $nothingNew
     * @param Closure(Obstacle): T $met
     *
     * @return T
     */
    public function either(Closure $saying, Closure $nothingNew, Closure $met): object
    {
        return match (true) {
            $this->why instanceof Obstacle => $met($this->why),
            $this->line !== '' => $saying($this->line),
            default => $nothingNew(),
        };
    }
}
