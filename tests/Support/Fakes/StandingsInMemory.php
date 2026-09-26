<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\Reading;
use Modules\Kernel\Api\Showing;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Standings;
use stdClass;

/**
 * Words a test states, rather than a store a test has to set up.
 *
 * `B1` — the port exists so that *this stack's one line last said broken, two
 * days ago* is a sentence a test writes rather than a record it has to encode,
 * store, and read back. The adapter's own tests do the encoding; nothing else
 * should have to know it happened.
 *
 * **Everything it answers is retained**, exactly as the adapter's is, because
 * there is no other kind: a word heard in this session is drawn by the screen
 * that heard it, not read back from here.
 *
 * `refusing()` is the device that will not keep one. It answers the same as a
 * working one for everything it was told before, so a test can say *the store
 * broke after this* without the setup becoming a story.
 */
final class StandingsInMemory implements Standings
{
    /** @var array<string, Showing> */
    private array $held = [];

    private function __construct(private readonly bool $keeps) {}

    /** A device that keeps what it is given. */
    public static function working(): self
    {
        return new self(keeps: true);
    }

    /** A device that will not keep a word, which costs the operator nothing. */
    public static function refusing(): self
    {
        return new self(keeps: false);
    }

    /** State that a stack's one line last said a word, heard at a certain moment. */
    public function lastHeard(StackId $stack, HowItStands $standing, Instant $at): self
    {
        $this->held[$stack->stored()] = Showing::holding(Reading::retained($standing, $at));

        return $this;
    }

    /**
     * State that a stack holds something that is not a word at all.
     *
     * What a store written by another build of this app looks like from in
     * here: `Reading`'s retained arm is typed `object`, so it carries whatever
     * was put in it. The adapter refuses such a record before it becomes a
     * `Reading` — this is how a screen's own guard against the same thing gets
     * a case to be driven by, since nothing else can produce one.
     */
    public function lastHeardAsSomethingElse(StackId $stack, Instant $at): self
    {
        $this->held[$stack->stored()] = Showing::holding(Reading::retained(new stdClass(), $at));

        return $this;
    }

    /** State that a stack holds a word read live, which the adapter never answers. */
    public function heardLive(StackId $stack, HowItStands $standing): self
    {
        $this->held[$stack->stored()] = Showing::holding(Reading::live($standing));

        return $this;
    }

    public function lastKnownOf(StackId $stack): Showing
    {
        return $this->held[$stack->stored()] ?? Showing::waiting();
    }

    public function remember(StackId $stack, HowItStands $standing, Instant $at): Noted
    {
        if (! $this->keeps) {
            return Noted::notKept();
        }

        $this->lastHeard($stack, $standing, $at);

        return Noted::downAt($at);
    }
}
