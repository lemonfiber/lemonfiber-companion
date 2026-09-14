<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What became of asking a stack what it would put right.
 *
 * `N2-R4` wants an operator told what a repair does, what else it affects and
 * whether it can be undone *before* being asked to confirm. The stack answers
 * that in a listing — and it answers it as a job, so this is the reading of a
 * handle rather than the answer to a question.
 *
 * **Four states, and the fourth is the one worth spelling out.** Still running
 * and finished are the obvious pair. *Ended* is the third: a job the stack no
 * longer has an outcome for, because it was let go of, or because the stack
 * restarted, or because it never really got going. A port that folded ended
 * into still-running would spin forever on a handle nothing will ever answer
 * for; one that folded it into finished would report a listing that does not
 * exist. Both are worse than saying *ask again*, which is what this arm is for.
 *
 * The fourth is {@see Obstacle}, and it is about reaching the stack rather than
 * about the job — the same set every other screen in this app reads.
 *
 * **A finished offer can be empty, and that is an answer.** A stack with
 * nothing to put right is the healthy case, and {@see Offer} holding an empty
 * {@see Repairs} says so without a null anywhere (`C2`). It is told apart from
 * a job that ended, which is the distinction an operator acts on: *there is
 * nothing to fix* against *ask me again*.
 */
final readonly class HowTheOfferIsGoing
{
    private function __construct(
        private ?Offer $offered,
        private ?Obstacle $met,
        private bool $running,
    ) {}

    /** The stack is still working out what it would do. */
    public static function stillRunning(): self
    {
        return new self(offered: null, met: null, running: true);
    }

    /** It finished, and this is what it would put right. */
    public static function offering(Offer $offer): self
    {
        return new self(offered: $offer, met: null, running: false);
    }

    /**
     * The stack has no outcome for that job any more.
     *
     * Not a failure and not an answer. The remedy is to ask again, which is a
     * thing the operator does rather than something this app should do on their
     * behalf — `N1-R17` keeps a screen from being a poller, and a job that
     * ended is exactly where an automatic retry becomes one.
     */
    public static function ended(): self
    {
        return new self(offered: null, met: null, running: false);
    }

    /** The stack could not be reached to ask, and this is what was met. */
    public static function met(Obstacle $why): self
    {
        return new self(offered: null, met: $why, running: false);
    }

    /**
     * Say what happens in each of the four, and get back what you built.
     *
     * Every arm required. An optional one would be a default, and a default is
     * where a job that ended quietly becomes a stack with nothing to fix.
     *
     * @template TRunning of object
     * @template TOffered of object
     * @template TEnded of object
     * @template TMet of object
     *
     * @param Closure(): TRunning     $stillRunning
     * @param Closure(Offer): TOffered $offering
     * @param Closure(): TEnded       $ended
     * @param Closure(Obstacle): TMet $met
     *
     * @return TRunning|TOffered|TEnded|TMet
     */
    public function either(Closure $stillRunning, Closure $offering, Closure $ended, Closure $met): object
    {
        // One expression rather than four `if`s, and the order is the meaning:
        // being unable to reach the stack outranks anything believed about the
        // job, because what is believed was read from a stack that is not
        // answering now. Running outranks the rest for the same reason `Launch`
        // puts locked first — it is the state the others are not yet in.
        return match (true) {
            $this->met instanceof Obstacle => $met($this->met),
            $this->running => $stillRunning(),
            $this->offered instanceof Offer => $offering($this->offered),
            default => $ended(),
        };
    }
}
