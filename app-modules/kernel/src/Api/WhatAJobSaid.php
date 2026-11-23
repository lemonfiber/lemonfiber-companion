<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_slice;
use function array_values;
use function count;

/** Every line one job said, newest last, kept to as many as a phone shows. */
final readonly class WhatAJobSaid
{
    /** @param list<string> $lines */
    private function __construct(
        public array $lines,
        public int $letGo,
    ) {}

    public static function nothing(): self
    {
        return new self([], 0);
    }

    /** What the job said with what was heard from it since added, the oldest let go of past the bound. */
    public function heard(WhatWasHeardFromAJob $heard): self
    {
        return $heard->either(
            said: fn(string ...$lines): self => $this->keeping(...$lines),
            nothingNew: fn(): self => $this,
            met: fn(): self => $this,
        );
    }

    private function keeping(string ...$lines): self
    {
        $kept = [...$this->lines, ...array_values($lines)];
        $over = count($kept) - HowManyLines::ON_A_PHONE;

        if ($over <= 0) {
            return new self($kept, $this->letGo);
        }

        return new self(array_slice($kept, $over), $this->letGo + $over);
    }
}
