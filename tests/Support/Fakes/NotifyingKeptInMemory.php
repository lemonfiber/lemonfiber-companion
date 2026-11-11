<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;
use function count;

use Modules\Connection\Internal\KeptSettings;
use Modules\Connection\Internal\NotifyingKept;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\Shape;

/**
 * What the operator lets reach the phone from each stack, kept in memory, for a test to arrange and read.
 *
 * Held to `NotifyingKeptContractTest` beside the adapter, so what it promises is
 * what the database does: one row a stack, a later one replacing the earlier,
 * and a row a later build wrote answered as one this build cannot read.
 */
final class NotifyingKeptInMemory implements NotifyingKept
{
    /** @var array<string, array{SealedPayload, Shape}> each row, by stack */
    private array $rows = [];

    /** @var array<string, true> the rows a later build wrote, by stack */
    private array $unreadable = [];

    private function __construct(private readonly bool $reachable) {}

    /** A store that keeps what it is given, holding nothing yet. */
    public static function empty(): self
    {
        return new self(reachable: true);
    }

    /** A store every request to is refused, as a database whose table is not there. */
    public static function unreachable(): self
    {
        return new self(reachable: false);
    }

    public function keep(SealedStack $stack, SealedPayload $payload, Shape $shape, Instant $notedAt): Noted
    {
        if (! $this->reachable) {
            return Noted::notKept();
        }

        unset($this->unreadable[$stack->forTheStore()]);
        $this->rows[$stack->forTheStore()] = [$payload, $shape];

        return Noted::downAt($notedAt);
    }

    public function found(SealedStack $stack): KeptSettings
    {
        if (array_key_exists($stack->forTheStore(), $this->unreadable)) {
            return KeptSettings::thatThisBuildCannotRead();
        }

        if (! array_key_exists($stack->forTheStore(), $this->rows)) {
            return KeptSettings::none();
        }

        [$payload, $shape] = $this->rows[$stack->forTheStore()];

        return KeptSettings::found($payload, $shape);
    }

    public function forget(SealedStack $stack): Forgotten
    {
        $had = $this->howMany();

        unset($this->rows[$stack->forTheStore()], $this->unreadable[$stack->forTheStore()]);

        return Forgotten::rows($had - $this->howMany());
    }

    public function forgetEverything(): Forgotten
    {
        $had = $this->howMany();

        $this->rows = [];
        $this->unreadable = [];

        return Forgotten::rows($had);
    }

    /** A row for this stack that a later build wrote: for a test to arrange. */
    public function holdsOneALaterBuildWrote(SealedStack $stack): self
    {
        unset($this->rows[$stack->forTheStore()]);
        $this->unreadable[$stack->forTheStore()] = true;

        return $this;
    }

    private function howMany(): int
    {
        return count($this->rows) + count($this->unreadable);
    }
}
