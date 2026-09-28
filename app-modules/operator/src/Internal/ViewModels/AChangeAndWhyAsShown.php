<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One change a report says something further about, and what it says, as a template draws it. */
final readonly class AChangeAndWhyAsShown
{
    /**
     * @param string $target  what the change was against
     * @param string $because what is said of it, in the stack's words
     */
    public function __construct(
        public string $target,
        public string $because,
    ) {}
}
