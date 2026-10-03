<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One service a start would bring up, or that is already running, flattened to a row. */
final readonly class AServiceToStartAsShown
{
    /**
     * @param string $name the service, as the stack names it
     * @param string $said the catalogue key for its line: it would start, or it is already running
     */
    public function __construct(
        public string $name,
        public string $said,
    ) {}
}
