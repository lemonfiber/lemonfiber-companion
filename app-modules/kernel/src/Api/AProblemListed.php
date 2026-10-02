<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** One check a stack found wrong, by the check and when it went wrong. */
final readonly class AProblemListed
{
    public function __construct(private Check $check, private Instant $onset, private string $summary) {}

    /** The check that raised it. */
    public function check(): Check
    {
        return $this->check;
    }

    /** When the stack first saw it wrong since it last saw it right. */
    public function onset(): Instant
    {
        return $this->onset;
    }

    /** What is wrong, in one line, in the stack's words. */
    public function summary(): string
    {
        return $this->summary;
    }
}
