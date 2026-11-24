<?php

declare(strict_types=1);

namespace Modules\Health\Internal;

/** Whether a stack's mark is kept, carried out of the fold that read it. */
final readonly class WhetherAMarkIsKept
{
    public function __construct(public bool $is) {}
}
