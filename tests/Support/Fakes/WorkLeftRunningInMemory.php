<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_any;
use function array_key_exists;
use function count;

use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfWork;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhatAReturnFinds;
use Modules\Kernel\Api\WorkLeftRunning;

use function sprintf;

/**
 * Handles of work left running, kept in memory for the length of a test.
 *
 * `B1` — the port exists so that *a walk was left running on this stack* is a
 * sentence a test writes rather than a value it has to encode, store and read
 * back. The adapter's own tests do the encoding; nothing else should have to
 * know it happened.
 *
 * `refusing()` is the device that will not keep one. It reads back what it was
 * told before, so a test can say *a walk was left here and this device would
 * not note the next one* without the setup becoming a story — and it forgets as
 * a working one does, because the contract promises nothing a return would find
 * after forgetting either way.
 *
 * Not `readonly`: what is kept is written when the keeping happens.
 */
final class WorkLeftRunningInMemory implements WorkLeftRunning
{
    /** @var array<string, Job> */
    private array $held = [];

    private function __construct(private readonly bool $keeps) {}

    /** A device that keeps what it is given. */
    public static function working(): self
    {
        return new self(keeps: true);
    }

    /** A device that will not keep a handle, which loses the way back and not the work. */
    public static function refusing(): self
    {
        return new self(keeps: false);
    }

    /** State that work of this kind was left running on a stack, as though an earlier screen had kept it. */
    public function leftBefore(StackId $stack, KindOfWork $work, Job $job): self
    {
        $this->held[$this->under($stack, $work)] = $job;

        return $this;
    }

    public function whatWasLeft(StackId $stack, KindOfWork $work): WhatAReturnFinds
    {
        $under = $this->under($stack, $work);

        return array_key_exists($under, $this->held)
            ? WhatAReturnFinds::theJob($this->held[$under])
            : WhatAReturnFinds::nothing();
    }

    public function remember(StackId $stack, KindOfWork $work, Job $job): WhatAReturnFinds
    {
        if (! $this->keeps) {
            return WhatAReturnFinds::nothing();
        }

        $this->leftBefore($stack, $work, $job);

        return WhatAReturnFinds::theJob($job);
    }

    public function forget(StackId $stack, KindOfWork $work): WhatAReturnFinds
    {
        unset($this->held[$this->under($stack, $work)]);

        return WhatAReturnFinds::nothing();
    }

    /** One entry per stack and kind, as the adapter keeps one key per stack and kind. */
    public function forgetEverything(): Forgotten
    {
        $held = count($this->held);
        $this->held = [];

        return Forgotten::rows($held);
    }

    public function forgetTheStack(StackId $stack): Forgotten
    {
        $forgotten = Forgotten::nothing();

        foreach (KindOfWork::cases() as $work) {
            $forgotten = $forgotten->beside(Forgotten::rows(array_key_exists($this->under($stack, $work), $this->held) ? 1 : 0));
            $this->forget($stack, $work);
        }

        return $forgotten;
    }

    public function keepsAnythingOf(StackId $stack): bool
    {
        return array_any(KindOfWork::cases(), fn(KindOfWork $work): bool => array_key_exists($this->under($stack, $work), $this->held));
    }

    private function under(StackId $stack, KindOfWork $work): string
    {
        return sprintf('%s|%s', $work->value, $stack->stored());
    }
}
