<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** One moment in a traced item's history: what happened, and when the service said it did. */
final readonly class AMomentInItsHistory
{
    private function __construct(private WhatHappenedToIt $happened, private AMomentAsWritten $at) {}

    /** A moment the service recorded, with when it said, unwritten where it was not timed. */
    public static function recorded(WhatHappenedToIt $happened, AMomentAsWritten $at): self
    {
        return new self($happened, $at);
    }

    public function happened(): WhatHappenedToIt
    {
        return $this->happened;
    }

    /** When the service said it happened, unwritten where it was not timed. */
    public function at(): AMomentAsWritten
    {
        return $this->at;
    }
}
