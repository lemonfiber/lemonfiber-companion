<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What one run of a switch reported: whether it was a rehearsal, what it stopped, what it started, and what it left running.
 */
final readonly class TheSwitch
{
    private function __construct(
        private WhetherItWasRehearsed $did,
        private TheServicesNamed $stopped,
        private TheServicesNamed $started,
        private TheServicesNamed $kept,
        private ?TheCommandLine $stoppedWith,
    ) {}

    /** The stack's report, each list in the order it gave. */
    public static function said(WhetherItWasRehearsed $did, TheServicesNamed $stopped, TheServicesNamed $started, TheServicesNamed $kept): self
    {
        return new self($did, $stopped, $started, $kept, null);
    }

    /** The same report, with the command that stops what falls outside the new set. */
    public function stoppingWith(TheCommandLine $command): self
    {
        return new self($this->did, $this->stopped, $this->started, $this->kept, $command);
    }

    public function did(): WhetherItWasRehearsed
    {
        return $this->did;
    }

    /** Stopped, because the new set does not hold them. */
    public function stopped(): TheServicesNamed
    {
        return $this->stopped;
    }

    /** Started, because the new set holds them and they were not up. */
    public function started(): TheServicesNamed
    {
        return $this->started;
    }

    /** Left running, not restarted: both sets hold them. */
    public function kept(): TheServicesNamed
    {
        return $this->kept;
    }

    /**
     * The command that stops what falls outside, or that nothing had to stop.
     *
     * @template TWith of object
     * @template TNone of object
     *
     * @param Closure(TheCommandLine): TWith $with
     * @param Closure(): TNone               $none
     *
     * @return TWith|TNone
     */
    public function stopCommand(Closure $with, Closure $none): object
    {
        return $this->stoppedWith instanceof TheCommandLine ? $with($this->stoppedWith) : $none();
    }
}
