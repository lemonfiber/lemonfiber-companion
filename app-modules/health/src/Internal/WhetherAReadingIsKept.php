<?php

declare(strict_types=1);

namespace Modules\Health\Internal;

/** Whether the store holds a reading for a stack, as an answer a closure can give. */
final readonly class WhetherAReadingIsKept
{
    public function __construct(public bool $is) {}
}
