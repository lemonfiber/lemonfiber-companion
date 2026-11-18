<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * One run of a run-and-exit service, as it ended: when it started and ended, its exit code where it has one, and the last lines it wrote.
 */
final readonly class ItsLastRun
{
    private function __construct(private Instant $started, private Instant $ended, private ?int $exit, private TheLastLines $log) {}

    /** A run that exited with this code. */
    public static function exited(Instant $started, Instant $ended, int $exit, TheLastLines $log): self
    {
        return new self($started, $ended, $exit, $log);
    }

    /** A run that ended with no exit code, never started or ended by a signal. */
    public static function endedWithoutACode(Instant $started, Instant $ended, TheLastLines $log): self
    {
        return new self($started, $ended, null, $log);
    }

    /** When it started. */
    public function started(): Instant
    {
        return $this->started;
    }

    /** When it ended. */
    public function ended(): Instant
    {
        return $this->ended;
    }

    /**
     * Its exit code, or what stands for none.
     *
     * @template TCode of object
     * @template TUnsaid of object
     *
     * @param  Closure(int): TCode  $said
     * @param  Closure(): TUnsaid   $unsaid
     * @return TCode|TUnsaid
     */
    public function exit(Closure $said, Closure $unsaid): object
    {
        return $this->exit === null ? $unsaid() : $said($this->exit);
    }

    /** Whether it exited zero. */
    public function succeeded(): bool
    {
        return $this->exit === 0;
    }

    /** The last lines it wrote. */
    public function log(): TheLastLines
    {
        return $this->log;
    }
}
