<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/** One moment in a traced item's history: what happened, and when the service said it did. */
final readonly class AMomentInItsHistory
{
    private function __construct(private WhatHappenedToIt $happened, private string $at) {}

    /** A moment the service recorded; when is required. */
    public static function recorded(WhatHappenedToIt $happened, string $at): self
    {
        if (trim($at) === '') {
            throw TheTraceSaysNothing::about('at');
        }

        return new self($happened, $at);
    }

    public function happened(): WhatHappenedToIt
    {
        return $this->happened;
    }

    /** When the service said it happened. */
    public function at(): string
    {
        return $this->at;
    }
}
