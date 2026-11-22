<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** One thing in the stack a declaration covers, with its kind. */
final readonly class AThingLeftAlone
{
    use ReadsWhatIsLeftAlone;

    private function __construct(
        public WhatIsLeftAlone $kind,
        public string $name,
    ) {}

    public static function of(WhatIsLeftAlone $kind, string $name): self
    {
        return new self($kind, self::said($name, 'name'));
    }
}
