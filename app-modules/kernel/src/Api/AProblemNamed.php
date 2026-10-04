<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** One of the checks a stack most recently found wrong, by the check and when it went wrong, and nothing else. */
final readonly class AProblemNamed
{
    public function __construct(private Check $check, private Instant $onset) {}

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
}
