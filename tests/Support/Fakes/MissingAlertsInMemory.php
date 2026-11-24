<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;
use function count;

use Modules\Health\Internal\MissingAlertsKept;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\SealedStack;

/**
 * Which stacks may be missing alerts, kept in memory, for a test to arrange and read.
 *
 * Held to `MissingAlertsKeptContractTest` beside the adapter, so what it
 * promises is what the database does: one mark a stack, however often it is
 * marked.
 */
final class MissingAlertsInMemory implements MissingAlertsKept
{
    /** @var array<string, Instant> when each stack was marked, by stack */
    private array $marks = [];

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

    public function mark(SealedStack $stack, Instant $notedAt): Noted
    {
        if (! $this->reachable) {
            return Noted::notKept();
        }

        $this->marks[$stack->forTheStore()] = $notedAt;

        return Noted::downAt($notedAt);
    }

    public function whenMarked(SealedStack $stack): Noted
    {
        return array_key_exists($stack->forTheStore(), $this->marks) ? Noted::downAt($this->marks[$stack->forTheStore()]) : Noted::notKept();
    }

    public function forget(SealedStack $stack): Forgotten
    {
        $had = count($this->marks);
        unset($this->marks[$stack->forTheStore()]);

        return Forgotten::rows($had - count($this->marks));
    }

    public function forgetEverything(): Forgotten
    {
        $had = count($this->marks);
        $this->marks = [];

        return Forgotten::rows($had);
    }
}
