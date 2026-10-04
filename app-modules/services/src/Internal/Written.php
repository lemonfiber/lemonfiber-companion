<?php

declare(strict_types=1);

namespace Modules\Services\Internal;

/**
 * Part of a kept listing as it is written, carried out of a fold, which hands back an object.
 */
final readonly class Written
{
    /** @param array<array-key, mixed> $held */
    public function __construct(public array $held) {}
}
