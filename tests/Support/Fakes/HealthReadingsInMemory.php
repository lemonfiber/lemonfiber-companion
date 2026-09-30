<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_filter;
use function array_key_exists;
use function count;

use Modules\Health\Internal\HealthReadingsKept;
use Modules\Health\Internal\NewestHealthReading;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedReading;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\Shape;

/**
 * The health readings the phone keeps, held for as long as a test runs.
 *
 * Held to the same contract as
 * {@see \Modules\Health\Internal\Store\HealthReadingsInTheDatabase}, so a decision
 * tested over this is tested over a store that behaves like the database: one
 * reading per stack, the later replacing the earlier, a reading read exactly at
 * the cut-off kept, and a row this build cannot read reachable, because a
 * later build writing one is the case the third answer exists for.
 *
 * Written by hand rather than mocked, so a change to the port fails to compile
 * here rather than drifting.
 */
final class HealthReadingsInMemory implements HealthReadingsKept
{
    /** @var array<string, SealedReading> the stack's hash => its reading */
    private array $readings = [];

    /** @var array<string, true> the stack's hash, for each row this build cannot read */
    private array $unreadable = [];

    private function __construct(private readonly bool $reachable) {}

    /** A store that answers, holding nothing yet. */
    public static function empty(): self
    {
        return new self(reachable: true);
    }

    /** A store that will not answer: nothing is kept, found or forgotten. */
    public static function unreachable(): self
    {
        return new self(reachable: false);
    }

    public function keep(SealedStack $stack, SealedPayload $payload, Shape $shape, Instant $readAt): Noted
    {
        if (! $this->reachable) {
            return Noted::notKept();
        }

        unset($this->unreadable[$stack->forTheStore()]);
        $this->readings[$stack->forTheStore()] = SealedReading::of($payload, $shape, $readAt);

        return Noted::downAt($readAt);
    }

    public function newest(SealedStack $stack): NewestHealthReading
    {
        if (array_key_exists($stack->forTheStore(), $this->unreadable)) {
            return NewestHealthReading::thatThisBuildCannotRead();
        }

        if (! array_key_exists($stack->forTheStore(), $this->readings)) {
            return NewestHealthReading::none();
        }

        $reading = $this->readings[$stack->forTheStore()];

        return NewestHealthReading::found($reading->payload(), $reading->shape(), $reading->readAt());
    }

    public function forget(SealedStack $stack): Forgotten
    {
        $had = count($this->readings) + count($this->unreadable);

        unset($this->readings[$stack->forTheStore()], $this->unreadable[$stack->forTheStore()]);

        return Forgotten::rows($had - count($this->readings) - count($this->unreadable));
    }

    public function forgetOlderThan(Instant $before): Forgotten
    {
        $had = count($this->readings);

        $this->readings = array_filter(
            $this->readings,
            static fn(SealedReading $reading): bool => ! $reading->readAt()->isBefore($before),
        );

        return Forgotten::rows($had - count($this->readings));
    }

    public function forgetEverything(): Forgotten
    {
        $had = count($this->readings) + count($this->unreadable);

        $this->readings = [];
        $this->unreadable = [];

        return Forgotten::rows($had);
    }

    /** A row for this stack that a later build wrote: for a test to arrange. */
    public function holdsOneALaterBuildWrote(SealedStack $stack): self
    {
        unset($this->readings[$stack->forTheStore()]);
        $this->unreadable[$stack->forTheStore()] = true;

        return $this;
    }
}
