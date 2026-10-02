<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One kind a stack can mark as new, as its settings page draws the switch for it. */
final readonly class AKindOfNewsAsShown
{
    /**
     * @param string $kind     the kind, as the switch hands it back
     * @param string $said     the key for what it is called
     * @param bool   $isMarked whether the stack marks it as new
     */
    public function __construct(
        public string $kind,
        public string $said,
        public bool $isMarked,
    ) {}
}
