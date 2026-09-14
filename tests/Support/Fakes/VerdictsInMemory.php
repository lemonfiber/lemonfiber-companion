<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Reading;
use Modules\Kernel\Api\Showing;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Verdicts;
use stdClass;

/**
 * Verdicts a test states, rather than a store a test has to set up.
 *
 * `B1` — the port exists so that *this stack was last seen broken, two days
 * ago* is a sentence a test writes rather than a record it has to encode,
 * store, and read back. The adapter's own tests do the encoding; nothing else
 * should have to know it happened.
 *
 * **Everything it answers is retained**, exactly as the adapter's is, because
 * there is no other kind: a verdict read in this session came off a stack and
 * never went near this port.
 *
 * `refusing()` is the device that will not keep one. It answers the same as a
 * working one for everything it was told before, so a test can say *the store
 * broke after this* without the setup becoming a story.
 */
final class VerdictsInMemory implements Verdicts
{
    /** @var array<string, Showing> */
    private array $held = [];

    private function __construct(private readonly bool $keeps) {}

    /** A device that keeps what it is given. */
    public static function working(): self
    {
        return new self(keeps: true);
    }

    /** A device that will not keep a verdict, which costs the operator nothing. */
    public static function refusing(): self
    {
        return new self(keeps: false);
    }

    /** State that a stack was last seen a certain way, at a certain moment. */
    public function lastSeen(StackId $stack, Overall $overall, Instant $at): self
    {
        $this->held[$stack->stored()] = Showing::holding(Reading::retained($overall, $at));

        return $this;
    }

    /**
     * State that a stack holds something that is not a verdict at all.
     *
     * What a store written by another build of this app looks like from in
     * here: `Reading`'s retained arm is typed `object`, so it carries whatever
     * was put in it. The adapter refuses such a record before it becomes a
     * `Reading` — this is how a screen's own guard against the same thing gets
     * a case to be driven by, since nothing else can produce one.
     */
    public function lastSeenAsSomethingElse(StackId $stack, Instant $at): self
    {
        $this->held[$stack->stored()] = Showing::holding(Reading::retained(new stdClass(), $at));

        return $this;
    }

    public function lastKnownOf(StackId $stack): Showing
    {
        return $this->held[$stack->stored()] ?? Showing::waiting();
    }

    public function remember(StackId $stack, Overall $overall, Instant $at): Noted
    {
        if (! $this->keeps) {
            return Noted::notKept();
        }

        $this->lastSeen($stack, $overall, $at);

        return Noted::downAt($at);
    }
}
