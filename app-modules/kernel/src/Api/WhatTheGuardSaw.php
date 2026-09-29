<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What a guard did once it saw the data location go.
 *
 * The forms it stopped, whether stopping them worked, and why it ended. *Whether
 * it worked* is the part worth reading twice: a guard that saw the drive go and
 * could not stop the services has protected nothing, and nothing here lets
 * that read as a guard that did.
 */
final readonly class WhatTheGuardSaw
{
    private function __construct(
        private Forms $forms,
        private bool $stopped,
        private string $reason,
    ) {}

    /** The forms it was guarding, whether stopping them worked, and why it ended, as the stack said. */
    public static function of(Forms $forms, bool $stopped, string $reason): self
    {
        return new self($forms, $stopped, $reason);
    }

    /** The forms it was guarding, which it set out to stop. */
    public function forms(): Forms
    {
        return $this->forms;
    }

    /** Whether stopping them worked. */
    public function stoppedThem(): bool
    {
        return $this->stopped;
    }

    /** Why it ended, in the stack's words. */
    public function reason(): string
    {
        return $this->reason;
    }
}
