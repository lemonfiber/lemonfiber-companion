<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;
use function count;

use Modules\Kernel\Api\AnEventHeard;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhereAStreamResumes;
use Modules\Kernel\Api\WhereHearingLeftOff;

/**
 * Where each stack's stream left off, held in memory; or a store that keeps nothing it is told.
 *
 * Not `readonly`: what is kept is written when the keeping happens.
 */
final class WhereHearingLeftOffInMemory implements WhereHearingLeftOff
{
    /** @var array<string, AnEventHeard> */
    private array $after = [];

    /** How many times an event was noted, kept or not. */
    private int $noted = 0;

    private function __construct(private readonly bool $keeps) {}

    public static function nowhere(): self
    {
        return new self(keeps: true);
    }

    public static function refusing(): self
    {
        return new self(keeps: false);
    }

    /** State that this stack's stream got as far as this event, as though an earlier launch had kept it. */
    public function heardBefore(StackId $stack, AnEventHeard $event): self
    {
        $this->after[$stack->stored()] = $event;

        return $this;
    }

    public function heardTo(StackId $stack, AnEventHeard $event): bool
    {
        $this->noted++;

        if (! $this->keeps) {
            return false;
        }

        $this->heardBefore($stack, $event);

        return true;
    }

    public function leftOffAt(StackId $stack): WhereAStreamResumes
    {
        return array_key_exists($stack->stored(), $this->after)
            ? WhereAStreamResumes::after($this->after[$stack->stored()])
            : WhereAStreamResumes::fromTheStart();
    }

    public function forgetTheStack(StackId $stack): Forgotten
    {
        if (! $this->keepsAnythingOf($stack)) {
            return Forgotten::nothing();
        }

        unset($this->after[$stack->stored()]);

        return Forgotten::rows(1);
    }

    public function keepsAnythingOf(StackId $stack): bool
    {
        return array_key_exists($stack->stored(), $this->after);
    }

    public function forgetEverything(): Forgotten
    {
        $held = count($this->after);
        $this->after = [];

        return Forgotten::rows($held);
    }

    /** How many times an event was noted, kept or not. */
    public function timesNoted(): int
    {
        return $this->noted;
    }
}
