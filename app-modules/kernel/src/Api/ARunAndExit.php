<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * One run-and-exit service, as its runs left it: its health, its last run, whether one is going, the times skipped and whether this machine's scheduler fires it.
 */
final readonly class ARunAndExit
{
    private function __construct(
        private ServiceId $id,
        private string $name,
        private HowItsLastRunWent $health,
        private ?ItsLastRun $last,
        private bool $running,
        private bool $fired,
        private int $skipped,
    ) {}

    /** A service that has not run yet. */
    public static function notRunYet(ServiceId $id, string $name, bool $running, bool $fired, int $skipped): self
    {
        return new self($id, $name, HowItsLastRunWent::NotRun, null, $running, $fired, $skipped);
    }

    /** A service whose last run went as its health says. */
    public static function lastRan(ServiceId $id, string $name, HowItsLastRunWent $health, ItsLastRun $last, bool $running, bool $fired, int $skipped): self
    {
        return new self($id, $name, $health, $last, $running, $fired, $skipped);
    }

    /** The id it goes by, its Compose service name. */
    public function id(): ServiceId
    {
        return $this->id;
    }

    /** What it is called in front of an operator. */
    public function name(): string
    {
        return $this->name;
    }

    /** How its last run went. */
    public function health(): HowItsLastRunWent
    {
        return $this->health;
    }

    /**
     * Its last run, or what stands for none.
     *
     * @template TRan of object
     * @template TNever of object
     *
     * @param  Closure(ItsLastRun): TRan $ran
     * @param  Closure(): TNever         $never
     * @return TRan|TNever
     */
    public function last(Closure $ran, Closure $never): object
    {
        return $this->last instanceof ItsLastRun ? $ran($this->last) : $never();
    }

    /** Whether a run of it is going now. */
    public function isRunning(): bool
    {
        return $this->running;
    }

    /** Whether this machine's scheduler holds its job, so its times are fired. */
    public function isFired(): bool
    {
        return $this->fired;
    }

    /** How many times fell due while a run was still going, each skipped. */
    public function skipped(): int
    {
        return $this->skipped;
    }
}
