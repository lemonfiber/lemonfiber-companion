<?php

declare(strict_types=1);

namespace Modules\News\Internal;

/** Whether something about what is new was kept, carried out of the fold that sealed it. */
final readonly class WhetherItWasKept
{
    public function __construct(public bool $was) {}
}
