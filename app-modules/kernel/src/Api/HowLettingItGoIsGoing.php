<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What became of stopping seeding one download, once the operator agreed.
 *
 * Shaped as {@see HowPuttingItBackIsGoing} is. A job the stack no longer has
 * an outcome for leaves the operator not knowing whether the client still
 * holds the download, which is said as that rather than as a failure.
 */
final readonly class HowLettingItGoIsGoing
{
    /** Every field defaults, and each constructor says only its own state. */
    private function __construct(
        private ?ADownloadLetGo $done = null,
        private ?Obstacle $met = null,
        private bool $running = false,
    ) {}

    /** The stack is still asking the client. */
    public static function stillRunning(): self
    {
        return new self(running: true);
    }

    /** It finished, and this is the stack's report of it. */
    public static function done(ADownloadLetGo $report): self
    {
        return new self(done: $report);
    }

    /** The stack has no outcome for it any more. */
    public static function ended(): self
    {
        return new self();
    }

    /** The stack could not be reached to ask, and this is what was met. */
    public static function met(Obstacle $why): self
    {
        return new self(met: $why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * Being unable to reach the stack outranks anything believed about the job.
     *
     * @template T of object
     *
     * @param Closure(): T               $stillRunning
     * @param Closure(ADownloadLetGo): T $done
     * @param Closure(): T               $ended
     * @param Closure(Obstacle): T       $met
     *
     * @return T
     */
    public function either(Closure $stillRunning, Closure $done, Closure $ended, Closure $met): object
    {
        return match (true) {
            $this->met instanceof Obstacle => $met($this->met),
            $this->running => $stillRunning(),
            $this->done instanceof ADownloadLetGo => $done($this->done),
            default => $ended(),
        };
    }
}
