<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * The VPN's forwarded port before a start and after it, and whether every client takes it.
 *
 * Five answers, each its own arm: the same port, a port that moved, a port
 * named only after the start, no port the stack could name, and no port
 * forwarded at all.
 */
final readonly class ThePortAfterAStart
{
    private function __construct(private ?int $was, private ?int $now, private bool $forwarded, private bool $everyClientTakesIt) {}

    /** The port it was before the start and is after it. */
    public static function same(int $port, bool $everyClientTakesIt): self
    {
        return new self($port, $port, forwarded: true, everyClientTakesIt: $everyClientTakesIt);
    }

    /** The port moved from one to the other. */
    public static function moved(int $was, int $now, bool $everyClientTakesIt): self
    {
        return new self($was, $now, forwarded: true, everyClientTakesIt: $everyClientTakesIt);
    }

    /** The port after the start, where the stack named none before it. */
    public static function cameUpOn(int $now, bool $everyClientTakesIt): self
    {
        return new self(null, $now, forwarded: true, everyClientTakesIt: $everyClientTakesIt);
    }

    /** A port is forwarded and the stack could not name it after the start. */
    public static function unsaid(bool $everyClientTakesIt): self
    {
        return new self(null, null, forwarded: true, everyClientTakesIt: $everyClientTakesIt);
    }

    /** The stack forwards no port. */
    public static function notForwarded(): self
    {
        return new self(null, null, forwarded: false, everyClientTakesIt: true);
    }

    /** Whether every client takes the port it is now; true where none is forwarded. */
    public function everyClientTakesIt(): bool
    {
        return $this->everyClientTakesIt;
    }

    /**
     * Say what happens for each answer.
     *
     * @template TSame of object
     * @template TMoved of object
     * @template TNow of object
     * @template TUnsaid of object
     * @template TNone of object
     *
     * @param Closure(int): TSame       $same         the port it was and is
     * @param Closure(int, int): TMoved $moved        the port it was, then the port it is
     * @param Closure(int): TNow        $now          the port it is, where the stack named none before
     * @param Closure(): TUnsaid        $unsaid       a port the stack could not name after the start
     * @param Closure(): TNone          $notForwarded no port forwarded at all
     *
     * @return TSame|TMoved|TNow|TUnsaid|TNone
     */
    public function either(Closure $same, Closure $moved, Closure $now, Closure $unsaid, Closure $notForwarded): object
    {
        return match (true) {
            ! $this->forwarded => $notForwarded(),
            $this->now === null => $unsaid(),
            $this->was === null => $now($this->now),
            $this->was === $this->now => $same($this->now),
            default => $moved($this->was, $this->now),
        };
    }
}
