<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What became of a listing the operator agreed to.
 *
 * The sibling of {@see HowTheOfferIsGoing} and shaped the same way, because it
 * reads the same kind of thing: every action on this surface arrives
 * as a job, so agreeing answers a handle and this is the reading of it.
 *
 * **Two types rather than one, deliberately.** They differ only in what the
 * finished arm carries — a listing of what *would* be done against a record of
 * what *was* — and PHP cannot express that as one type without the arm going
 * untyped. An untyped arm is how a screen comes to render an offer as an
 * outcome, which is the one confusion on this surface that would have somebody
 * believe their machine had been changed when it had not.
 *
 * **The third arm matters more here than it does there.** A job that ended
 * after an *offer* costs a second question. A job that ended after an
 * *agreement* means the operator does not know what happened to their machine,
 * and the app must say exactly that rather than *it failed* — because it may
 * well have worked. The remedy is to look at the stack's own health again,
 * which is a screen away, and not to agree to anything a second time.
 */
final readonly class HowTheRepairIsGoing
{
    /**
     * Every field defaults, and each constructor says only its own state.
     *
     * {@see HowTheOfferIsGoing} carries the same note for the same measurement:
     * `met()` writing `running: false` is a value nothing can ever read,
     * because {@see self::either()} consults the obstacle first — and a value
     * nothing reads is one no test can hold to being right. The cure is
     * {@see Size}'s, which is to leave the meaningless value unwritten.
     */
    private function __construct(
        private ?WhatWasMended $done = null,
        private ?Obstacle $met = null,
        private bool $running = false,
    ) {}

    /** The stack is still carrying out what it was agreed to. */
    public static function stillRunning(): self
    {
        return new self(running: true);
    }

    /** It finished, and this is what became of each repair. */
    public static function done(WhatWasMended $mended): self
    {
        return new self(done: $mended);
    }

    /**
     * The stack has no outcome for that job any more.
     *
     * Not a failure and not a result. Something may well have been done, and
     * the only honest next step is to look at the machine's health rather than
     * to agree again — which would be the app asking a stack to repeat work it
     * cannot confirm it did not already do.
     */
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
     * Say what happens in each of the four, and get back what you built.
     *
     * @template TRunning of object
     * @template TDone of object
     * @template TEnded of object
     * @template TMet of object
     *
     * @param Closure(): TRunning           $stillRunning
     * @param Closure(WhatWasMended): TDone $done
     * @param Closure(): TEnded             $ended
     * @param Closure(Obstacle): TMet       $met
     *
     * @return TRunning|TDone|TEnded|TMet
     */
    public function either(Closure $stillRunning, Closure $done, Closure $ended, Closure $met): object
    {
        // The order is the meaning, as {@see HowTheOfferIsGoing::either()} sets
        // out: being unable to reach the stack outranks anything believed about
        // the job, because what is believed was read from a machine that is not
        // answering now.
        return match (true) {
            $this->met instanceof Obstacle => $met($this->met),
            $this->running => $stillRunning(),
            $this->done instanceof WhatWasMended => $done($this->done),
            default => $ended(),
        };
    }
}
