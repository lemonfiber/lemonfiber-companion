<?php

declare(strict_types=1);

namespace Modules\Updates\Internal;

/**
 * Fields as a kept reading writes them, carried out of a fold, which hands back an object.
 */
final readonly class Fields
{
    /** @param array<array-key, mixed> $held */
    public function __construct(public array $held) {}
}
