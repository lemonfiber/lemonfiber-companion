<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The one thing a monitor covers: a service, an entry point or a job, by name.
 */
final readonly class WhatAMonitorCovers
{
    private function __construct(private WhatKindOfThingIsCovered $kind, private string $name) {}

    /** A thing of this kind, by the name the stack gave it. */
    public static function of(WhatKindOfThingIsCovered $kind, string $name): self
    {
        return new self($kind, $name);
    }

    /** What kind of thing it is. */
    public function kind(): WhatKindOfThingIsCovered
    {
        return $this->kind;
    }

    /** Its name, as the stack gave it. */
    public function name(): string
    {
        return $this->name;
    }

    /** Whether it is this service. */
    public function isTheService(ServiceId $service): bool
    {
        return $this->kind === WhatKindOfThingIsCovered::Service && $this->name === $service->named();
    }
}
