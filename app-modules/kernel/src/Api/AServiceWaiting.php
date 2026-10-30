<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_any;
use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * A service the stack holds back until the operator gives what it needs to start, with everything it waits for in the stack's order.
 *
 * @implements IteratorAggregate<int, WhatAServiceWaitsFor>
 */
final readonly class AServiceWaiting implements IteratorAggregate
{
    /** @param list<WhatAServiceWaitsFor> $wants */
    private function __construct(private ServiceId $service, private array $wants) {}

    /** This service, waiting for these, in the stack's order. */
    public static function for(ServiceId $service, WhatAServiceWaitsFor ...$wants): self
    {
        return new self($service, array_values($wants));
    }

    /** The service. */
    public function service(): ServiceId
    {
        return $this->service;
    }

    /** Whether one of the things it waits for is this setting. */
    public function waitsFor(ASetting $setting): bool
    {
        return array_any($this->wants, static fn(WhatAServiceWaitsFor $want): bool => $want->isTheSetting($setting));
    }

    /** @return Traversable<int, WhatAServiceWaitsFor> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->wants);
    }
}
